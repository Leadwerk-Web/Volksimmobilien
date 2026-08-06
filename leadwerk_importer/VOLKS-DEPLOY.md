# volksimmobilien – Deployment & Medien

## Quellbaum

| Kanal | Pfad | Verwendung |
|-------|------|------------|
| Entwicklung | Repo-Root (`index.html`, `Fotos/`, `css/`, `js/`) | Bearbeitung der statischen Site |
| Import (lokal) | Repo-Root (Auto-Erkennung) oder `leadwerk_importer/source_assets/` | `Leadwerk_Importer` Media-Scan |
| Frontend CSS/JS | `volksimmobilien_theme/assets/` | WordPress-Theme (Enqueue) |
| Medien live | `wp-content/uploads/` nach Import | Attachment-IDs in importiertem HTML |

## source_assets synchronisieren

Auf dem Server oder vor Deploy:

```bash
cd /pfad/zum/Volksimmobilien
chmod +x leadwerk_importer/scripts/sync-volks-source-assets.sh
./leadwerk_importer/scripts/sync-volks-source-assets.sh
```

Das Skript kopiert HTML, `Fotos/`, `Video/`, `css/`, `js/` nach `leadwerk_importer/source_assets/` (ohne ACM-Dateien).

## WordPress-Plugins

1. `leadwerk-fields` aktivieren (ACF **aus**)
2. `leadwerk_importer` aktivieren
3. `leadwerk-wpml-clone` **nicht** aktivieren (Volks-Manifest: `translations_enabled: false`)
4. Theme `volksimmobilien_theme` aktivieren

## Produktion / Sicherheit

- `leadwerk_importer/source_assets/` nicht öffentlich browsbar machen (nginx/apache deny oder außerhalb webroot)
- Nach erfolgreichem Medien-Import verlässt das Frontend den Plugin-Ordner über Attachment-URLs
- Filter `volks_static_source_base_path` / `volks_static_source_base_url` für Fallbacks

## Manifest

Standard: `manifest/mapping-volks.json` (konstante `LEADWERK_IMPORT_MANIFEST_FILE`).

ACM-Import: `mapping.json` + Filter `leadwerk_import_manifest_file` setzen.
