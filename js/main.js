/* ═══════════════════════════════════════════════════════
   VOLKSIMMOBILIEN – Main JavaScript
   ═══════════════════════════════════════════════════════ */

(function () {
  'use strict';

  /* ─── HEADER SCROLL + SCROLL-TO-TOP ─────────────── */
  const header = document.getElementById('siteHeader');
  const scrollToTopBtn = document.getElementById('scrollToTop');
  let lastScroll = 0;
  let ticking = false;

  function onScroll() {
    const scrollY = window.scrollY;
    if (header) {
      if (scrollY > 60) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }
    if (scrollToTopBtn) {
      const showTop = scrollY > 400;
      scrollToTopBtn.classList.toggle('is-visible', showTop);
      scrollToTopBtn.setAttribute('aria-hidden', showTop ? 'false' : 'true');
      scrollToTopBtn.tabIndex = showTop ? 0 : -1;
    }
    lastScroll = scrollY;
    ticking = false;
  }

  window.addEventListener('scroll', function () {
    if (!ticking) {
      window.requestAnimationFrame(onScroll);
      ticking = true;
    }
  }, { passive: true });

  onScroll();

  if (scrollToTopBtn) {
    scrollToTopBtn.addEventListener('click', function () {
      const instant = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: instant ? 'auto' : 'smooth' });
      scrollToTopBtn.blur();
    });
  }

  /* ─── HAMBURGER / MOBILE NAV ────────────────────── */
  const hamburger = document.getElementById('hamburger');
  const mobileOverlay = document.getElementById('mobileOverlay');

  if (hamburger && mobileOverlay) {
    hamburger.addEventListener('click', function () {
      const isOpen = hamburger.classList.toggle('active');
      mobileOverlay.classList.toggle('active');
      hamburger.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    mobileOverlay.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        hamburger.classList.remove('active');
        mobileOverlay.classList.remove('active');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* ─── REVEAL ON SCROLL (IntersectionObserver) ───── */
  const revealElements = document.querySelectorAll('.reveal');

  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(function (el) {
      revealObserver.observe(el);
    });
  } else {
    revealElements.forEach(function (el) {
      el.classList.add('visible');
    });
  }

  /* ─── ANIMATED COUNTER ──────────────────────────── */
  const counters = document.querySelectorAll('[data-count]');

  if (counters.length && 'IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          counterObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(function (counter) {
      counterObserver.observe(counter);
    });
  }

  function animateCounter(el) {
    const target = parseInt(el.getAttribute('data-count'), 10);
    const duration = 1800;
    const start = performance.now();

    function update(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  /* ─── CURRENT YEAR ──────────────────────────────── */
  const yearEl = document.getElementById('currentYear');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  /* ─── MOBILE STICKY BAR (show after hero) ───────── */
  const mobileBar = document.getElementById('mobileStickyBar');
  const heroSection = document.getElementById('hero');

  if (mobileBar && heroSection && 'IntersectionObserver' in window) {
    const barObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          mobileBar.classList.add('visible');
        } else {
          mobileBar.classList.remove('visible');
        }
      });
    }, { threshold: 0 });

    barObserver.observe(heroSection);
  }

  /* ─── HERO VIDEO: reduced motion ────────────────── */
  const heroVideo = document.querySelector('.hero-video');
  if (heroVideo && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    heroVideo.removeAttribute('autoplay');
    heroVideo.pause();
  }

  /* ─── SMOOTH SCROLL FOR ANCHOR LINKS ────────────── */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ─── VALUATION WIZARD ─────────────────────────── */
  var wizard = document.getElementById('valuationWizard');
  if (wizard) {
    var steps = wizard.querySelectorAll('.wizard-step');
    /* Eigener Name: sonst überschreibt der Weil-Slider später dieselbe var progressBar im IIFE */
    var wizardProgressEl = document.getElementById('wizardProgressBar');
    var btnNext = document.getElementById('wizardNext');
    var btnBack = document.getElementById('wizardBack');
    var navBar = document.getElementById('wizardNav');
    var current = 1;
    var totalSteps = 3;
    var data = {};

    function showStep(n) {
      steps.forEach(function (s) { s.classList.remove('active'); });
      var step = wizard.querySelector('[data-step="' + n + '"]');
      if (step) step.classList.add('active');

      if (wizardProgressEl) {
        wizardProgressEl.style.width = Math.min((n / totalSteps) * 100, 100) + '%';
      }

      if (n <= totalSteps) {
        navBar.style.display = 'flex';
        btnBack.hidden = (n === 1);
        btnNext.textContent = (n === totalSteps) ? 'Absenden' : 'Weiter';
        if (n < totalSteps) {
          btnNext.innerHTML += ' <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
        }
      } else {
        navBar.style.display = 'none';
      }

      validateStep(n);
    }

    function validateStep(n) {
      if (!btnNext) return;
      var valid = false;

      if (n === 1) {
        valid = !!wizard.querySelector('.wizard-option.selected');
      } else if (n === 2) {
        var plz = wizard.querySelector('#wiz-plz');
        valid = plz && plz.value.trim().length === 5;
      } else if (n === 3) {
        var vorname = wizard.querySelector('#wiz-vorname');
        var nachname = wizard.querySelector('#wiz-nachname');
        var email = wizard.querySelector('#wiz-email');
        valid = vorname && vorname.value.trim() !== '' &&
                nachname && nachname.value.trim() !== '' &&
                email && email.value.trim() !== '' && email.validity.valid;
      }

      btnNext.disabled = !valid;
    }

    wizard.querySelectorAll('.wizard-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        wizard.querySelectorAll('.wizard-option').forEach(function (o) {
          o.classList.remove('selected');
        });
        opt.classList.add('selected');
        data.type = opt.getAttribute('data-value');
        validateStep(current);
      });
    });

    wizard.querySelectorAll('input').forEach(function (input) {
      input.addEventListener('input', function () { validateStep(current); });
    });

    btnNext.addEventListener('click', function () {
      if (btnNext.disabled) return;

      if (current === totalSteps) {
        wizard.querySelectorAll('input').forEach(function (inp) {
          data[inp.name] = inp.value;
        });
        current = 4;
        showStep(current);
        return;
      }

      current++;
      showStep(current);
    });

    btnBack.addEventListener('click', function () {
      if (current > 1) {
        current--;
        showStep(current);
      }
    });

    showStep(1);
  }

  /* ─── WEIL SLIDER ──────────────────────────────────── */
  var slider = document.querySelector('.weil-slider');
  if (slider) {
    var slides = slider.querySelectorAll('.weil-slide');
    var dotsContainer = slider.querySelector('.weil-slider-dots');
    var weilProgressBar = slider.querySelector('.weil-slider-progress-bar');
    var currentSlide = 0;
    var autoTimeout = null;
    var paused = false;
    var INTERVAL_MS = 7000;

    slider.style.setProperty('--weil-slider-interval', INTERVAL_MS + 'ms');

    slides.forEach(function (_, i) {
      var dot = document.createElement('button');
      dot.className = 'weil-slider-dot' + (i === 0 ? ' active' : '');
      dot.setAttribute('aria-label', 'Slide ' + (i + 1));
      dot.addEventListener('click', function () { goTo(i); startAuto(); });
      dotsContainer.appendChild(dot);
    });

    var dots = dotsContainer.querySelectorAll('.weil-slider-dot');

    function resetProgress() {
      weilProgressBar.classList.remove('animating');
      weilProgressBar.style.width = '0%';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          weilProgressBar.classList.add('animating');
        });
      });
    }

    function goTo(index) {
      slides[currentSlide].classList.remove('active');
      dots[currentSlide].classList.remove('active');
      currentSlide = index;
      slides[currentSlide].classList.add('active');
      dots[currentSlide].classList.add('active');
      resetProgress();
    }

    function next() {
      goTo((currentSlide + 1) % slides.length);
    }

    function scheduleNext() {
      clearTimeout(autoTimeout);
      autoTimeout = setTimeout(function () {
        next();
        if (!paused) scheduleNext();
      }, INTERVAL_MS);
    }

    function startAuto() {
      paused = false;
      resetProgress();
      scheduleNext();
    }

    function stopAuto() {
      paused = true;
      clearTimeout(autoTimeout);
      weilProgressBar.classList.remove('animating');
      weilProgressBar.style.width = weilProgressBar.getBoundingClientRect().width /
        weilProgressBar.parentElement.getBoundingClientRect().width * 100 + '%';
    }

    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);

    resetProgress();
    startAuto();
  }

  /* ─── HERO SLIDER (Mallorca) ─────────────────────── */
  var heroSlider = document.getElementById('heroSlider');
  if (heroSlider) {
    var heroSlides = heroSlider.querySelectorAll('.hero-slide');
    var heroDotsContainer = document.getElementById('heroSliderDots');
    var heroProgress = document.getElementById('heroSliderProgressBar');
    var heroCaptionEl = document.getElementById('heroSliderCaption');
    var heroMeta = document.getElementById('heroSliderMeta');
    var heroIndex = 0;
    var heroTimer = null;
    var heroPaused = false;
    var HERO_INTERVAL = 6500;
    var heroReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Pagination-Dots erzeugen */
    if (heroDotsContainer) {
      for (var i = 0; i < heroSlides.length; i++) {
        (function (idx) {
          var dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'hero-slider-dot' + (idx === 0 ? ' is-active' : '');
          dot.setAttribute('role', 'tab');
          dot.setAttribute('aria-label', 'Slide ' + (idx + 1));
          dot.addEventListener('click', function () {
            heroGoTo(idx);
            heroStart();
          });
          heroDotsContainer.appendChild(dot);
        })(i);
      }
    }
    var heroDots = heroDotsContainer ? heroDotsContainer.querySelectorAll('.hero-slider-dot') : [];

    function heroResetProgress() {
      if (!heroProgress) return;
      heroProgress.classList.remove('is-running');
      heroProgress.style.width = '0%';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          heroProgress.classList.add('is-running');
        });
      });
    }

    function heroUpdateMeta(idx) {
      if (!heroMeta || !heroCaptionEl) return;
      var slide = heroSlides[idx];
      var caption = slide ? slide.getAttribute('data-caption') : '';

      heroMeta.classList.add('is-fading');
      setTimeout(function () {
        if (caption !== null) heroCaptionEl.innerHTML = caption;
        heroMeta.classList.remove('is-fading');
      }, 280);
    }

    function heroGoTo(idx) {
      if (idx === heroIndex || idx < 0 || idx >= heroSlides.length) return;
      heroSlides[heroIndex].classList.remove('is-active');
      if (heroDots[heroIndex]) heroDots[heroIndex].classList.remove('is-active');

      heroIndex = idx;
      heroSlides[heroIndex].classList.add('is-active');
      if (heroDots[heroIndex]) heroDots[heroIndex].classList.add('is-active');

      heroUpdateMeta(heroIndex);
      heroResetProgress();
    }

    function heroNext() {
      heroGoTo((heroIndex + 1) % heroSlides.length);
    }

    function heroSchedule() {
      clearTimeout(heroTimer);
      heroTimer = setTimeout(function () {
        heroNext();
        if (!heroPaused) heroSchedule();
      }, HERO_INTERVAL);
    }

    function heroStart() {
      if (heroReducedMotion) return;
      heroPaused = false;
      heroResetProgress();
      heroSchedule();
    }

    function heroStop() {
      heroPaused = true;
      clearTimeout(heroTimer);
      if (heroProgress) {
        var pct = heroProgress.getBoundingClientRect().width /
          heroProgress.parentElement.getBoundingClientRect().width * 100;
        heroProgress.classList.remove('is-running');
        heroProgress.style.width = pct + '%';
      }
    }

    /* Pause beim Hover über Hero-Inhalt (nicht über die Bilder selbst, damit
       Touch-Geräte nicht versehentlich pausieren) */
    heroSlider.addEventListener('mouseenter', heroStop);
    heroSlider.addEventListener('mouseleave', heroStart);

    /* Pause, wenn Tab im Hintergrund liegt */
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        heroStop();
      } else {
        heroStart();
      }
    });

    heroUpdateMeta(0);
    if (!heroReducedMotion) {
      heroResetProgress();
      heroSchedule();
    }
  }

  /* ─── PROCESS REEL (Mallorca) ─────────────────────────
     Cinematic Stepper mit Auto-Crossfade, Klick auf Knoten,
     Tastatur (←/→ / Pos1 / Ende), Pause auf Hover und
     Mouse-Tilt-Parallax auf dem Visual. */
  document.querySelectorAll('[data-process-reel]').forEach(function (reel) {
    var nodes = Array.prototype.slice.call(reel.querySelectorAll('.process-reel-node'));
    var scenes = Array.prototype.slice.call(reel.querySelectorAll('.process-reel-scene'));
    var lineFill = reel.querySelector('[data-reel-line-fill]');
    var stageBar = reel.querySelector('[data-reel-stage-bar]');
    var tiltVisuals = Array.prototype.slice.call(reel.querySelectorAll('[data-reel-tilt]'));

    if (!nodes.length || !scenes.length) return;

    var total = nodes.length;
    var interval = parseInt(reel.getAttribute('data-autoplay'), 10) || 6500;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var current = 0;
    var timer = null;
    var paused = reduceMotion;
    var visible = false;

    function setLine(index) {
      if (!lineFill) return;
      var pct = total > 1 ? (index / (total - 1)) * 100 : 0;
      lineFill.style.width = pct + '%';
    }

    function restartStageBar() {
      if (!stageBar) return;
      stageBar.style.transition = 'none';
      stageBar.style.transform = 'scaleX(0)';
      // Reflow erzwingen, damit der Browser den Reset registriert.
      void stageBar.offsetWidth;
      if (!paused && !reduceMotion) {
        stageBar.style.transition = 'transform ' + interval + 'ms linear';
        stageBar.style.transform = 'scaleX(1)';
      }
    }

    function freezeStageBar() {
      if (!stageBar) return;
      var rect = stageBar.getBoundingClientRect();
      var parentRect = stageBar.parentElement.getBoundingClientRect();
      var ratio = parentRect.width > 0 ? rect.width / parentRect.width : 0;
      stageBar.style.transition = 'none';
      stageBar.style.transform = 'scaleX(' + Math.max(0, Math.min(1, ratio)) + ')';
    }

    function setActive(index) {
      var next = ((index % total) + total) % total;
      nodes.forEach(function (node, i) {
        var active = i === next;
        node.classList.toggle('is-active', active);
        node.setAttribute('aria-selected', active ? 'true' : 'false');
        node.setAttribute('tabindex', active ? '0' : '-1');
      });
      /* Ein Durchgang: alte und neue Szene wechseln im selben Frame → Crossfade */
      scenes.forEach(function (scene, i) {
        var active = i === next;
        scene.classList.toggle('is-active', active);
        scene.setAttribute('aria-hidden', active ? 'false' : 'true');
        if (typeof scene.toggleAttribute === 'function') {
          if (active) scene.removeAttribute('inert');
          else scene.setAttribute('inert', '');
        }
      });
      setLine(next);
      current = next;
      restartStageBar();
    }

    function clearTimer() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    function startAuto() {
      if (paused || reduceMotion || !visible) return;
      clearTimer();
      timer = window.setInterval(function () {
        setActive(current + 1);
      }, interval);
      restartStageBar();
    }

    function stopAuto() {
      clearTimer();
      freezeStageBar();
    }

    nodes.forEach(function (node) {
      node.addEventListener('click', function () {
        var idx = parseInt(node.getAttribute('data-index'), 10) || 0;
        setActive(idx);
        if (!paused) startAuto();
      });
    });

    /* Tastatur: ←/→ wechselt, Pos1/Ende springt zum ersten/letzten Schritt */
    reel.querySelector('.process-reel-track').addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') {
        e.preventDefault();
        setActive(current + 1);
        nodes[current].focus();
        if (!paused) startAuto();
      } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        setActive(current - 1);
        nodes[current].focus();
        if (!paused) startAuto();
      } else if (e.key === 'Home') {
        e.preventDefault();
        setActive(0);
        nodes[0].focus();
      } else if (e.key === 'End') {
        e.preventDefault();
        setActive(total - 1);
        nodes[total - 1].focus();
      }
    });

    /* Pause beim Hover/Focus, weiter beim Verlassen */
    reel.addEventListener('mouseenter', stopAuto);
    reel.addEventListener('mouseleave', function () { if (!paused) startAuto(); });
    reel.addEventListener('focusin', stopAuto);
    reel.addEventListener('focusout', function (e) {
      if (!reel.contains(e.relatedTarget) && !paused) startAuto();
    });

    /* Pause, wenn Tab im Hintergrund liegt */
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stopAuto();
      else if (!paused) startAuto();
    });

    /* Mouse-Tilt-Parallax auf dem Visual (cinematic, dezent) */
    if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
      tiltVisuals.forEach(function (visual) {
        visual.addEventListener('mousemove', function (e) {
          var rect = visual.getBoundingClientRect();
          var x = (e.clientX - rect.left) / rect.width - 0.5;
          var y = (e.clientY - rect.top) / rect.height - 0.5;
          visual.style.setProperty('--tilt-x', (y * -5).toFixed(2) + 'deg');
          visual.style.setProperty('--tilt-y', (x * 6).toFixed(2) + 'deg');
        });
        visual.addEventListener('mouseleave', function () {
          visual.style.setProperty('--tilt-x', '0deg');
          visual.style.setProperty('--tilt-y', '0deg');
        });
      });
    }

    /* Erst starten, wenn der Reel sichtbar ist */
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          visible = entry.isIntersecting;
          if (visible && !paused) startAuto();
          else stopAuto();
        });
      }, { threshold: 0.35 });
      io.observe(reel);
    } else {
      visible = true;
      if (!paused) startAuto();
    }

    setActive(0);
  });

})();
