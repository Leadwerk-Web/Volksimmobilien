#!/usr/bin/env python3
"""Prepare Volksimmobilien HTML for the GTD static-source contract."""
from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path("/Users/atlas/Documents/Github/Volksimmobilien")
GTD = Path("/Users/atlas/Documents/Github/Netzwerft/Global Theme Distribution/projects/volksimmobilien")

PAGES = [
    "index.html",
    "bewerten.html",
    "kaufen.html",
    "verkaufen.html",
    "ausland.html",
    "mallorca.html",
    "immobilienmakler-durmersheim.html",
    "immobilienmakler-ettlingen.html",
    "immobilienmakler-gaggenau.html",
    "immobilienmakler-malsch.html",
    "immobilienmakler-rastatt.html",
    "immobilienmakler-rheinstetten.html",
    "immobilienmakler-au-am-rhein.html",
    "gewerbeimmobilien-verkaufen.html",
    "mehrfamilienhaus-verkaufen.html",
    "verkaufs-checkliste.html",
    "impressum.html",
    "datenschutz.html",
    "danke.html",
    "404.html",
]

FOCUS = {
    "index.html": "Immobilienmakler Heidelberg Baden-Baden",
    "bewerten.html": "Immobilie bewerten Karlsruhe",
    "kaufen.html": "Immobilie kaufen Karlsruhe",
    "verkaufen.html": "Immobilie verkaufen Karlsruhe",
    "ausland.html": "Immobilien Ausland Mallorca Kroatien",
    "mallorca.html": "Immobilien Mallorca",
    "immobilienmakler-durmersheim.html": "Immobilienmakler Durmersheim",
    "immobilienmakler-ettlingen.html": "Immobilienmakler Ettlingen",
    "immobilienmakler-gaggenau.html": "Immobilienmakler Gaggenau",
    "immobilienmakler-malsch.html": "Immobilienmakler Malsch",
    "immobilienmakler-rastatt.html": "Immobilienmakler Rastatt",
    "immobilienmakler-rheinstetten.html": "Immobilienmakler Rheinstetten",
    "immobilienmakler-au-am-rhein.html": "Immobilienmakler Au am Rhein",
    "gewerbeimmobilien-verkaufen.html": "Gewerbeimmobilie verkaufen Karlsruhe",
    "mehrfamilienhaus-verkaufen.html": "Mehrfamilienhaus verkaufen Karlsruhe",
    "verkaufs-checkliste.html": "Immobilie verkaufen Checkliste",
    "impressum.html": "volksimmobilien Impressum",
    "datenschutz.html": "volksimmobilien Datenschutz",
    "danke.html": "volksimmobilien Nachricht erhalten",
    "404.html": "volksimmobilien Seite nicht gefunden",
}

GOOGLE_FONTS = re.compile(
    r"[ \t]*<link rel=\"preconnect\" href=\"https://fonts\.googleapis\.com\">\s*"
    r"<link rel=\"preconnect\" href=\"https://fonts\.gstatic\.com\" crossorigin>\s*"
    r"<link href=\"https://fonts\.googleapis\.com/css2\?[^\"]+\" rel=\"stylesheet\">\s*",
    re.I,
)
CANONICAL = re.compile(r"[ \t]*<link rel=\"canonical\" href=\"https://volks\.immobilien[^\"]*\">\s*", re.I)
OG_URL = re.compile(r"[ \t]*<meta property=\"og:url\" content=\"https://volks\.immobilien[^\"]*\">\s*", re.I)
HOST_MEDIA = re.compile(r"https://volks\.immobilien/(Fotos/[^\"']+|assets/[^\"']+|Video/[^\"']+|downloads/[^\"']+)")
FAVICON_BLOCK = re.compile(
    r"(?:[ \t]*<link rel=\"(?:icon|shortcut icon)\"[^>]*href=\"Fotos/Volksimmobilien%20-%20favicon\.png\">\s*)+",
    re.I,
)
STYLE_LINK = re.compile(
    r'<link rel="stylesheet" href="css/style\.css(?:\?[^"]*)?">',
    re.I,
)
SCRIPT_CACHE = re.compile(r'(<script src="js/main\.js)(?:\?[^"]*)?("></script>)', re.I)
HAMBURGER = re.compile(r'(<button class="hamburger" id="hamburger")')
EMPTY_ALT = re.compile(
    r"<img\b(?=[^>]*\balt=\"\")(?![^>]*(?:aria-hidden|data-lw-decorative|role=\"presentation\"|role='presentation'))([^>]*)>",
    re.I,
)
SWITCHER = '<div class="site-language-switcher" data-lw-language-switcher-slot hidden></div>\n      '


def local_media(url: str) -> str:
    path = url.replace("https://volks.immobilien/", "")
    return path.replace("%20", " ")


def mark_decorative(match: re.Match[str]) -> str:
    attrs = match.group(1)
    if "aria-hidden" in attrs.lower():
        return match.group(0)
    return f'<img{attrs} aria-hidden="true">'


def transform(name: str, html: str) -> str:
    html = GOOGLE_FONTS.sub("", html)
    html = CANONICAL.sub("\n", html)
    html = OG_URL.sub("\n", html)
    html = FAVICON_BLOCK.sub(
        '<link rel="icon" type="image/webp" href="assets/favicon.webp">\n',
        html,
        count=1,
    )
    html = HOST_MEDIA.sub(lambda match: local_media(match.group(0)), html)
    html = re.sub(
        r'<meta name="twitter:image" content="https://volks\.immobilien/([^"]+)">',
        lambda m: f'<meta name="twitter:image" content="{local_media("https://volks.immobilien/" + m.group(1))}">',
        html,
    )

    if 'name="leadwerk:focus-keyphrase"' not in html:
        phrase = FOCUS[name]
        html = html.replace(
            '<meta name="viewport" content="width=device-width, initial-scale=1.0">',
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
            f'<meta name="leadwerk:focus-keyphrase" content="{phrase}">',
            1,
        )

    if "css/local-fonts.css" not in html:
        html = STYLE_LINK.sub(
            '<link rel="stylesheet" href="css/local-fonts.css">\n'
            '<link rel="stylesheet" href="css/style.css">\n'
            '<link rel="stylesheet" href="css/wpforms-contact.css">',
            html,
            count=1,
        )

    html = SCRIPT_CACHE.sub(r"\1\2", html)

    if "data-lw-language-switcher-slot" not in html:
        html = HAMBURGER.sub(SWITCHER + r"\1", html, count=1)

    html = html.replace('href="/immobilien/"', 'href="kaufen.html" data-volks-listings="archive"')
    html = html.replace("href='https://volks.immobilien/immobilien/'", 'href="kaufen.html" data-volks-listings="archive"')
    html = html.replace('href="https://volks.immobilien/immobilien/"', 'href="kaufen.html" data-volks-listings="archive"')
    html = html.replace(
        'src="https://www.google.com/maps?q=Deutschland&output=embed&z=6"',
        'src="https://www.google.com/maps?q=Deutschland&output=embed&z=6" data-lw-remote="consent"',
    )
    html = html.replace('rel="noopener"', 'rel="noopener noreferrer"')
    html = html.replace('rel="noopener noreferrer noreferrer"', 'rel="noopener noreferrer"')
    html = EMPTY_ALT.sub(mark_decorative, html)
    return html


def write_project_json() -> None:
    mapping = json.loads((ROOT / "leadwerk_importer/manifest/mapping-volks.json").read_text(encoding="utf-8"))
    pages = mapping["pages"]
    overrides = {}
    for page in pages:
        source = page["source_file"]
        slug = page["slug"]
        key = page["source_key"]
        title = page["title"]
        entry: dict = {
            "sourceKey": key,
            "title": title,
            "status": "publish",
        }
        if source == "index.html":
            entry["route"] = "/"
            entry["template"] = "home"
        elif source == "404.html":
            entry["route"] = "/404/"
            entry["template"] = "404"
            entry["status"] = "system"
            entry["noindex"] = True
        elif source == "danke.html":
            entry["route"] = "/danke/"
            entry["template"] = "special"
            entry["noindex"] = True
        elif source in ("impressum.html", "datenschutz.html"):
            entry["route"] = f"/{slug}/"
            entry["template"] = "legal"
        else:
            entry["route"] = f"/{slug}/"
            entry["template"] = "page"
        entry["seo"] = {"focusKeyphrase": FOCUS[source]}
        overrides[source] = entry

    project = {
        "$schema": "../../schemas/project.schema.json",
        "schemaVersion": 1,
        "project": {
            "id": "volksimmobilien",
            "name": "volksimmobilien",
            "slug": "volksimmobilien",
            "sourceLocale": "de-DE",
            "siteHosts": ["volks.immobilien", "www.volks.immobilien"],
        },
        "source": {
            "root": "../../../../Volksimmobilien",
            "pagePatterns": PAGES,
            "assetRoots": ["Fotos", "css", "js", "fonts", "assets", "Video", "downloads"],
            "pinnedAssets": [],
            "legacyAutoFields": True,
            "ignore": [
                ".DS_Store",
                "**/.DS_Store",
                "index-alternative.html",
                "instagram-vorschau.html",
                "leadwerk_importer/**",
                "volksimmobilien_theme/**",
                "volksimmobilien-propstack/**",
                "scripts/**",
                "**/*.zip",
                "**/*.bak",
                "webp-conversion-*.json",
            ],
            "pageOverrides": overrides,
        },
        "wordpress": {
            "themeSlug": "leadwerk-theme-volksimmobilien",
            "textDomain": "leadwerk-theme-volksimmobilien",
            "minimumWordPress": "6.9",
            "minimumPhp": "8.1",
            "testedWordPress": "7.0",
            "modules": ["fields", "importer", "translations", "migration"],
            "updateUri": "https://updates.leadwerk.de/themes/leadwerk-theme-volksimmobilien",
            "updateManifestUrl": "https://updates.leadwerk.de/v1/updates/volksimmobilien/development",
            "updatePublicKey": "REPLACE_WITH_BASE64_ED25519_PUBLIC_KEY_1234567890",
            "homepage": "https://volks.immobilien/",
            "frontPageSourceKey": "volks-home-v1",
            "notFoundSourceKey": "volks-404-v1",
            "ownedContentAdoption": "preserve",
        },
        "variables": {"file": "project.variables.json"},
        "build": {
            "channel": "development",
            "releaseEnabled": False,
            "releaseBlockReason": "Existing live site: keep field trees, Leadwerk Optionen and WPForms IDs. Do not publish until overlay reuse is verified on staging.",
            "overlayRoot": "overlay",
            "sourceDateEpoch": 315532800,
            "moduleSources": {
                "translations": "../../templates/wordpress-theme-suite/module-sources/translations",
                "migration": "../../templates/wordpress-theme-suite/module-sources/migration",
            },
            "editableContentPolicy": "require",
            "qualityProfile": "production",
            "strict": True,
        },
    }
    dest = GTD / "project.json"
    dest.write_text(json.dumps(project, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("wrote", dest)


def main() -> None:
    for name in PAGES:
        path = ROOT / name
        original = path.read_text(encoding="utf-8")
        updated = transform(name, original)
        if updated != original:
            path.write_text(updated, encoding="utf-8")
            print("updated", name)
        else:
            print("unchanged", name)
    write_project_json()


if __name__ == "__main__":
    main()
