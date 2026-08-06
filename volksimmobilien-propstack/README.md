# Volksimmobilien Propstack

Read-only-Synchronisierung der in Propstack freigegebenen Immobilien in WordPress. Das Plugin stellt das Archiv `/immobilien/`, serverseitige Filter und die Detailseiten unter `/immobilie/{slug}/` bereit. Objekte im Status `Verkauft` erscheinen ohne Preis und ohne Detailseiten-Link in der Referenzgalerie `/verkauft/`.

## Sicherheitsmodell

- Die API-Klasse kann ausschließlich `GET`-Anfragen ausführen.
- Kontakte, Eigentümer, Beziehungen, interne Notizen, Straßen-/Hausnummern und der rohe API-Datensatz werden nicht gespeichert.
- Private Bilder und Dokumente werden immer verworfen. `remove_from_portal` bleibt für aktive Angebote gesperrt; ausschließlich der explizit konfigurierte Status `Verkauft` darf in die separate Referenzgalerie.
- Preise, Mieten und Courtage-Daten verkaufter Referenzen werden weder ausgegeben noch in WordPress-Meta übernommen.
- Ein API- oder Mappingfehler verändert bestehende Immobilien nicht.
- Fehlende Objekte werden erst nach einem vollständig erfolgreichen Abruf auf `Entwurf` gestellt.
- Deaktivieren des Plugins löscht keine synchronisierten Immobilien und ermöglicht einen schnellen Rollback.

## Einrichtung

1. Das Plugin installieren und aktivieren.
2. Außerhalb des Webroots eine Datei mit Modus `0600` anlegen:

   ```text
   PROPSTACK_API_KEY=...
   ```

3. Unter **Werkzeuge → Propstack Sync** den absoluten Pfad dieser Datei eintragen.
4. Für dieses Propstack-Konto `Vermarktung` als aktiven Status und `Verkauft` als Galeriestatus konfigurieren.
5. Zuerst **Testlauf**, danach **Jetzt synchronisieren** ausführen.
6. Die stündliche Automatik erst nach erfolgreicher Sichtprüfung aktivieren.

Alternativ kann der Dateipfad in `wp-config.php` fest definiert werden:

```php
define( 'VOLKS_PROPSTACK_API_KEY_FILE', '/geschuetzter/pfad/propstack.env' );
```

## WP-CLI

```bash
wp volks-propstack status
wp volks-propstack sync --dry-run
wp volks-propstack sync
```

Der API-Key wird weder in der WordPress-Datenbank noch in Ausgaben oder Fehlerlogs gespeichert.
