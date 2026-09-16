# Project Brief — volksimmobilien

Fakten aus der bestehenden Website und dem Impressum. Keine neuen Kundendaten erfinden.

## Identität und Ziel

- Projektname: volksimmobilien
- Sichtbare Marke: volksimmobilien
- Firma: volksimmobilien km GmbH
- Wertversprechen: Persönlich vor Ort, ehrlich bewertet, klar vermarktet. Immobilienmakler von Heidelberg bis Baden-Baden.
- Primäres Ziel: Verkaufsmandate, Wertermittlungen, Kaufanfragen
- Zielgruppe: Eigentümer und Käufer in der Achse Heidelberg–Baden-Baden
- Gebiet / Sprache: Baden-Württemberg, kanonisch Deutsch. Übersetzungen über Leadwerk Sprachen, nicht als extra HTML-Ordner.
- Ton: Direkt, persönlich, ohne leere Versprechen.

## Seiten

Kanonische HTML-Dateien und `sourceKey` aus `leadwerk_importer/manifest/mapping-volks.json`. Nicht ändern.

- Start (`volks-home-v1`), Bewerten, Kaufen, Verkaufen, Ausland, Mallorca
- Standortseiten Durmersheim, Ettlingen, Gaggenau, Malsch, Rastatt, Rheinstetten, Au am Rhein
- Gewerbeimmobilie verkaufen, Mehrfamilienhaus verkaufen, Verkaufs-Checkliste
- Impressum, Datenschutz, Danke, 404

`index-alternative.html` und `instagram-vorschau.html` sind keine GTD-Seiten.

## Marke und Medien

- Logo: `Fotos/volksimmobilien-logo-weiss.webp`
- Favicon: `assets/favicon.webp`
- Primärfarbe: `#C95A3F`
- Standard-OG: `Fotos/Slider1_optimiert.webp`
- Fonts lokal: Plus Jakarta Sans, DM Sans (OFL in `fonts/`)
- Instagram: https://www.instagram.com/volksimmobilien/

Bestehende Bildpfade unter `Fotos/` nicht umbenennen. Live-Attachments hängen an `leadwerk_source_path`.

## Kontakt und Formulare

- Adresse: Würmersheimer Straße 6, 76474 Au am Rhein
- Telefon: +49 170 2 98 51 41
- E-Mail: info@volksimmobilien.eu
- Bestehende WPForms über Leadwerk Optionen: `wpforms_form_id_de`, optional `wpforms_form_id_en`, `wpforms_form_id_valuation`
- Keine neuen WPForms anlegen
- Wertermittlungsrechner sendet an das vorhandene Valuation-Formular
- Danke ist die Bestätigungsseite

## WordPress-Besonderheiten

- Propstack-CPT `/immobilien/` bleibt Plugin-Archiv, keine GTD-Seite
- Overlay liest `volks_*_sections` / `volks_*_page` und `leadwerk_opt_*`
- `ownedContentAdoption: preserve`
