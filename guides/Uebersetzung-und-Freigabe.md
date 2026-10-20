# Übersetzung und Launch einer Länderwebsite

So wird eine neue Sprache oder Länderwebsite eingeführt und gepflegt. Beispiel in diesem Projekt: die französische Website (`/fr-fr/`).

## Aufbau

- **Ein Seitenbaum für alle Sprachen.** Deutsch ist die Standardsprache, Englisch und Französisch sind Übersetzungen.
- **Connected Mode:** Jede Übersetzung ist mit ihrem deutschen Original verbunden. Ändert sich die Struktur auf Deutsch, sieht die Länderredaktion sofort, welche Inhalte fehlen oder veraltet sind.
- **Strikte Sprachfolge:** Ist eine Seite nicht übersetzt, wird sie in dieser Sprache nicht angezeigt. Es erscheinen also keine gemischten Seiten aus zwei Sprachen.
- **URL-Schema:** `/de-de/`, `/en/`, `/fr-fr/`, wobei die URL-Segmente in der jeweiligen Sprache sind (z. B. `/fr-fr/produits/capteurs`).

## Neue Sprache anlegen (Administrator)

1. **Site Management > Sites**: Site bearbeiten, Reiter *Languages*, neue Sprache hinzufügen (Titel, Locale z. B. `it_IT.UTF-8`, Basis `/it-it/`, hreflang `it-IT`, Fallback *strict*).
2. Sprachdatei für die Oberfläche (Menü, Footer) ergänzen: `Resources/Private/Language/it.locallang.xlf`.
3. Benutzergruppe **Übersetzung Italienisch** anlegen und unter *Zugriffsrechte > Sprachen* nur Italienisch erlauben.

## Übersetzungsablauf je Seite

| Schritt | Wer | Was |
|---|---|---|
| 1 | Zentrale Redaktion | Deutsche Seite fertigstellen und freigeben |
| 2 | Zentrale Redaktion | Im Seitenmodul *Sprachvergleich* die Seite für die neue Sprache übersetzen lassen (Schaltfläche **Übersetzen**). TYPO3 legt verbundene Kopien an, die mit `[Translate to …]` markiert und verborgen sind |
| 3 | Länderredaktion | Texte, URL-Segment, SEO-Titel, Beschreibung und Alternativtexte übersetzen. Fachbegriffe nach Glossar |
| 4 | Fachabteilung im Land | Technische Daten und Begriffe prüfen |
| 5 | Länderredaktion | QA-Checkliste abarbeiten, Übersetzung sichtbar schalten |

## Glossar (Auszug)

| Deutsch | Englisch | Französisch |
|---|---|---|
| Hydrospeicher | accumulator | accumulateur |
| Filterelement | filter element | élément filtrant |
| Rücklauffilter | return line filter | filtre de retour |
| Zustandsüberwachung | condition monitoring | surveillance d’état |
| Betriebsdruck | operating pressure | pression de service |

## Checkliste Go-live einer Länderwebsite

- [ ] Alle Seiten der Hauptnavigation übersetzt und sichtbar
- [ ] Impressum und Datenschutz in der Landessprache geprüft
- [ ] URL-Segmente in der Landessprache, keine `translate-to-…` Reste
- [ ] SEO-Titel und Beschreibungen übersetzt
- [ ] `hreflang`-Links auf allen Seiten vorhanden (automatisch, Stichprobe im Quelltext)
- [ ] Sprachumschalter führt auf die passende Seite in der anderen Sprache
- [ ] Automatischer QA-Lauf ohne Fehler (`Build/qa/qa.py`)
- [ ] Länderredaktion geschult und Zugang eingerichtet
