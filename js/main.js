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

  /* ─── VALUATION WIZARD ─────────────────────────────────
     7 Schritte (+ Erfolg): Typ → Subtyp (entfällt bei MFH) → Adresse
     → Eckdaten → Ausstattung & Zustand → Anlass → Kontakt → Danke. */
  var wizard = document.getElementById('valuationWizard');
  if (wizard) {
    var steps = wizard.querySelectorAll('.wizard-step');
    /* Eigener Name: sonst überschreibt der Weil-Slider später dieselbe var progressBar im IIFE */
    var wizardProgressEl = document.getElementById('wizardProgressBar');
    var btnNext = document.getElementById('wizardNext');
    var btnBack = document.getElementById('wizardBack');
    var navBar = document.getElementById('wizardNav');
    var mapIframe = document.getElementById('wizardMap');
    var current = 1;
    var totalSteps = 7;
    var successStep = 8;
    var data = {};
    var mapDebounce = null;

    function hasSubtype() {
      return data.type === 'einfamilienhaus' || data.type === 'wohnung';
    }

    function nextStepFrom(n) {
      if (n === 1 && !hasSubtype()) return 3;
      return n + 1;
    }

    function prevStepFrom(n) {
      if (n === 3 && !hasSubtype()) return 1;
      return n - 1;
    }

    function applySubtypeVisibility() {
      wizard.querySelectorAll('.wizard-choice-group--subtype').forEach(function (g) {
        var match = g.getAttribute('data-subtype-for') === data.type;
        g.hidden = !match;
      });
    }

    function applyEckdatenHeading() {
      var h = wizard.querySelector('#wizEckdatenHeading');
      var hint = wizard.querySelector('#wizEckdatenHint');
      if (!h || !hint) return;
      if (data.type === 'wohnung') {
        h.textContent = 'Erzähl uns mehr über die Immobilie';
        hint.textContent = 'Je genauer die Angaben zu Wohnfläche, Zimmer und Baujahr, desto besser unsere Einschätzung für Deine Eigentumswohnung.';
      } else if (data.type === 'mehrfamilienhaus') {
        h.textContent = 'Erzähl uns mehr über Deine Immobilie';
        hint.textContent = 'Flächen, Wohneinheiten, Baujahr und Mieteinnahmen – je genauer, desto besser unsere Einordnung Deines Mehrfamilienhauses.';
      } else {
        h.textContent = 'Erzähl uns mehr über Deine Immobilie';
        hint.textContent = 'Je genauer die Angaben, desto besser unsere Einschätzung.';
      }
    }

    function applyEckdatenLayout() {
      var grid = wizard.querySelector('#wizEckdatenGrid');
      var grundWrap = wizard.querySelector('#wiz-eckdaten-grund-wrap');
      var mietWrap = wizard.querySelector('#wiz-eckdaten-mfh-miete-wrap');
      var zimmerWrap = wizard.querySelector('#wiz-eckdaten-zimmer-wrap');
      var whEinWrap = wizard.querySelector('#wiz-eckdaten-wohneinheiten-wrap');
      var zimmerInp = wizard.querySelector('#wiz-zimmer');
      var whEinInp = wizard.querySelector('#wiz-wohneinheiten');
      var mietRange = wizard.querySelector('#wiz-mfh-miete-range');
      var mietNum = wizard.querySelector('#wiz-mfh-miete');
      if (!grid || !grundWrap) return;
      var isWhg = data.type === 'wohnung';
      var isMfh = data.type === 'mehrfamilienhaus';
      grundWrap.hidden = isWhg;
      grid.classList.toggle('wizard-eckdaten--wohnung', isWhg);
      if (zimmerWrap) zimmerWrap.hidden = isMfh;
      if (whEinWrap) whEinWrap.hidden = !isMfh;
      if (mietWrap) {
        mietWrap.hidden = !isMfh;
        mietWrap.setAttribute('aria-hidden', isMfh ? 'false' : 'true');
      }
      if (mietRange) mietRange.disabled = !isMfh;
      if (mietNum) mietNum.disabled = !isMfh;
      if (zimmerInp && whEinInp) {
        if (isMfh) {
          zimmerInp.removeAttribute('required');
          whEinInp.setAttribute('required', 'required');
        } else {
          whEinInp.removeAttribute('required');
          zimmerInp.setAttribute('required', 'required');
        }
      }
    }

    function applyDetailVisibility() {
      var groups = wizard.querySelectorAll('.wizard-detail-fields');
      groups.forEach(function (g) {
        var match = g.getAttribute('data-detail-for') === data.type;
        g.hidden = !match;
      });
    }

    function effectiveStepCount(n) {
      /* Fortschritt: MFH ohne Subtyp-Schritt → eine Stufe weniger */
      var skipsSubtype = !hasSubtype();
      var totalVisible = skipsSubtype ? totalSteps - 1 : totalSteps;
      var pos = n;
      if (n >= successStep) pos = totalVisible;
      else if (skipsSubtype && n >= 3) pos = n - 1;
      return { pos: pos, total: totalVisible };
    }

    function showStep(n) {
      steps.forEach(function (s) { s.classList.remove('active'); });
      var step = wizard.querySelector('[data-step="' + n + '"]');
      if (step) step.classList.add('active');

      if (n === 2) applySubtypeVisibility();
      if (n === 4) {
        applyEckdatenHeading();
        applyEckdatenLayout();
        syncEckdatenDetailSliders();
        applyDetailVisibility();
        if (data.type === 'einfamilienhaus') syncEfhSlidersFromValues();
        if (data.type === 'wohnung') syncWhgFlaecheSlider();
        if (data.type === 'mehrfamilienhaus') syncMfhSlidersFromValues();
      }
      if (n === 3) {
        clearTimeout(mapDebounce);
        mapDebounce = setTimeout(updateMapPreview, 200);
      }

      if (wizardProgressEl) {
        var ec = effectiveStepCount(n);
        wizardProgressEl.style.width = Math.min((ec.pos / ec.total) * 100, 100) + '%';
      }

      if (n <= totalSteps) {
        navBar.style.display = 'flex';
        btnBack.hidden = (n === 1);
        if (n === totalSteps) {
          btnNext.textContent = 'Absenden';
        } else {
          btnNext.innerHTML = 'Weiter <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
        }
      } else {
        navBar.style.display = 'none';
      }

      validateStep(n);
    }

    function isFilled(el) {
      return el && el.value !== undefined && el.value.toString().trim() !== '';
    }

    function validateStep(n) {
      if (!btnNext) return;
      var valid = false;

      if (n === 1) {
        valid = !!wizard.querySelector('.wizard-option.selected');
      } else if (n === 2) {
        var visibleSub = wizard.querySelector('.wizard-choice-group--subtype:not([hidden])');
        valid = !!(visibleSub && visibleSub.querySelector('.wizard-choice.selected'));
      } else if (n === 3) {
        var plz = wizard.querySelector('#wiz-plz');
        var ort = wizard.querySelector('#wiz-ort');
        var strasse = wizard.querySelector('#wiz-strasse');
        valid = isFilled(strasse) && isFilled(ort) &&
                plz && /^[0-9]{5}$/.test(plz.value.trim());
      } else if (n === 4) {
        var flaeche = wizard.querySelector('#wiz-flaeche');
        var zimmer = wizard.querySelector('#wiz-zimmer');
        var wohnEin = wizard.querySelector('#wiz-wohneinheiten');
        var baujahr = wizard.querySelector('#wiz-baujahr');
        var grund = wizard.querySelector('#wiz-grundstueck');
        var zimmerOk = data.type === 'mehrfamilienhaus'
          ? !!(wohnEin && isFilled(wohnEin) && wohnEin.validity.valid)
          : !!(zimmer && isFilled(zimmer) && zimmer.validity.valid);
        valid = isFilled(flaeche) && zimmerOk && isFilled(baujahr) &&
                flaeche.validity.valid && baujahr.validity.valid;
        if (valid && data.type !== 'wohnung' && grund) {
          valid = isFilled(grund) && grund.validity.valid;
        }
        if (valid && data.type === 'mehrfamilienhaus') {
          var miete = wizard.querySelector('#wiz-mfh-miete');
          valid = !!(miete && isFilled(miete) && miete.validity.valid);
        }
      } else if (n === 5) {
        var selAus = wizard.querySelector('#wiz-ausstattung');
        var selZu = wizard.querySelector('#wiz-zustand');
        valid = !!(selAus && selAus.value && selZu && selZu.value);
      } else if (n === 6) {
        var selAnlass = wizard.querySelector('#wiz-anlass');
        valid = !!(selAnlass && selAnlass.value);
      } else if (n === 7) {
        var vorname = wizard.querySelector('#wiz-vorname');
        var nachname = wizard.querySelector('#wiz-nachname');
        var email = wizard.querySelector('#wiz-email');
        var datenschutz = wizard.querySelector('#wiz-datenschutz');
        valid = isFilled(vorname) && isFilled(nachname) &&
                email && email.value.trim() !== '' && email.validity.valid &&
                datenschutz && datenschutz.checked;
      }

      btnNext.disabled = !valid;
    }

    /* Typ-Auswahl: setzt Subtyp zurück, wenn der Typ wechselt */
    wizard.querySelectorAll('.wizard-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        var prevType = data.type;
        wizard.querySelectorAll('.wizard-option').forEach(function (o) {
          o.classList.remove('selected');
        });
        opt.classList.add('selected');
        data.type = opt.getAttribute('data-value');
        if (prevType !== data.type) {
          delete data.subtype;
          wizard.querySelectorAll('.wizard-choice-group--subtype .wizard-choice').forEach(function (c) {
            c.classList.remove('selected');
          });
        }
        applySubtypeVisibility();
        applyDetailVisibility();
        applyEckdatenLayout();
        applyEckdatenHeading();
        validateStep(current);
      });
    });

    /* Choice-Gruppen (Subtyp, Anlass, Zeitrahmen) bzw. natives Select Ausstattung/Zustand (Custom-UI) */
    wizard.querySelectorAll('.wizard-choice-group').forEach(function (group) {
      var groupName = group.getAttribute('data-name');
      var dropdown = group.querySelector('select.wizard-select-native');
      if (dropdown) {
        dropdown.addEventListener('change', function () {
          if (groupName) data[groupName] = dropdown.value;
          validateStep(current);
        });
        return;
      }
      group.querySelectorAll('.wizard-choice').forEach(function (choice) {
        choice.addEventListener('click', function () {
          group.querySelectorAll('.wizard-choice').forEach(function (c) {
            c.classList.remove('selected');
          });
          choice.classList.add('selected');
          if (groupName) data[groupName] = choice.getAttribute('data-value');
          validateStep(current);
        });
      });
    });

    /* Custom-Dropdown (Glasmorphism-Liste, Menü an body gegen overflow: hidden) */
    function initWizardCustomSelects(root) {
      function getMenuFromBtn(btn) {
        var id = btn.getAttribute('aria-controls');
        return id ? document.getElementById(id) : null;
      }

      function syncOptionAria(menu, value) {
        menu.querySelectorAll('.wizard-select__option').forEach(function (opt) {
          opt.setAttribute('aria-selected', opt.getAttribute('data-value') === value ? 'true' : 'false');
        });
      }

      function syncTriggerLabel(select, btn) {
        var span = btn.querySelector('.wizard-select__value');
        if (!span) return;
        var v = select.value;
        var text = '';
        for (var i = 0; i < select.options.length; i++) {
          if (select.options[i].value === v) {
            text = select.options[i].textContent;
            break;
          }
        }
        span.textContent = text || 'Bitte wählen …';
      }

      function positionMenu(btn, menu) {
        var r = btn.getBoundingClientRect();
        var w = Math.min(r.width, window.innerWidth - 16);
        var left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
        menu.style.left = left + 'px';
        menu.style.top = (r.bottom + 6) + 'px';
        menu.style.width = w + 'px';
        var spaceBelow = window.innerHeight - r.bottom - 14;
        var maxH = Math.min(Math.max(spaceBelow, 120), window.innerHeight * 0.45, 280);
        menu.style.maxHeight = maxH + 'px';
      }

      function closeCustomSelect(wrap) {
        var box = wrap.querySelector('.wizard-custom-select__box');
        var btn = wrap.querySelector('.wizard-select--trigger');
        var menu = btn ? getMenuFromBtn(btn) : null;
        if (!menu || !btn || !box) return;
        menu.hidden = true;
        wrap.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        if (menu.parentNode === document.body) {
          box.appendChild(menu);
        }
      }

      function closeAllCustomExcept(keepWrap) {
        root.querySelectorAll('[data-custom-select].is-open').forEach(function (w) {
          if (w !== keepWrap) closeCustomSelect(w);
        });
      }

      root.querySelectorAll('[data-custom-select]').forEach(function (wrap) {
        if (wrap.getAttribute('data-custom-bound')) return;
        wrap.setAttribute('data-custom-bound', '1');
        var box = wrap.querySelector('.wizard-custom-select__box');
        var select = wrap.querySelector('select.wizard-select-native');
        var btn = wrap.querySelector('.wizard-select--trigger');
        var menu = btn ? getMenuFromBtn(btn) : null;
        if (!box || !select || !btn || !menu) return;

        syncTriggerLabel(select, btn);
        syncOptionAria(menu, select.value);

        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var open = wrap.classList.contains('is-open');
          closeAllCustomExcept(wrap);
          if (open) {
            closeCustomSelect(wrap);
            return;
          }
          document.body.appendChild(menu);
          positionMenu(btn, menu);
          menu.hidden = false;
          wrap.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
          requestAnimationFrame(function () {
            positionMenu(btn, menu);
          });
        });

        menu.addEventListener('click', function (e) {
          var li = e.target.closest('.wizard-select__option');
          if (!li || !menu.contains(li)) return;
          e.stopPropagation();
          var val = li.getAttribute('data-value');
          select.value = val === null ? '' : val;
          syncTriggerLabel(select, btn);
          syncOptionAria(menu, select.value);
          select.dispatchEvent(new Event('change', { bubbles: true }));
          closeCustomSelect(wrap);
          btn.focus();
        });

        select.addEventListener('change', function () {
          syncTriggerLabel(select, btn);
          syncOptionAria(menu, select.value);
        });
      });

      document.addEventListener('click', function (e) {
        root.querySelectorAll('[data-custom-select].is-open').forEach(function (w) {
          var b = w.querySelector('.wizard-select--trigger');
          var m = b ? getMenuFromBtn(b) : null;
          if (!m || !b) return;
          if (!w.contains(e.target) && !m.contains(e.target)) {
            closeCustomSelect(w);
          }
        });
      });

      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        root.querySelectorAll('[data-custom-select].is-open').forEach(closeCustomSelect);
      });

      function repositionOpenMenus() {
        root.querySelectorAll('[data-custom-select].is-open').forEach(function (w) {
          var b = w.querySelector('.wizard-select--trigger');
          var m = b ? getMenuFromBtn(b) : null;
          if (b && m && !m.hidden) positionMenu(b, m);
        });
      }

      window.addEventListener('resize', repositionOpenMenus);
      window.addEventListener('scroll', repositionOpenMenus, true);
    }

    initWizardCustomSelects(wizard);

    /* Google-Maps-Vorschau aus Adresse aktualisieren (debounced) */
    function updateMapPreview() {
      if (!mapIframe) return;
      var s = (wizard.querySelector('#wiz-strasse') || {}).value || '';
      var p = (wizard.querySelector('#wiz-plz') || {}).value || '';
      var o = (wizard.querySelector('#wiz-ort') || {}).value || '';
      s = s.trim(); p = p.trim(); o = o.trim();
      if (!s && !p && !o) return;
      var parts = [];
      if (s) parts.push(s);
      if (p && o) parts.push(p + ' ' + o);
      else if (p) parts.push(p);
      else if (o) parts.push(o);
      var query = parts.join(', ');
      var hasCity = !!o || /^[0-9]{5}$/.test(p);
      if (!hasCity) return;
      query += ', Deutschland';
      var url = 'https://www.google.com/maps?q=' + encodeURIComponent(query) + '&output=embed&z=15';
      mapIframe.src = url;
    }

    ['#wiz-strasse', '#wiz-plz', '#wiz-ort'].forEach(function (sel) {
      var el = wizard.querySelector(sel);
      if (!el) return;
      el.addEventListener('input', function () {
        clearTimeout(mapDebounce);
        mapDebounce = setTimeout(updateMapPreview, 700);
      });
    });

    function clampNum(n, min, max) {
      if (typeof n !== 'number' || isNaN(n)) return min;
      return Math.min(max, Math.max(min, n));
    }

    function updateRangeFill(rangeEl) {
      if (!rangeEl) return;
      var min = +rangeEl.min;
      var max = +rangeEl.max;
      var val = +rangeEl.value;
      var pct = max > min ? ((val - min) / (max - min)) * 100 : 0;
      rangeEl.style.setProperty('--wizard-range-fill', pct + '%');
      rangeEl.setAttribute('aria-valuenow', String(val));
    }

    function syncEckdatenDetailSliders() {
      [
        ['#wiz-flaeche-range', '#wiz-flaeche'],
        ['#wiz-grundstueck-range', '#wiz-grundstueck'],
        ['#wiz-mfh-miete-range', '#wiz-mfh-miete']
      ].forEach(function (pair) {
        var r = wizard.querySelector(pair[0]);
        var num = wizard.querySelector(pair[1]);
        if (!r || !num) return;
        var v = parseFloat(num.value);
        if (!isNaN(v)) {
          v = clampNum(v, +r.min, +r.max);
          num.value = String(v);
          r.value = String(v);
        } else if (num.value.trim() === '') {
          num.value = r.value;
        }
        updateRangeFill(r);
      });
    }

    function syncEfhSlidersFromValues() {
      [
        ['#wiz-efh-flaeche-range', '#wiz-efh-flaeche'],
        ['#wiz-efh-grundstueck-range', '#wiz-efh-grundstueck']
      ].forEach(function (pair) {
        var r = wizard.querySelector(pair[0]);
        var num = wizard.querySelector(pair[1]);
        if (!r || !num) return;
        var v = parseFloat(num.value);
        if (!isNaN(v)) {
          v = clampNum(v, +r.min, +r.max);
          num.value = String(v);
          r.value = String(v);
        } else if (num.value.trim() === '') {
          num.value = r.value;
        }
        updateRangeFill(r);
      });
    }

    function syncWhgFlaecheSlider() {
      var r = wizard.querySelector('#wiz-whg-flaeche-range');
      var num = wizard.querySelector('#wiz-whg-flaeche');
      if (!r || !num) return;
      var v = parseFloat(num.value);
      if (!isNaN(v)) {
        v = clampNum(v, +r.min, +r.max);
        num.value = String(v);
        r.value = String(v);
      } else if (num.value.trim() === '') {
        num.value = r.value;
      }
      updateRangeFill(r);
    }

    function syncMfhSlidersFromValues() {
      [
        ['#wiz-mfh-flaeche-range', '#wiz-mfh-flaeche'],
        ['#wiz-mfh-grundstueck-range', '#wiz-mfh-grundstueck']
      ].forEach(function (pair) {
        var r = wizard.querySelector(pair[0]);
        var num = wizard.querySelector(pair[1]);
        if (!r || !num) return;
        var v = parseFloat(num.value);
        if (!isNaN(v)) {
          v = clampNum(v, +r.min, +r.max);
          num.value = String(v);
          r.value = String(v);
        } else if (num.value.trim() === '') {
          num.value = r.value;
        }
        updateRangeFill(r);
      });
    }

    function bindEfhDetailControls() {
      wizard.querySelectorAll('.wizard-stepper').forEach(function (wrap) {
        var inp = wrap.querySelector('.wizard-stepper__input[data-detail-name]');
        var down = wrap.querySelector('[data-stepper-down]');
        var up = wrap.querySelector('[data-stepper-up]');
        if (!inp || !down || !up) return;
        if (wrap.getAttribute('data-stepper-bound')) return;
        wrap.setAttribute('data-stepper-bound', '1');
        var step = parseFloat(inp.getAttribute('step')) || 1;
        var min = parseFloat(inp.getAttribute('min')) || 0;
        var max = parseFloat(inp.getAttribute('max')) || 99;
        function roundStep(v) {
          var x = Math.round(v / step) * step;
          var p = (String(step).split('.')[1] || '').length;
          return parseFloat(x.toFixed(p));
        }
        function clampVal(v) {
          var x = roundStep(v);
          if (x < min) x = min;
          if (x > max) x = max;
          return x;
        }
        down.addEventListener('click', function () {
          var v = parseFloat(inp.value);
          if (isNaN(v)) v = min;
          inp.value = String(clampVal(v - step));
          inp.dispatchEvent(new Event('input', { bubbles: true }));
        });
        up.addEventListener('click', function () {
          var v = parseFloat(inp.value);
          if (isNaN(v)) v = min;
          inp.value = String(clampVal(v + step));
          inp.dispatchEvent(new Event('input', { bubbles: true }));
        });
      });

      function bindPair(rangeSel, numSel) {
        var rangeEl = wizard.querySelector(rangeSel);
        var numEl = wizard.querySelector(numSel);
        if (!rangeEl || !numEl) return;
        if (rangeEl.getAttribute('data-wizard-range-bound')) return;
        rangeEl.setAttribute('data-wizard-range-bound', '1');
        var rmin = +rangeEl.min;
        var rmax = +rangeEl.max;

        rangeEl.addEventListener('input', function () {
          numEl.value = rangeEl.value;
          updateRangeFill(rangeEl);
          numEl.dispatchEvent(new Event('input', { bubbles: true }));
        });

        numEl.addEventListener('input', function () {
          var raw = numEl.value.trim();
          if (raw === '') {
            updateRangeFill(rangeEl);
            return;
          }
          var v = clampNum(parseFloat(raw), rmin, rmax);
          numEl.value = String(v);
          rangeEl.value = String(v);
          updateRangeFill(rangeEl);
        });

        numEl.addEventListener('blur', function () {
          var raw = numEl.value.trim();
          if (raw === '' || isNaN(parseFloat(raw))) {
            numEl.value = rangeEl.value;
          } else {
            var v = clampNum(parseFloat(raw), rmin, rmax);
            numEl.value = String(v);
            rangeEl.value = numEl.value;
          }
          updateRangeFill(rangeEl);
          numEl.dispatchEvent(new Event('input', { bubbles: true }));
        });

        updateRangeFill(rangeEl);
      }

      bindPair('#wiz-efh-flaeche-range', '#wiz-efh-flaeche');
      bindPair('#wiz-efh-grundstueck-range', '#wiz-efh-grundstueck');
      bindPair('#wiz-whg-flaeche-range', '#wiz-whg-flaeche');
      bindPair('#wiz-mfh-flaeche-range', '#wiz-mfh-flaeche');
      bindPair('#wiz-mfh-grundstueck-range', '#wiz-mfh-grundstueck');
      bindPair('#wiz-flaeche-range', '#wiz-flaeche');
      bindPair('#wiz-grundstueck-range', '#wiz-grundstueck');
      bindPair('#wiz-mfh-miete-range', '#wiz-mfh-miete');
    }

    bindEfhDetailControls();

    /* Inputs (text/number/email) und Checkboxen */
    wizard.querySelectorAll('input').forEach(function (input) {
      var ev = input.type === 'checkbox' ? 'change' : 'input';
      input.addEventListener(ev, function () { validateStep(current); });
    });

    wizard.querySelectorAll('select.wizard-select-native').forEach(function (sel) {
      sel.addEventListener('change', function () { validateStep(current); });
    });

    btnNext.addEventListener('click', function () {
      if (btnNext.disabled) return;

      if (current === totalSteps) {
        /* Standard-Inputs mit name-Attribut sammeln */
        wizard.querySelectorAll('input[name], select[name]').forEach(function (inp) {
          if (inp.type === 'checkbox') data[inp.name] = inp.checked;
          else data[inp.name] = inp.value;
        });
        var detailInputs = wizard.querySelectorAll('.wizard-detail-fields[data-detail-for="' + data.type + '"] input[data-detail-name]');
        detailInputs.forEach(function (inp) {
          data[inp.getAttribute('data-detail-name')] = inp.value;
        });
        if (!hasSubtype()) delete data.subtype;
        if (data.type === 'wohnung') delete data.grundstueck;
        if (data.type !== 'mehrfamilienhaus') delete data.mieteinnahmen_jahr;
        if (data.type === 'mehrfamilienhaus') delete data.zimmer;
        else delete data.wohneinheiten;
        current = successStep;
        showStep(current);
        return;
      }

      current = nextStepFrom(current);
      showStep(current);
    });

    btnBack.addEventListener('click', function () {
      if (current > 1) {
        current = prevStepFrom(current);
        showStep(current);
      }
    });

    applyEckdatenLayout();
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

  /* ═══════════════════════════════════════════════════════
     KREISE-MAP – Interaktive SVG-Karte (Kerngebiet BW)

     Lädt Fotos/Kreise_final.svg inline, dekoriert die benannten
     <g>-Gruppen zentral mit class="district" + data-/aria-Attributen
     und bindet zentrale Hover-, Focus- und Click-Logik. Die Hover-
     Optik ist komplett in CSS gesteuert (.district), JS pflegt nur
     den .is-active-Zustand sowie Tooltip- und Info-Panel-Inhalte.

     Live-Daten: districtMapping[*].listings Platzhalter – im Live-System
     mit echten Zählungen aus dem Immobiliensystem überschreiben.
     ═══════════════════════════════════════════════════════ */
  function initKreisMap() {
    var root = document.querySelector('[data-kreis-map]');
    if (!root) return;

    var svgHost = root.querySelector('.kreis-map-svg-host');
    var tooltip = document.getElementById('kreisMapTooltip');
    var infoType = document.getElementById('kreisMapInfoType');
    var infoName = document.getElementById('kreisMapInfoName');
    var infoCount = document.getElementById('kreisMapInfoCount');
    var infoDesc = document.getElementById('kreisMapInfoDesc');
    var infoCta = document.getElementById('kreisMapInfoCta');
    if (!svgHost) return;

    /* Mapping: ursprüngliche <g id> aus Kreise_final.svg
       → saubere data-district-id, Anzeigename, Typ und Beschreibung.
       Eindeutige Zuordnung anhand der Gruppennamen aus der SVG-Quelle. */
    /* listings: Platzhalter bis Anbindung ans Immobiliensystem */
    var districtMapping = {
      'Neckar-Odenwald': { id: 'neckar-odenwald-kreis', name: 'Neckar-Odenwald-Kreis', type: 'Landkreis',  listings: 4, description: 'Entdecken Sie aktuelle Immobilienangebote im Neckar-Odenwald-Kreis.' },
      'Rhein-Neckar':    { id: 'rhein-neckar-kreis',    name: 'Rhein-Neckar-Kreis',    type: 'Landkreis',  listings: 18, description: 'Entdecken Sie aktuelle Immobilienangebote im Rhein-Neckar-Kreis.' },
      'Mannheim':        { id: 'mannheim',              name: 'Mannheim',              type: 'Stadtkreis', listings: 12, description: 'Entdecken Sie aktuelle Immobilienangebote in Mannheim.' },
      'Heidelberg':      { id: 'heidelberg',            name: 'Heidelberg',            type: 'Stadtkreis', listings: 9, description: 'Entdecken Sie aktuelle Immobilienangebote in Heidelberg.' },
      'Freudenstadt':    { id: 'freudenstadt',          name: 'Landkreis Freudenstadt', type: 'Landkreis', listings: 2, description: 'Entdecken Sie aktuelle Immobilienangebote im Landkreis Freudenstadt.' },
      'Pforzheim':       { id: 'pforzheim',             name: 'Pforzheim',             type: 'Stadtkreis', listings: 7, description: 'Entdecken Sie aktuelle Immobilienangebote in Pforzheim.' },
      'Enzkreis':        { id: 'enzkreis',              name: 'Enzkreis',              type: 'Landkreis',  listings: 5, description: 'Entdecken Sie aktuelle Immobilienangebote im Enzkreis.' },
      'Calw':            { id: 'calw',                  name: 'Landkreis Calw',        type: 'Landkreis',  listings: 3, description: 'Entdecken Sie aktuelle Immobilienangebote im Landkreis Calw.' },
      'LK_Karlsruhe':    { id: 'landkreis-karlsruhe',   name: 'Landkreis Karlsruhe',   type: 'Landkreis',  listings: 11, description: 'Entdecken Sie aktuelle Immobilienangebote im Landkreis Karlsruhe.' },
      'Stadt_Karlsruhe': { id: 'karlsruhe-stadt',       name: 'Karlsruhe',             type: 'Stadtkreis', listings: 15, description: 'Entdecken Sie aktuelle Immobilienangebote in Karlsruhe.' },
      'Rastatt':         { id: 'rastatt',               name: 'Landkreis Rastatt',     type: 'Landkreis',  listings: 8, description: 'Entdecken Sie aktuelle Immobilienangebote im Landkreis Rastatt.' },
      'Baden-Baden':     { id: 'baden-baden',           name: 'Baden-Baden',           type: 'Stadtkreis', listings: 6, description: 'Entdecken Sie aktuelle Immobilienangebote in Baden-Baden.' }
    };

    /* Lookup nach data-district-id für Tooltip- und Panel-Inhalte */
    var districtData = {};
    Object.keys(districtMapping).forEach(function (origId) {
      var info = districtMapping[origId];
      districtData[info.id] = info;
    });

    /* SVG ist inline im DOM – einfach übernehmen.
       Vorteil: funktioniert auch lokal per file:// (kein Fetch nötig). */
    var svg = svgHost.querySelector('svg');
    if (!svg) {
      svgHost.innerHTML = '<p class="kreis-map-error">Karte konnte derzeit nicht geladen werden.</p>';
      return;
    }

    svg.setAttribute('role', 'group');
    svg.setAttribute('aria-label', 'Stadt- und Landkreise im Kerngebiet zwischen Baden-Baden und Heidelberg');
    svg.setAttribute('focusable', 'false');

    Object.keys(districtMapping).forEach(function (origId) {
      var info = districtMapping[origId];
      var g = svg.querySelector('g[id="' + origId + '"]');
      if (!g) return;
      g.classList.add('district');
      g.setAttribute('data-district-id', info.id);
      g.setAttribute('data-district-name', info.name);
      g.setAttribute('tabindex', '0');
      g.setAttribute('role', 'button');
      g.setAttribute('aria-label', info.name + ', ' + info.listings + ' gelistete Immobilien');
    });

    attachInteractions(svg);

    function attachInteractions(svg) {
      var supportsHover = window.matchMedia('(hover: hover)').matches;
      var activeId = null;
      var hideTimer = null;

      /* SVG-Malreihenfolge = DOM-Reihenfolge. Gehoverter / aktiver Kreis
         wird ans Ende gehängt, damit er garantiert über allen anderen liegt.
         Beim Zurücksetzen wird die ursprüngliche Reihenfolge wiederhergestellt. */
      var districtOrder = Array.prototype.slice.call(svg.querySelectorAll('.district'));

      function restoreDistrictLayerOrder() {
        districtOrder.forEach(function (g) {
          if (g && g.parentNode === svg) svg.appendChild(g);
        });
      }

      function bringDistrictToFront(g) {
        if (g && g.parentNode === svg) svg.appendChild(g);
      }

      function hasInfoPanel() {
        return !!(infoType && infoName && infoCount && infoDesc);
      }

      function defaultPanel() {
        if (!hasInfoPanel()) return;
        infoType.textContent = 'Region auswählen';
        infoName.textContent = 'Baden-Württemberg';
        infoCount.textContent = 'Kerngebiet zwischen Baden-Baden und Heidelberg';
        infoDesc.textContent = 'Bewege die Maus oder tippe auf einen Stadt- oder Landkreis, um aktuelle Immobilien zum Verkauf zu sehen.';
        if (infoCta) {
          infoCta.disabled = true;
          delete infoCta.dataset.districtId;
        }
      }

      function updatePanel(id) {
        if (!hasInfoPanel()) return;
        var d = id ? districtData[id] : null;
        if (!d) { defaultPanel(); return; }
        infoType.textContent = d.type;
        infoName.textContent = d.name;
        infoCount.textContent = d.listings === 1 ? '1 gelistete Immobilie' : d.listings + ' gelistete Immobilien';
        infoDesc.textContent = d.description;
        if (infoCta) {
          infoCta.disabled = false;
          infoCta.dataset.districtId = id;
        }
      }

      function setActive(id) {
        if (activeId === id) return;
        if (activeId) {
          var prev = svg.querySelector('[data-district-id="' + activeId + '"]');
          if (prev) prev.classList.remove('is-active');
        }
        if (id) {
          var cur = svg.querySelector('[data-district-id="' + id + '"]');
          if (cur) {
            cur.classList.add('is-active');
            bringDistrictToFront(cur);
          }
        } else {
          restoreDistrictLayerOrder();
        }
        activeId = id;
        updatePanel(id);
        if (!id) hideTooltip();
        else if (tooltip) showTooltip(id);
      }

      function showTooltip(id) {
        if (!tooltip) return;
        var d = districtData[id];
        if (!d) return;
        var typeEl = tooltip.querySelector('.kreis-map-tooltip-type');
        var nameEl = tooltip.querySelector('.kreis-map-tooltip-name');
        var countEl = tooltip.querySelector('.kreis-map-tooltip-count');
        if (typeEl) typeEl.textContent = d.type || '';
        if (nameEl) nameEl.textContent = d.name;
        var n = typeof d.listings === 'number' ? d.listings : parseInt(String(d.listings), 10) || 0;
        if (countEl) countEl.textContent = n === 1 ? '1 gelistete Immobilie' : n + ' gelistete Immobilien';
        tooltip.style.left = '';
        tooltip.style.top = '';
        tooltip.removeAttribute('hidden');
        window.clearTimeout(hideTimer);
        tooltip.setAttribute('data-visible', 'true');
      }

      function hideTooltip() {
        if (!tooltip) return;
        tooltip.removeAttribute('data-visible');
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(function () {
          tooltip.setAttribute('hidden', '');
        }, 220);
      }

      function getDistrict(target) {
        return target && target.closest ? target.closest('.district') : null;
      }

      /* Maus */
      svg.addEventListener('mouseover', function (e) {
        var d = getDistrict(e.target);
        if (!d) return;
        var id = d.getAttribute('data-district-id');
        setActive(id);
      });
      svg.addEventListener('mouseleave', function () {
        if (!supportsHover) return;
        setActive(null);
      });

      /* Tastatur-Fokus */
      svg.addEventListener('focusin', function (e) {
        var d = getDistrict(e.target);
        if (!d) return;
        var id = d.getAttribute('data-district-id');
        setActive(id);
      });
      svg.addEventListener('focusout', function (e) {
        if (e.relatedTarget && svg.contains(e.relatedTarget)) return;
        setActive(null);
      });

      /* Click + Enter/Space – aktuell nur Console-Log + Custom-Event,
         damit später eine echte Filter-/Routing-Logik andocken kann. */
      function selectDistrict(id) {
        var d = districtData[id];
        if (!d) return;
        // eslint-disable-next-line no-console
        console.log('Kreis ausgewählt:', { id: id, name: d.name });
        document.dispatchEvent(new CustomEvent('kreis-map:select', {
          detail: { id: id, district: d }
        }));
      }

      svg.addEventListener('click', function (e) {
        var d = getDistrict(e.target);
        if (!d) return;
        var id = d.getAttribute('data-district-id');
        setActive(id);
        selectDistrict(id);
      });
      svg.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var d = getDistrict(e.target);
        if (!d) return;
        e.preventDefault();
        var id = d.getAttribute('data-district-id');
        setActive(id);
        selectDistrict(id);
      });

      if (infoCta) {
        infoCta.addEventListener('click', function () {
          var id = infoCta.dataset.districtId;
          if (!id) return;
          selectDistrict(id);
        });
      }

      /* Touch: Tap außerhalb der Map setzt Auswahl zurück */
      if (!supportsHover) {
        document.addEventListener('click', function (e) {
          if (!root.contains(e.target)) setActive(null);
        });
      }

      defaultPanel();
    }
  }

  initKreisMap();

})();
