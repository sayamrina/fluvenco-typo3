"""Automated quality check for the Fluvenco site.

Crawls every page in every language from the start pages, then checks:
HTTP status, broken internal links, page title, meta description, exactly one h1,
image alt text, hreflang alternates, and accessibility (axe-core, WCAG 2.1 AA).
Writes a Markdown report. Usage: python qa.py http://127.0.0.1:8080 report.md
"""
import sys
import urllib.parse
from collections import Counter
from datetime import date
from pathlib import Path

from playwright.sync_api import sync_playwright

BASE = sys.argv[1].rstrip("/")
REPORT = Path(sys.argv[2])
START = ["/en/", "/de-de/", "/fr-fr/"]
AXE = (Path(__file__).parent / "axe.min.js").read_text()

results, issues, axe_rules = [], [], Counter()
seen, queue = set(), list(START)

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page(viewport={"width": 1280, "height": 800})
    while queue:
        path = queue.pop(0)
        if path in seen:
            continue
        seen.add(path)
        response = page.goto(BASE + path, wait_until="load")
        status = response.status if response else 0
        row = {"path": path, "status": status}
        if status != 200:
            issues.append(f"{path}: HTTP {status}")
            results.append(row)
            continue
        info = page.evaluate("""() => ({
            title: document.title,
            description: document.querySelector('meta[name=description]')?.content || '',
            h1: document.querySelectorAll('h1').length,
            missingAlt: [...document.images].filter(i => !i.hasAttribute('alt')).map(i => i.src),
            hreflang: document.querySelectorAll('link[rel=alternate][hreflang]').length,
            lang: document.documentElement.lang,
            links: [...document.querySelectorAll('a[href]')].map(a => a.getAttribute('href')),
        })""")
        row.update(title=info["title"], h1=info["h1"], hreflang=info["hreflang"], lang=info["lang"])
        if not info["description"]:
            issues.append(f"{path}: meta description missing")
        if info["h1"] != 1:
            issues.append(f"{path}: {info['h1']} h1 headings (expected 1)")
        for src in info["missingAlt"]:
            issues.append(f"{path}: image without alt attribute ({src})")
        if info["hreflang"] < 4:
            issues.append(f"{path}: only {info['hreflang']} hreflang links")
        for href in info["links"]:
            url = urllib.parse.urlparse(urllib.parse.urljoin(BASE + path, href))
            if url.netloc == urllib.parse.urlparse(BASE).netloc and not href.startswith("#"):
                target = url.path
                if target not in seen and target not in queue:
                    queue.append(target)
        # Measure the final state, not a frame of an entry animation
        page.evaluate("() => Promise.all(document.getAnimations().map(a => a.finished))")
        page.add_script_tag(content=AXE)
        axe = page.evaluate("async () => (await axe.run(document, {runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa']})).violations.map(v => ({id: v.id, impact: v.impact, n: v.nodes.length}))")
        row["axe"] = len(axe)
        for v in axe:
            axe_rules[v["id"]] += v["n"]
            issues.append(f"{path}: accessibility {v['id']} ({v['impact']}, {v['n']} elements)")
        results.append(row)
    browser.close()

pages = [r for r in results if r["status"] == 200]
lines = [
    "# QA-Bericht / QA report",
    "",
    f"Automatischer Lauf vom {date.today():%d.%m.%Y} mit `site/Build/qa/qa.py` (Playwright + axe-core 4.10, Regeln WCAG 2.0 A/AA und 2.1 AA).",
    "",
    f"Geprüfte URLs: **{len(results)}** · erfolgreich (HTTP 200): **{len(pages)}** · gefundene Probleme: **{len(issues)}**",
    "",
    "| Seite | Sprache | Titel | h1 | hreflang | axe-Verstöße |",
    "|---|---|---|---|---|---|",
]
for r in results:
    title = r.get("title", "HTTP " + str(r["status"])).replace("|", "·")
    lines.append(f"| `{r['path']}` | {r.get('lang', '')} | {title} | {r.get('h1', '')} | {r.get('hreflang', '')} | {r.get('axe', '')} |")
lines += ["", "## Probleme", ""] + ([f"- {i}" for i in issues] or ["Keine Probleme gefunden."])
REPORT.write_text("\n".join(lines) + "\n")
print(f"{len(results)} URLs, {len(issues)} issues")
for i in issues[:40]:
    print(" -", i)
