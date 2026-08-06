(function () {
  'use strict';

  /* Property templates have no #hero for the theme observer to watch. */
  var mobileStickyBar = document.getElementById('mobileStickyBar');
  if (mobileStickyBar) {
    mobileStickyBar.classList.add('visible');
  }

  document.querySelectorAll('[data-vps-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('[data-vps-gallery-main]');
    if (!main) return;

    var inlineButtons = Array.prototype.slice.call(gallery.querySelectorAll('[data-vps-image]'));
    var lightbox = gallery.querySelector('[data-vps-lightbox]');
    var openButton = gallery.querySelector('[data-vps-lightbox-open]');
    var lightboxImage = lightbox ? lightbox.querySelector('[data-vps-lightbox-image]') : null;
    var lightboxCounter = lightbox ? lightbox.querySelector('[data-vps-lightbox-counter]') : null;
    var lightboxClose = lightbox ? lightbox.querySelector('[data-vps-lightbox-close]') : null;
    var lightboxPrev = lightbox ? lightbox.querySelector('[data-vps-lightbox-prev]') : null;
    var lightboxNext = lightbox ? lightbox.querySelector('[data-vps-lightbox-next]') : null;
    var lightboxStage = lightbox ? lightbox.querySelector('[data-vps-lightbox-stage]') : null;
    var lightboxButtons = lightbox
      ? Array.prototype.slice.call(lightbox.querySelectorAll('[data-vps-lightbox-source]'))
      : [];
    var currentIndex = 0;
    var touchStartX = null;

    function normalizedIndex(index) {
      if (!lightboxButtons.length) return 0;
      return (index + lightboxButtons.length) % lightboxButtons.length;
    }

    function updateInline(index) {
      if (!lightboxButtons.length) return;
      currentIndex = normalizedIndex(index);
      var source = lightboxButtons[currentIndex].getAttribute('data-vps-gallery-source');
      if (source) main.src = source;

      inlineButtons.forEach(function (item) {
        var isActive = Number(item.getAttribute('data-vps-index')) === currentIndex;
        item.classList.toggle('is-active', isActive);
        if (isActive) {
          item.setAttribute('aria-current', 'true');
        } else {
          item.removeAttribute('aria-current');
        }
      });
    }

    function renderLightbox(index) {
      if (!lightboxImage || !lightboxButtons.length) return;
      currentIndex = normalizedIndex(index);
      var active = lightboxButtons[currentIndex];
      var source = active.getAttribute('data-vps-lightbox-source');
      if (source) {
        lightboxImage.src = source;
        lightboxImage.alt = main.alt + ' – Bild ' + (currentIndex + 1);
      }
      if (lightboxCounter) {
        lightboxCounter.textContent = 'Bild ' + (currentIndex + 1) + ' von ' + lightboxButtons.length;
      }
      lightboxButtons.forEach(function (item, itemIndex) {
        var isActive = itemIndex === currentIndex;
        item.classList.toggle('is-active', isActive);
        if (isActive) {
          item.setAttribute('aria-current', 'true');
          item.scrollIntoView({ block: 'nearest', inline: 'center' });
        } else {
          item.removeAttribute('aria-current');
        }
      });
    }

    function openLightbox() {
      if (!lightbox || !lightboxButtons.length) return;
      renderLightbox(currentIndex);
      document.body.classList.add('vps-lightbox-open');
      if (typeof lightbox.showModal === 'function') {
        lightbox.showModal();
      } else {
        lightbox.setAttribute('open', '');
      }
      if (lightboxClose) lightboxClose.focus();
    }

    function closeLightbox() {
      if (!lightbox) return;
      if (typeof lightbox.close === 'function' && lightbox.open) {
        lightbox.close();
      } else {
        lightbox.removeAttribute('open');
        document.body.classList.remove('vps-lightbox-open');
        updateInline(currentIndex);
        if (openButton) openButton.focus();
      }
    }

    inlineButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var url = button.getAttribute('data-vps-image');
        if (!url) return;
        currentIndex = Number(button.getAttribute('data-vps-index')) || 0;
        updateInline(currentIndex);
      });
    });

    if (!lightbox || !openButton || !lightboxImage || !lightboxButtons.length) return;

    openButton.addEventListener('click', openLightbox);
    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
    if (lightboxPrev) lightboxPrev.addEventListener('click', function () { renderLightbox(currentIndex - 1); });
    if (lightboxNext) lightboxNext.addEventListener('click', function () { renderLightbox(currentIndex + 1); });

    lightboxButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        renderLightbox(Number(button.getAttribute('data-vps-lightbox-index')) || 0);
      });
    });

    lightbox.addEventListener('cancel', function (event) {
      event.preventDefault();
      closeLightbox();
    });

    lightbox.addEventListener('close', function () {
      document.body.classList.remove('vps-lightbox-open');
      updateInline(currentIndex);
      openButton.focus();
    });

    lightbox.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        renderLightbox(currentIndex - 1);
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        renderLightbox(currentIndex + 1);
      } else if (event.key === 'Home') {
        event.preventDefault();
        renderLightbox(0);
      } else if (event.key === 'End') {
        event.preventDefault();
        renderLightbox(lightboxButtons.length - 1);
      }
    });

    if (lightboxStage) {
      lightboxStage.addEventListener('touchstart', function (event) {
        touchStartX = event.changedTouches.length ? event.changedTouches[0].clientX : null;
      }, { passive: true });
      lightboxStage.addEventListener('touchend', function (event) {
        if (touchStartX === null || !event.changedTouches.length) return;
        var distance = event.changedTouches[0].clientX - touchStartX;
        touchStartX = null;
        if (Math.abs(distance) < 50) return;
        renderLightbox(currentIndex + (distance < 0 ? 1 : -1));
      }, { passive: true });
    }
  });
})();
