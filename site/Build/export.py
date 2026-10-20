"""Export the running TYPO3 site as static HTML for GitHub Pages.

Crawls all pages of all languages, saves them with their assets, rewrites URLs
for the target base path and writes a plain sitemap.xml.
Usage: python export.py http://127.0.0.1:8080 ../docs/site https://sayamrina.github.io/fluvenco-typo3/site/
"""
import re
import shutil
import sys
import urllib.parse
import urllib.request
from pathlib import Path

SOURCE = sys.argv[1].rstrip("/")
OUT = Path(sys.argv[2])
TARGET = sys.argv[3].rstrip("/") + "/"
TARGET_PATH = urllib.parse.urlparse(TARGET).path
START = ["/de-de/", "/en/", "/fr-fr/"]
ASSET = re.compile(r'(?:href|src)="(/(?:_assets|fileadmin|typo3temp)/[^"?#]+)')
LINK = re.compile(r'href="(/[^"#?]*)"')


def fetch(path):
    with urllib.request.urlopen(SOURCE + path) as response:
        return response.read()


if OUT.exists():
    shutil.rmtree(OUT)
pages, assets, queue = [], set(), list(START)
while queue:
    path = queue.pop(0)
    if path in pages:
        continue
    html = fetch(path).decode()
    pages.append(path)
    assets.update(ASSET.findall(html))
    for link in LINK.findall(html):
        if not ASSET.match(f'href="{link}') and link not in pages and link not in queue and not link.startswith("/typo3"):
            queue.append(link)
    # Absolute URLs (canonical, hreflang) and root-relative links point to the published copy
    html = html.replace(SOURCE + "/", TARGET)
    html = re.sub(r'(href|src)="/(?!/)', rf'\1="{TARGET_PATH}', html)
    html = re.sub(r"\?\d{10}\"", '"', html)
    target = OUT / path.strip("/") / "index.html"
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(html)

for asset in sorted(assets):
    data = fetch(asset)
    target = OUT / asset.lstrip("/")
    target.parent.mkdir(parents=True, exist_ok=True)
    if asset.endswith(".css"):
        data = data.decode().replace("url(/", f"url({TARGET_PATH}").encode()
    target.write_bytes(data)

(OUT / "index.html").write_text(
    f'<!doctype html><meta charset="utf-8"><title>Fluvenco</title>'
    f'<meta http-equiv="refresh" content="0; url={TARGET}de-de/"><link rel="canonical" href="{TARGET}de-de/">'
    f'<a href="{TARGET}de-de/">Fluvenco</a>'
)
urls = "".join(f"<url><loc>{TARGET}{p.lstrip('/')}</loc></url>" for p in pages)
(OUT / "sitemap.xml").write_text(
    f'<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">{urls}</urlset>\n'
)
print(f"{len(pages)} pages, {len(assets)} assets -> {OUT}")
