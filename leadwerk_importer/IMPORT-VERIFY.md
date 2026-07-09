# volksimmobilien – Import-Verifikation

## Vor dem Import

- [ ] PHP-Erweiterung `dom` aktiv
- [ ] `leadwerk-fields` aktiv, **ACF deaktiviert**
- [ ] Theme `volksimmobilien_theme` aktiv
- [ ] Repo-Root enthält `index.html` und `Fotos/` (oder `source_assets` per Sync-Skript befüllt)
- [ ] `mapping-volks.json` vorhanden (9 Seiten inkl. Danke + 404)

## Import (WP Admin → Werkzeuge → Leadwerk Import)

1. **Dry-Run** starten – Preflight grün, ~9 Seiten, Medienanzahl plausibel
2. **Live-Import** – Log ohne blocking errors
3. **Einstellungen → Permalinks → Speichern**

## Smoke-Tests Frontend

| Test | Erwartung |
|------|-----------|
| Startseite `/` | Alle Sektionen sichtbar, Hero-Video, Kreiskarte |
| Header „Bewerten“ | `/bewerten/` (kein `.html`) |
| Header „Wertermittlung“ (Unterseite) | `/#wertermittlung` |
| Footer Impressum/Datenschutz | `/impressum/`, `/datenschutz/` |
| Danke (WPForms) | `/danke/` |
| 404 (unbekannte URL) | Theme `404.php`, Inhalt aus Import |
| Footer Kaufen/Verkaufen/Mallorca | jeweilige Permalinks |
| Interne Content-Links | keine 404 auf `*.html` |
| Bilder | aus Uploads, nicht aus `/plugins/.../source_assets/` |
| Slider / Mallorca-Hintergründe | `background-image` in `style` wird aufgelöst |
| Fehlende Medien nach Update | AJAX `leadwerk_repair_volks_media` oder erneuter Import (Options-Schritt repariert Felder) |

## Bekannte Hinweise

- Kontaktformular nutzt ggf. `mailto:` via `main.js` – bei Bedarf WPForms-ID in Leadwerk Optionen setzen
- `index-alternative.html` wird nicht importiert (bewusst ausgelassen)
