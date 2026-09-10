# Instagram-Feed

Zeigt die zwölf neuesten Beiträge von [@volksimmobilien](https://www.instagram.com/volksimmobilien/)
am Seitenende: Profilkopf, Raster, Reel-Vorschau beim Überfahren und eine Lightbox mit Video und Text.

Zu sehen unter [instagram-vorschau.html](instagram-vorschau.html). Die Startseite ist bewusst
unverändert, der Feed wird erst nach Freigabe dorthin übernommen.

## Warum ein Snapshot statt eines Live-Abrufs

Dieses Repository ist öffentlich und die Seite wird statisch ausgeliefert. Ein Access Token im
Frontend wäre damit für jeden lesbar, der die Seite aufruft. Deshalb werden Profil, Texte, Bilder
und Reels einmal abgerufen und im Projekt abgelegt. Das hat drei Vorteile:

- Kein Token in der ausgelieferten Seite.
- Die Bild-URLs von Instagram laufen nach kurzer Zeit ab, lokale Dateien nicht. Ohne Spiegelung
  wären die Vorschaubilder nach ein paar Tagen tot.
- Keine Ladezeit über fremde CDNs, die Medien liegen bei den übrigen Bildern der Seite.

Der Preis: Neue Beiträge erscheinen nicht von allein. Der Feed wird durch einen Lauf des
Sync-Skripts aktualisiert.

## Feed aktualisieren

```bash
IG_TOKEN=<token> node scripts/instagram-sync.mjs
```

Alternativ den Token in `scripts/.instagram-token` ablegen, dann genügt
`node scripts/instagram-sync.mjs`. Die Datei ist über `.gitignore` von Git ausgenommen und darf
nicht eingecheckt werden.

Das Skript schreibt `js/instagram-feed.json` und die Medien nach `Fotos/instagram/`. Beides
danach committen. Für die Bildumwandlung nach WebP wird Python mit Pillow genutzt, für das
Verkleinern der Reels ffmpeg. Fehlt eines von beiden, fällt das Skript auf das Original zurück.

## Token

Der Access Token stammt von khexklusiv Media (Felix Schindele), die das Instagram-Profil betreuen.
Er liegt **nicht** im Repository, der Ablageort steht in `zugaenge.md` im Kundenordner.

Instagram-Token dieser Art laufen nach 60 Tagen ab. Läuft der Sync mit einer Meldung der
Instagram-API auf einen Fehler, ist in aller Regel der Token abgelaufen und muss bei khmedia neu
erzeugt werden. Der bestehende Feed bleibt davon unberührt, er liegt ja im Projekt.

## Dateien

| Datei | Zweck |
| --- | --- |
| `scripts/instagram-sync.mjs` | Holt Profil und Beiträge, spiegelt die Medien, schreibt den Snapshot |
| `js/instagram-feed.json` | Der Snapshot, aus dem die Seite baut |
| `js/instagram-feed.js` | Baut Profilkopf, Raster und Lightbox |
| `css/instagram.css` | Gestaltung, nutzt die Design-Tokens aus `style.css` |
| `Fotos/instagram/` | Gespiegelte Bilder und Reels |

## Einbau auf einer Seite

```html
<link rel="stylesheet" href="css/instagram.css">
...
<section class="section ig-section" id="instagram" data-ig-feed aria-labelledby="instagram-heading">
  <div class="container">
    <div class="section-header reveal">
      <span class="section-eyebrow">Instagram</span>
      <h2 class="section-title" id="instagram-heading">Einblicke zwischen den Terminen</h2>
    </div>
    <div class="reveal" data-ig-profile></div>
    <ul class="ig-grid reveal" data-ig-grid></ul>
  </div>
</section>
...
<script src="js/instagram-feed.js" defer></script>
```

Lässt sich der Snapshot nicht laden, blendet das Skript die Sektion aus, statt eine leere Fläche
stehen zu lassen.
