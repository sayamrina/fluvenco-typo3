# Redakteursleitfaden: Fluvenco Website (TYPO3 13)

Dieser Leitfaden richtet sich an Redakteurinnen und Redakteure in der Zentrale und in den Länderredaktionen. Er erklärt die häufigsten Aufgaben Schritt für Schritt.

## 1. Anmelden und Orientierung

1. Backend aufrufen: `https://<domain>/typo3/` und mit dem eigenen Benutzerkonto anmelden.
2. Links im Modulmenü **Web > Seite** öffnen. In der Mitte erscheint der Seitenbaum, rechts der Inhalt der gewählten Seite.
3. Über **Sprache** (oben im Seitenmodul) zwischen Englisch (Hauptversion), Deutsch und Französisch wechseln. Die Ansicht **Sprachvergleich** zeigt alle Sprachen nebeneinander.

## 2. Inhalte bearbeiten

1. Seite im Seitenbaum anklicken.
2. Beim gewünschten Inhaltselement auf das **Stift-Symbol** klicken.
3. Text ändern und **Speichern** (Strg + S). Mit dem **Auge-Symbol** lässt sich die Seite in der Vorschau prüfen.

Erlaubte Inhaltselemente (die Auswahl ist bewusst begrenzt, damit das Layout einheitlich bleibt):

| Element | Wofür |
|---|---|
| Text | Fließtext mit Überschrift und Links |
| Text & Medien | Text mit einem Bild links oder rechts |
| Aufzählung | Vorteile, Einsatzbereiche |
| Tabelle | Technische Daten (erste Zeile = Kopfzeile, Spalten mit senkrechtem Strich trennen) |
| Hero (Bühne) | Große Einstiegsfläche, nur auf Startseiten |
| Teaser-Karten (Unterseiten) | Zeigt die Unterseiten einer Seite als Karten. Bild und Kurztext kommen aus den Seiteneigenschaften der Unterseite (Feld *Medien* und *Kurzfassung*) |

## 3. Neue Seite anlegen

1. Im Seitenbaum oben das Symbol **Neue Seite** auf die gewünschte Position ziehen.
2. Titel eingeben. Die URL (Feld **URL-Segment**) wird automatisch erzeugt. Bitte kurz und klein geschrieben halten, z. B. `/products/filtration` (Deutsch: `/produkte/filtertechnik`).
3. Reiter **SEO** ausfüllen (siehe Abschnitt 5).
4. Reiter **Ressourcen > Medien**: ein Bild für die Teaser-Karte hinterlegen.
5. Seite speichern. Neue Seiten sind zunächst **verborgen**. Erst nach der Freigabe (siehe Abschnitt 6) sichtbar schalten.

## 4. Bilder

- Bilder im Modul **Datei > Dateiliste** im passenden Ordner hochladen (z. B. `fluvenco/produkte`).
- Dateinamen sprechend wählen: `filter-druckfilter-df420.jpg`, nicht `IMG_2034.jpg`.
- Jedes Bild braucht einen **Alternativtext**, der beschreibt, was zu sehen ist. Er ist wichtig für Barrierefreiheit und Suchmaschinen. In Übersetzungen wird der Alternativtext mit übersetzt.

## 5. SEO-Felder je Seite

| Feld | Regel |
|---|---|
| Seitentitel (SEO) | 50 bis 60 Zeichen, wichtigster Begriff vorne |
| Beschreibung | 120 bis 160 Zeichen, beschreibt den Nutzen der Seite |
| URL-Segment | kurz, sprechend, je Sprache in der jeweiligen Sprache |
| Überschrift H1 | genau eine pro Seite (das erste Element mit Layout *Überschrift 1*) |

Canonical-Links, `hreflang`-Angaben für alle Sprachen und die XML-Sitemap (`/en/sitemap.xml`) erzeugt TYPO3 automatisch.

## 6. Freigabe

1. Redaktion bearbeitet die Seite (verborgen oder in der Vorschau).
2. Fachabteilung prüft Inhalt und technische Daten.
3. Redaktion arbeitet die QA-Checkliste ab (`QA-Checkliste.md`).
4. Seite sichtbar schalten (Schalter-Symbol im Seitenbaum oder in den Seiteneigenschaften).

## 7. Rollen im Backend

| Benutzergruppe | Rechte |
|---|---|
| Redaktion (alle Sprachen) | Seiten und Inhalte in allen Sprachen bearbeiten |
| Übersetzung Deutsch | nur deutsche Inhalte bearbeiten |
| Übersetzung Französisch | nur französische Inhalte bearbeiten |

Alle Gruppen sehen nur die freigegebenen Inhaltselemente und Felder. Administratoren verwalten Templates, Seitenkonfiguration und Benutzer.

## 8. Häufige Fragen

**Ich sehe meine Änderung nicht auf der Website.** Seite im Backend über das Blitz-Symbol *Cache leeren* oder kurz warten. Ist die Seite eventuell noch verborgen?

**Ein Inhalt ist nur auf Englisch da.** Die Übersetzung fehlt. Siehe `Uebersetzung-und-Freigabe.md`.

**Ich habe etwas gelöscht.** Im Modul **Web > Papierkorb** lässt sich der Inhalt wiederherstellen.
