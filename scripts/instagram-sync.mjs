#!/usr/bin/env node
/**
 * Holt das Instagram-Profil und die neuesten Beitraege von volksimmobilien
 * und legt sie als statischen Snapshot im Projekt ab.
 *
 * Warum ein Snapshot und kein Live-Abruf im Browser?
 * Die Seite liegt in einem oeffentlichen Repository. Ein Access Token im
 * Frontend waere damit fuer jeden lesbar. Ausserdem laufen die Bild-URLs von
 * Instagram nach kurzer Zeit ab. Deshalb werden Bilder und Videos einmal
 * heruntergeladen und lokal ausgeliefert: kein Token in der Seite, keine
 * toten Vorschaubilder, keine Ladezeit ueber fremde CDNs.
 *
 * Aufruf:
 *   IG_TOKEN=<token> node scripts/instagram-sync.mjs
 * oder Token in scripts/.instagram-token ablegen (wird nicht eingecheckt):
 *   node scripts/instagram-sync.mjs
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync, statSync, rmSync, renameSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const SCRIPT_DIR = dirname(fileURLToPath(import.meta.url));
const ROOT = resolve(SCRIPT_DIR, '..');
const MEDIA_DIR = join(ROOT, 'Fotos', 'instagram');
const DATA_FILE = join(ROOT, 'js', 'instagram-feed.json');

/** So viele Beitraege landen im Snapshot. */
const POST_LIMIT = 12;
/** Videos, die auch nach dem Verkleinern darueber liegen, bleiben aussen vor. */
const MAX_VIDEO_BYTES = 2.8 * 1024 * 1024;
const API = 'https://graph.instagram.com/v21.0';

function getToken() {
  if (process.env.IG_TOKEN) return process.env.IG_TOKEN.trim();
  const tokenFile = join(SCRIPT_DIR, '.instagram-token');
  if (existsSync(tokenFile)) return readFileSync(tokenFile, 'utf8').trim();
  console.error(
    'Kein Token gefunden.\n' +
      'Entweder IG_TOKEN als Umgebungsvariable setzen oder den Token in\n' +
      'scripts/.instagram-token ablegen (die Datei ist von Git ausgenommen).'
  );
  process.exit(1);
}

async function api(path, params, token) {
  const url = new URL(`${API}/${path}`);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, value);
  url.searchParams.set('access_token', token);
  const res = await fetch(url);
  const json = await res.json();
  if (!res.ok || json.error) {
    // Der Token darf nie in eine Fehlermeldung geraten.
    throw new Error(`Instagram-API: ${json.error?.message ?? res.status}`);
  }
  return json;
}

async function download(url, targetPath) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Download fehlgeschlagen (${res.status})`);
  const buffer = Buffer.from(await res.arrayBuffer());
  writeFileSync(targetPath, buffer);
  return buffer.length;
}

/** Wandelt ein JPEG in WebP um, passend zum Rest der Seite. Faellt still zurueck. */
function toWebp(jpegPath, webpPath) {
  const code = [
    'from PIL import Image',
    'import sys',
    'img = Image.open(sys.argv[1]).convert("RGB")',
    'img.thumbnail((1080, 1080), Image.LANCZOS)',
    'img.save(sys.argv[2], "WEBP", quality=82, method=6)',
  ].join('\n');
  for (const python of ['python', 'py', 'python3']) {
    try {
      execFileSync(python, ['-c', code, jpegPath, webpPath], { stdio: 'pipe' });
      return true;
    } catch {
      /* naechsten Interpreter versuchen */
    }
  }
  return false;
}

/**
 * Rechnet ein Reel auf Web-Groesse herunter. Die Originale von Instagram sind
 * mit 2 bis 4 MB zu schwer fuer eine Startseite. Ton bleibt erhalten, damit das
 * Video in der Lightbox vollwertig ist.
 */
function compressVideo(sourcePath, targetPath) {
  try {
    execFileSync(
      'ffmpeg',
      [
        '-y', '-loglevel', 'error',
        '-i', sourcePath,
        '-vf', "scale='min(720,iw)':-2",
        '-c:v', 'libx264', '-crf', '30', '-preset', 'slow', '-profile:v', 'main',
        '-c:a', 'aac', '-b:a', '96k',
        '-movflags', '+faststart',
        targetPath,
      ],
      { stdio: 'pipe' }
    );
    return true;
  } catch {
    return false;
  }
}

/** Kuerzt eine Caption auf den ersten Sinnabschnitt, ohne Hashtag-Block. */
function cleanCaption(caption) {
  if (!caption) return '';
  const withoutTrailer = caption.split('\n__')[0].split('\n#')[0];
  return withoutTrailer.replace(/\s*\n\s*/g, ' ').trim();
}

function hashtagsOf(caption) {
  if (!caption) return [];
  return [...new Set(caption.match(/#[\wäöüÄÖÜß]+/g) ?? [])].slice(0, 6);
}

async function main() {
  const token = getToken();
  mkdirSync(MEDIA_DIR, { recursive: true });
  mkdirSync(dirname(DATA_FILE), { recursive: true });

  console.log('Profil abrufen ...');
  const profile = await api(
    'me',
    {
      fields:
        'id,username,name,biography,followers_count,follows_count,media_count,profile_picture_url,website',
    },
    token
  );

  console.log('Beitraege abrufen ...');
  const media = await api(
    'me/media',
    {
      fields:
        'id,caption,media_type,media_product_type,media_url,permalink,thumbnail_url,timestamp,like_count,comments_count',
      limit: String(POST_LIMIT),
    },
    token
  );

  console.log('Profilbild sichern ...');
  const avatarJpg = join(MEDIA_DIR, 'profil.jpg');
  const avatarWebp = join(MEDIA_DIR, 'profil.webp');
  await download(profile.profile_picture_url, avatarJpg);
  const avatarConverted = toWebp(avatarJpg, avatarWebp);
  if (avatarConverted) rmSync(avatarJpg, { force: true });

  const posts = [];
  for (const [index, item] of media.data.entries()) {
    const slug = `post-${String(index + 1).padStart(2, '0')}`;
    const posterUrl = item.media_type === 'VIDEO' ? item.thumbnail_url : item.media_url;
    if (!posterUrl) {
      console.warn(`  ${slug}: kein Vorschaubild, wird uebersprungen`);
      continue;
    }

    process.stdout.write(`  ${slug} (${item.media_type}) ... `);
    const jpgPath = join(MEDIA_DIR, `${slug}.jpg`);
    const webpPath = join(MEDIA_DIR, `${slug}.webp`);
    await download(posterUrl, jpgPath);
    const converted = toWebp(jpgPath, webpPath);
    if (converted) rmSync(jpgPath, { force: true });

    const post = {
      id: item.id,
      permalink: item.permalink,
      type: item.media_type,
      productType: item.media_product_type ?? 'FEED',
      poster: `Fotos/instagram/${slug}.${converted ? 'webp' : 'jpg'}`,
      caption: cleanCaption(item.caption),
      hashtags: hashtagsOf(item.caption),
      timestamp: item.timestamp,
      likes: item.like_count ?? 0,
      comments: item.comments_count ?? 0,
    };

    // Reels werden lokal gespiegelt, damit die Vorschau nicht von ablaufenden
    // Instagram-URLs abhaengt. Zu grosse Dateien bleiben aussen vor.
    if (item.media_type === 'VIDEO' && item.media_url) {
      const videoPath = join(MEDIA_DIR, `${slug}.mp4`);
      const rawPath = join(MEDIA_DIR, `${slug}.raw.mp4`);
      try {
        await download(item.media_url, rawPath);
        if (compressVideo(rawPath, videoPath)) {
          rmSync(rawPath, { force: true });
        } else {
          // Ohne ffmpeg lieber das Original als gar nichts.
          renameSync(rawPath, videoPath);
        }
        const bytes = statSync(videoPath).size;
        if (bytes > MAX_VIDEO_BYTES) {
          rmSync(videoPath, { force: true });
          process.stdout.write(`Video zu gross (${(bytes / 1024 / 1024).toFixed(1)} MB), ausgelassen `);
        } else {
          post.video = `Fotos/instagram/${slug}.mp4`;
          process.stdout.write(`Video ${(bytes / 1024).toFixed(0)} KB `);
        }
      } catch (error) {
        rmSync(rawPath, { force: true });
        console.warn(`Video nicht geladen: ${error.message}`);
      }
    }

    posts.push(post);
    console.log('ok');
  }

  const snapshot = {
    generatedAt: new Date().toISOString(),
    profile: {
      username: profile.username,
      name: profile.name ?? profile.username,
      biography: profile.biography ?? '',
      followers: profile.followers_count ?? 0,
      posts: profile.media_count ?? posts.length,
      avatar: `Fotos/instagram/profil.${avatarConverted ? 'webp' : 'jpg'}`,
      permalink: `https://www.instagram.com/${profile.username}/`,
    },
    posts,
  };

  writeFileSync(DATA_FILE, `${JSON.stringify(snapshot, null, 2)}\n`, 'utf8');

  const totalBytes = posts.reduce((sum, post) => {
    let bytes = statSync(join(ROOT, post.poster)).size;
    if (post.video) bytes += statSync(join(ROOT, post.video)).size;
    return sum + bytes;
  }, 0);

  console.log(
    `\nFertig: ${posts.length} Beitraege, ${(totalBytes / 1024 / 1024).toFixed(1)} MB Medien.\n` +
      `Snapshot: js/instagram-feed.json`
  );
}

main().catch((error) => {
  console.error(`\nAbbruch: ${error.message}`);
  process.exit(1);
});
