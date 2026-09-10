/**
 * Instagram-Feed fuer volksimmobilien.
 *
 * Liest den Snapshot aus js/instagram-feed.json (erzeugt von
 * scripts/instagram-sync.mjs) und baut daraus Profilkopf, Raster und
 * Lightbox. Es wird kein Access Token benoetigt und nichts von Instagram
 * nachgeladen: alle Medien liegen im Projekt.
 */
(function () {
  'use strict';

  var FEED_URL = 'js/instagram-feed.json';

  var section = document.querySelector('[data-ig-feed]');
  if (!section) return;

  var grid = section.querySelector('[data-ig-grid]');
  var profileSlot = section.querySelector('[data-ig-profile]');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var posts = [];
  var profile = null;
  var lightbox = null;
  var currentIndex = -1;
  var lastFocused = null;

  /* ---------- Hilfen ---------- */

  function el(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text != null) node.textContent = text;
    return node;
  }

  function formatCount(value) {
    if (value >= 1000) {
      return (value / 1000).toFixed(value % 1000 >= 100 ? 1 : 0).replace('.', ',') + 'k';
    }
    return String(value);
  }

  function formatDate(iso) {
    var date = new Date(iso);
    if (isNaN(date)) return '';
    return date.toLocaleDateString('de-DE', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function icon(paths, size) {
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', size || 16);
    svg.setAttribute('height', size || 16);
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    paths.forEach(function (d) {
      var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', d);
      svg.appendChild(path);
    });
    return svg;
  }

  var ICONS = {
    heart: ['M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l8.8 8.8 8.8-8.8a5.5 5.5 0 000-7.8z'],
    comment: ['M21 11.5a8.4 8.4 0 01-9 8.4 8.9 8.9 0 01-4-.9L3 21l1.9-5a8.4 8.4 0 01-.9-4 8.4 8.4 0 018.4-8.4h.6A8.4 8.4 0 0121 11z'],
    play: ['M5 3l14 9-14 9V3z'],
    layers: ['M12 2L2 7l10 5 10-5-10-5z', 'M2 17l10 5 10-5', 'M2 12l10 5 10-5'],
    close: ['M18 6L6 18', 'M6 6l12 12'],
    prev: ['M15 18l-6-6 6-6'],
    next: ['M9 18l6-6-6-6'],
    external: ['M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6', 'M15 3h6v6', 'M10 14L21 3'],
  };

  /* ---------- Profilkopf ---------- */

  function renderProfile() {
    var card = el('div', 'ig-profile');

    var ring = el('div', 'ig-profile__avatar-ring');
    var avatar = el('img', 'ig-profile__avatar');
    avatar.src = profile.avatar;
    avatar.alt = 'Profilbild von ' + profile.name;
    avatar.width = 84;
    avatar.height = 84;
    avatar.loading = 'lazy';
    ring.appendChild(avatar);

    var body = el('div', 'ig-profile__body');

    var handle = el('a', 'ig-profile__handle');
    handle.href = profile.permalink;
    handle.target = '_blank';
    handle.rel = 'noopener noreferrer';
    handle.appendChild(document.createTextNode('@' + profile.username));
    var check = icon(['M22 11.08V12a10 10 0 11-5.93-9.14', 'M22 4L12 14.01l-3-3'], 17);
    check.setAttribute('class', 'ig-profile__verified');
    handle.appendChild(check);
    body.appendChild(handle);

    if (profile.biography) {
      body.appendChild(el('p', 'ig-profile__bio', profile.biography));
    }

    var stats = el('ul', 'ig-profile__stats');
    [
      { value: formatCount(profile.posts), label: 'Beiträge' },
      { value: formatCount(profile.followers), label: 'Follower' },
    ].forEach(function (entry) {
      var li = document.createElement('li');
      li.appendChild(el('span', 'ig-profile__stat-value', entry.value));
      li.appendChild(el('span', 'ig-profile__stat-label', entry.label));
      stats.appendChild(li);
    });
    body.appendChild(stats);

    var action = el('div', 'ig-profile__action');
    var follow = el('a', 'btn btn-primary', 'Auf Instagram folgen');
    follow.href = profile.permalink;
    follow.target = '_blank';
    follow.rel = 'noopener noreferrer';
    action.appendChild(follow);

    card.appendChild(ring);
    card.appendChild(body);
    card.appendChild(action);
    profileSlot.appendChild(card);
  }

  /* ---------- Raster ---------- */

  function renderTile(post, index) {
    var item = document.createElement('li');

    var tile = el('button', 'ig-tile');
    tile.type = 'button';
    tile.style.animationDelay = reduceMotion ? '0ms' : index * 45 + 'ms';

    var label = post.caption
      ? post.caption.slice(0, 90)
      : 'Instagram-Beitrag von volksimmobilien';
    tile.setAttribute('aria-label', label + ' – Beitrag öffnen');

    var image = el('img', 'ig-tile__media');
    image.src = post.poster;
    image.alt = post.caption || 'Instagram-Beitrag von volksimmobilien';
    image.loading = 'lazy';
    image.decoding = 'async';
    tile.appendChild(image);

    // Reels bekommen eine stumme Vorschau, die erst beim Hover geladen wird.
    if (post.video && !reduceMotion) {
      var video = el('video', 'ig-tile__media ig-tile__video');
      video.muted = true;
      video.loop = true;
      video.playsInline = true;
      video.preload = 'none';
      video.setAttribute('aria-hidden', 'true');
      video.setAttribute('tabindex', '-1');
      video.poster = post.poster;
      video.dataset.src = post.video;
      tile.appendChild(video);
      attachHoverPreview(tile, video);
    }

    if (post.type === 'VIDEO' || post.type === 'CAROUSEL_ALBUM') {
      var badge = el('span', 'ig-tile__badge');
      badge.appendChild(icon(post.type === 'VIDEO' ? ICONS.play : ICONS.layers, 14));
      if (post.type === 'VIDEO') {
        badge.querySelector('path').setAttribute('fill', 'currentColor');
      }
      tile.appendChild(badge);
    }

    var overlay = el('div', 'ig-tile__overlay');
    if (post.caption) {
      overlay.appendChild(el('p', 'ig-tile__caption', post.caption));
    }
    var meta = el('div', 'ig-tile__meta');
    var likes = document.createElement('span');
    var heart = icon(ICONS.heart, 14);
    heart.setAttribute('fill', 'currentColor');
    likes.appendChild(heart);
    likes.appendChild(document.createTextNode(formatCount(post.likes)));
    meta.appendChild(likes);

    var comments = document.createElement('span');
    comments.appendChild(icon(ICONS.comment, 14));
    comments.appendChild(document.createTextNode(formatCount(post.comments)));
    meta.appendChild(comments);
    overlay.appendChild(meta);
    tile.appendChild(overlay);

    tile.addEventListener('click', function () {
      openLightbox(index);
    });

    item.appendChild(tile);
    return item;
  }

  /**
   * Laedt das Reel beim ersten Hover nach und spielt es stumm ab.
   * Beim Verlassen wird zurueckgespult, damit die Vorschau immer vorne startet.
   */
  function attachHoverPreview(tile, video) {
    var loaded = false;

    function start() {
      if (!loaded) {
        video.src = video.dataset.src;
        loaded = true;
      }
      tile.classList.add('is-playing');
      var attempt = video.play();
      if (attempt && typeof attempt.catch === 'function') {
        // Bricht der Browser das Abspielen ab, bleibt einfach das Standbild.
        attempt.catch(function () {
          tile.classList.remove('is-playing');
        });
      }
    }

    function stop() {
      tile.classList.remove('is-playing');
      video.pause();
      video.currentTime = 0;
    }

    tile.addEventListener('mouseenter', start);
    tile.addEventListener('mouseleave', stop);
    tile.addEventListener('focus', start);
    tile.addEventListener('blur', stop);
  }

  /* ---------- Lightbox ---------- */

  function buildLightbox() {
    var root = el('div', 'ig-lightbox');
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'Instagram-Beitrag');
    root.hidden = true;

    var dialog = el('div', 'ig-lightbox__dialog');

    var stage = el('div', 'ig-lightbox__stage');
    stage.setAttribute('data-ig-stage', '');

    var aside = el('div', 'ig-lightbox__aside');
    aside.innerHTML =
      '<div class="ig-lightbox__head">' +
      '<img class="ig-lightbox__avatar" alt="" width="42" height="42">' +
      '<span><a class="ig-lightbox__handle" target="_blank" rel="noopener noreferrer"></a>' +
      '<time class="ig-lightbox__date"></time></span>' +
      '</div>' +
      '<p class="ig-lightbox__caption"></p>' +
      '<div class="ig-lightbox__tags"></div>' +
      '<div class="ig-lightbox__meta"></div>' +
      '<a class="btn btn-primary btn-sm ig-lightbox__link" target="_blank" rel="noopener noreferrer">Auf Instagram ansehen</a>';

    var close = el('button', 'ig-lightbox__close');
    close.type = 'button';
    close.setAttribute('aria-label', 'Schließen');
    close.appendChild(icon(ICONS.close, 20));
    close.addEventListener('click', closeLightbox);

    var prev = el('button', 'ig-lightbox__nav ig-lightbox__nav--prev');
    prev.type = 'button';
    prev.setAttribute('aria-label', 'Vorheriger Beitrag');
    prev.appendChild(icon(ICONS.prev, 20));
    prev.addEventListener('click', function () { step(-1); });

    var next = el('button', 'ig-lightbox__nav ig-lightbox__nav--next');
    next.type = 'button';
    next.setAttribute('aria-label', 'Nächster Beitrag');
    next.appendChild(icon(ICONS.next, 20));
    next.addEventListener('click', function () { step(1); });

    dialog.appendChild(stage);
    dialog.appendChild(aside);
    dialog.appendChild(close);
    dialog.appendChild(prev);
    dialog.appendChild(next);
    root.appendChild(dialog);

    root.addEventListener('click', function (event) {
      if (event.target === root) closeLightbox();
    });

    document.body.appendChild(root);
    return root;
  }

  function fillLightbox(post) {
    var stage = lightbox.querySelector('[data-ig-stage]');
    stage.innerHTML = '';

    if (post.video) {
      var video = document.createElement('video');
      video.src = post.video;
      video.poster = post.poster;
      video.controls = true;
      video.autoplay = true;
      video.loop = true;
      video.playsInline = true;
      // Ohne stumm blockieren Browser den Autostart.
      video.muted = true;
      stage.appendChild(video);
      var attempt = video.play();
      if (attempt && typeof attempt.catch === 'function') attempt.catch(function () {});
    } else {
      var image = document.createElement('img');
      image.src = post.poster;
      image.alt = post.caption || 'Instagram-Beitrag von volksimmobilien';
      stage.appendChild(image);
    }

    lightbox.querySelector('.ig-lightbox__avatar').src = profile.avatar;
    var handle = lightbox.querySelector('.ig-lightbox__handle');
    handle.textContent = '@' + profile.username;
    handle.href = profile.permalink;

    var time = lightbox.querySelector('.ig-lightbox__date');
    time.textContent = formatDate(post.timestamp);
    time.dateTime = post.timestamp;

    lightbox.querySelector('.ig-lightbox__caption').textContent = post.caption || '';

    var tags = lightbox.querySelector('.ig-lightbox__tags');
    tags.innerHTML = '';
    (post.hashtags || []).forEach(function (tag) {
      tags.appendChild(el('span', 'ig-lightbox__tag', tag));
    });

    var meta = lightbox.querySelector('.ig-lightbox__meta');
    meta.innerHTML = '';
    var likes = document.createElement('span');
    var heart = icon(ICONS.heart, 16);
    heart.setAttribute('fill', 'currentColor');
    likes.appendChild(heart);
    likes.appendChild(document.createTextNode(formatCount(post.likes) + ' Likes'));
    meta.appendChild(likes);
    var comments = document.createElement('span');
    comments.appendChild(icon(ICONS.comment, 16));
    comments.appendChild(document.createTextNode(formatCount(post.comments) + ' Kommentare'));
    meta.appendChild(comments);

    var link = lightbox.querySelector('.ig-lightbox__link');
    link.href = post.permalink;
    link.innerHTML = '';
    link.appendChild(document.createTextNode('Auf Instagram ansehen'));
    link.appendChild(icon(ICONS.external, 15));
  }

  function openLightbox(index) {
    if (!lightbox) lightbox = buildLightbox();
    lastFocused = document.activeElement;
    currentIndex = index;
    fillLightbox(posts[index]);

    lightbox.hidden = false;
    document.body.classList.add('ig-lightbox-open');
    // Reflow erzwingen, damit der Uebergang greift. Bewusst kein
    // requestAnimationFrame: in einem Hintergrund-Tab laeuft der Rueckruf
    // nicht, die Lightbox bliebe unsichtbar und die Seite gesperrt.
    void lightbox.offsetWidth;
    lightbox.classList.add('is-open');
    lightbox.querySelector('.ig-lightbox__close').focus();

    document.addEventListener('keydown', onKeydown);
  }

  function closeLightbox() {
    if (!lightbox) return;
    lightbox.classList.remove('is-open');
    document.body.classList.remove('ig-lightbox-open');
    document.removeEventListener('keydown', onKeydown);

    var video = lightbox.querySelector('video');
    if (video) video.pause();

    window.setTimeout(function () {
      lightbox.hidden = true;
      lightbox.querySelector('[data-ig-stage]').innerHTML = '';
    }, 300);

    if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
  }

  function step(direction) {
    var next = (currentIndex + direction + posts.length) % posts.length;
    currentIndex = next;
    fillLightbox(posts[next]);
  }

  function onKeydown(event) {
    if (event.key === 'Escape') {
      closeLightbox();
    } else if (event.key === 'ArrowLeft') {
      step(-1);
    } else if (event.key === 'ArrowRight') {
      step(1);
    } else if (event.key === 'Tab') {
      trapFocus(event);
    }
  }

  /** Haelt den Tastaturfokus innerhalb der Lightbox. */
  function trapFocus(event) {
    var focusable = lightbox.querySelectorAll('button, a[href], video[controls]');
    if (!focusable.length) return;
    var first = focusable[0];
    var last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  /* ---------- Wischgesten ---------- */

  function attachSwipe() {
    var startX = 0;
    document.addEventListener('touchstart', function (event) {
      if (!lightbox || lightbox.hidden) return;
      startX = event.changedTouches[0].clientX;
    }, { passive: true });

    document.addEventListener('touchend', function (event) {
      if (!lightbox || lightbox.hidden) return;
      var delta = event.changedTouches[0].clientX - startX;
      if (Math.abs(delta) > 60) step(delta < 0 ? 1 : -1);
    }, { passive: true });
  }

  /* ---------- Start ---------- */

  fetch(FEED_URL, { cache: 'no-cache' })
    .then(function (response) {
      if (!response.ok) throw new Error('Feed nicht erreichbar (' + response.status + ')');
      return response.json();
    })
    .then(function (data) {
      profile = data.profile;
      posts = data.posts || [];
      if (!posts.length) throw new Error('Feed ist leer');

      renderProfile();
      var fragment = document.createDocumentFragment();
      posts.forEach(function (post, index) {
        fragment.appendChild(renderTile(post, index));
      });
      grid.appendChild(fragment);
      grid.classList.add('is-ready');
      attachSwipe();
    })
    .catch(function (error) {
      // Lieber gar keine Sektion als eine kaputte.
      section.hidden = true;
      if (window.console && console.warn) {
        console.warn('Instagram-Feed konnte nicht geladen werden:', error.message);
      }
    });
})();
