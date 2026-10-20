# QA-Checkliste vor der Veröffentlichung

Gilt für neue Seiten, Relaunches und Länderwebsites. Punkte mit ⚙ prüft das Skript `site/Build/qa/qa.py` automatisch für alle Seiten in allen Sprachen.

## Inhalt
- [ ] Texte fachlich freigegeben, technische Daten stimmen mit dem Datenblatt überein
- [ ] Rechtschreibung geprüft, Fachbegriffe nach Glossar
- [ ] Ansprechpartner und Kontaktdaten aktuell

## SEO
- [ ] ⚙ Seitentitel vorhanden
- [ ] ⚙ Meta-Beschreibung vorhanden
- [ ] ⚙ genau eine H1-Überschrift
- [ ] ⚙ `hreflang`-Links für alle Sprachen und `x-default`
- [ ] URL-Segment kurz und in der jeweiligen Sprache

## Barrierefreiheit (WCAG 2.1 AA)
- [ ] ⚙ automatische Prüfung mit axe-core ohne Verstöße (Kontraste, Labels, Sprache, Struktur)
- [ ] ⚙ alle Bilder mit Alternativtext
- [ ] Seite mit der Tastatur bedienbar (Tab-Reihenfolge, sichtbarer Fokus, „Zum Inhalt springen“)

## Technik
- [ ] ⚙ alle internen Links erreichbar (HTTP 200)
- [ ] Darstellung geprüft: Desktop, Tablet, Smartphone
- [ ] Browser: Chrome, Firefox, Safari, Edge
- [ ] Ladezeit: Bilder in passender Größe, keine unnötigen externen Skripte

## Ergebnis in diesem Projekt

Der letzte automatische Lauf ist in `QA-Bericht.md` dokumentiert.
