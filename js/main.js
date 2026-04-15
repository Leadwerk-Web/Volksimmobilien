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
    var progressBar = document.getElementById('wizardProgressBar');
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

      progressBar.style.width = Math.min((n / totalSteps) * 100, 100) + '%';

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
    var progressBar = slider.querySelector('.weil-slider-progress-bar');
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
      progressBar.classList.remove('animating');
      progressBar.style.width = '0%';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          progressBar.classList.add('animating');
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
      progressBar.classList.remove('animating');
      progressBar.style.width = progressBar.getBoundingClientRect().width /
        progressBar.parentElement.getBoundingClientRect().width * 100 + '%';
    }

    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);

    resetProgress();
    startAuto();
  }

})();
