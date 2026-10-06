/* Tabarak Electronics Child - UX layer v1.11.0 (vanilla JS, no dependencies) */
(function () {
  'use strict';
  var D = window.tabarakUx || { i18n: {} };
  var mqDesk = window.matchMedia ? window.matchMedia('(min-width: 992px)') : { matches: true };

  /* ---------- Mega navigation ---------- */
  var triggers = [].slice.call(document.querySelectorAll('.tux-nav__trigger'));
  function panelOf(btn) { return document.getElementById(btn.getAttribute('aria-controls')); }
  function closeAll(except) {
    triggers.forEach(function (b) {
      if (b === except) { return; }
      b.setAttribute('aria-expanded', 'false');
      var p = panelOf(b); if (p) { p.hidden = true; }
    });
  }
  function open(btn) { closeAll(btn); btn.setAttribute('aria-expanded', 'true'); var p = panelOf(btn); if (p) { p.hidden = false; } }
  function toggle(btn) { if (btn.getAttribute('aria-expanded') === 'true') { closeAll(); } else { open(btn); } }
  triggers.forEach(function (btn) {
    var item = btn.parentNode, timer;
    btn.addEventListener('click', function (e) { e.preventDefault(); toggle(btn); });
    item.addEventListener('mouseenter', function () { if (!mqDesk.matches) { return; } clearTimeout(timer); timer = setTimeout(function () { open(btn); }, 90); });
    item.addEventListener('mouseleave', function () { if (!mqDesk.matches) { return; } clearTimeout(timer); timer = setTimeout(function () { if (btn.getAttribute('aria-expanded') === 'true') { closeAll(); } }, 180); });
    btn.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); open(btn); var f = panelOf(btn) && panelOf(btn).querySelector('a'); if (f) { f.focus(); } }
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var openBtn = triggers.filter(function (b) { return b.getAttribute('aria-expanded') === 'true'; })[0];
      closeAll(); if (openBtn) { openBtn.focus(); }
    }
  });
  document.addEventListener('click', function (e) { if (!e.target.closest || !e.target.closest('.tux-nav__item--mega')) { closeAll(); } });

  /* ---------- Quantity stepper ---------- */
  function enhanceQty(root) {
    [].slice.call((root || document).querySelectorAll('.quantity')).forEach(function (q) {
      var input = q.querySelector('input.qty');
      if (!input || q.getAttribute('data-tux') || input.type === 'hidden') { return; }
      q.setAttribute('data-tux', '1');
      function mk(cls, label, txt, delta) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'tux-qty-btn ' + cls; b.setAttribute('aria-label', label); b.textContent = txt;
        b.addEventListener('click', function () {
          var step = parseFloat(input.step) || 1, min = input.min !== '' ? parseFloat(input.min) : 1, max = input.max !== '' ? parseFloat(input.max) : Infinity;
          var v = (parseFloat(input.value) || 0) + delta * step;
          if (v < min) { v = min; } if (v > max) { v = max; }
          input.value = v;
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        return b;
      }
      q.insertBefore(mk('tux-qty-btn--minus', (D.i18n && D.i18n.decrease) || 'Decrease quantity', '\u2212', -1), input);
      q.appendChild(mk('tux-qty-btn--plus', (D.i18n && D.i18n.increase) || 'Increase quantity', '+', 1));
    });
  }
  enhanceQty();
  if (window.jQuery) { window.jQuery(document.body).on('updated_cart_totals updated_wc_div', function () { enhanceQty(); }); }

  /* ---------- Sticky add-to-cart ---------- */
  var sticky = document.querySelector('.tux-sticky');
  var form = document.querySelector('.single-product form.cart');
  var realBtn = form && form.querySelector('.single_add_to_cart_button');
  if (sticky && form && realBtn) {
    sticky.hidden = false;
    var sBtn = sticky.querySelector('.tux-sticky__btn');
    if (form.classList.contains('variations_form')) {
      sBtn.addEventListener('click', function () { form.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
    } else {
      sBtn.addEventListener('click', function () { realBtn.click(); });
    }
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          var past = !en.isIntersecting && en.boundingClientRect.top < 0;
          sticky.classList.toggle('is-visible', past);
        });
      }, { threshold: 0 }).observe(form);
    }
  }
})();
