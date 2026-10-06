/* Tabarak Core - Sales funnel interactions (v1.11.0)
 * - Order modal filled with the chosen product
 * - Inline "best price" form
 * - Lead saved to wp-admin (Funnel Orders), then WhatsApp opens with a formatted order
 * - Sticky mobile bar, smooth scroll
 * Vanilla JS, no dependencies.
 */
(function () {
  'use strict';
  var T = window.tabarakFunnel || {};
  var STORE = 'tabarak_funnel_contact';
  var isMobile = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);

  /* ---------- Sticky mobile bar ---------- */
  var bar = document.querySelector('.tf-sticky');
  var hero = document.querySelector('.tf-hero');
  if (bar && hero && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (en) {
      bar.classList.toggle('is-visible', !en[0].isIntersecting);
    }).observe(hero);
  }

  /* ---------- Smooth scroll ---------- */
  document.querySelectorAll('.tf a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (id.length < 2) { return; }
      var t = document.querySelector(id);
      if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  /* ---------- Helpers ---------- */
  function money(n) {
    n = Math.round(Number(n) || 0);
    return (T.currency || 'KSh') + ' ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
  function normPhone(raw) {
    var d = String(raw || '').replace(/\D/g, '');
    if (d.length === 10 && d.charAt(0) === '0') { d = '254' + d.slice(1); }
    else if (d.length === 9 && (d.charAt(0) === '7' || d.charAt(0) === '1')) { d = '254' + d; }
    return /^254[17]\d{8}$/.test(d) ? d : '';
  }
  function makeRef() {
    var now = new Date();
    var p = function (x) { return (x < 10 ? '0' : '') + x; };
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', r = '';
    for (var i = 0; i < 4; i++) { r += chars.charAt(Math.floor(Math.random() * chars.length)); }
    return 'TBK-' + String(now.getFullYear()).slice(2) + p(now.getMonth() + 1) + p(now.getDate()) + '-' + r;
  }
  function val(form, name) {
    var el = form.elements[name];
    if (!el) { return ''; }
    if (el.length && el[0] && el[0].type === 'radio') {
      for (var i = 0; i < el.length; i++) { if (el[i].checked) { return el[i].value; } }
      return '';
    }
    if (el.type === 'checkbox') { return el.checked ? el.value : ''; }
    return String(el.value || '').trim();
  }
  function setErr(input, msg) {
    if (!input) { return; }
    var wrap = input.closest('.tf-f');
    if (!wrap) { return; }
    wrap.classList.toggle('has-error', !!msg);
    var e = wrap.querySelector('.tf-f__err');
    if (e) { e.textContent = msg || ''; }
  }
  function remember(form) {
    try {
      localStorage.setItem(STORE, JSON.stringify({
        name: val(form, 'name'), phone: val(form, 'phone'), location: val(form, 'location'), area: val(form, 'area')
      }));
    } catch (e) {}
  }
  function prefill(form) {
    var s = null;
    try { s = JSON.parse(localStorage.getItem(STORE)); } catch (e) {}
    if (!s) { return; }
    ['name', 'phone', 'location', 'area'].forEach(function (k) {
      var el = form.elements[k];
      if (el && !el.value && s[k]) { el.value = s[k]; }
    });
  }

  /* ---------- Validation ---------- */
  function validate(form) {
    var ok = true, first = null;
    var name = form.elements.name, phone = form.elements.phone, loc = form.elements.location;
    if (name) {
      var nv = name.value.trim();
      if (nv.length < 2) { setErr(name, 'Please enter your name.'); ok = false; first = first || name; } else { setErr(name, ''); }
    }
    if (phone) {
      if (!normPhone(phone.value)) { setErr(phone, 'Enter a valid Kenyan number, e.g. 0712 345 678.'); ok = false; first = first || phone; } else { setErr(phone, ''); }
    }
    if (loc && val(form, 'delivery') !== 'pickup') {
      if (!loc.value) { setErr(loc, 'Choose where we should deliver.'); ok = false; first = first || loc; } else { setErr(loc, ''); }
    } else if (loc) { setErr(loc, ''); }
    if (first) { first.focus(); }
    return ok;
  }

  /* ---------- Message ---------- */
  function buildMessage(form, product, ref, source) {
    var L = [];
    var qty = Math.max(1, parseInt(val(form, 'qty'), 10) || 1);
    if (product) {
      L.push('Hello Tabarak Electronics, I would like to order:');
      L.push('');
      L.push('*Order ref:* ' + ref);
      L.push('*Product:* ' + product.name);
      if (product.brand) { L.push('*Brand:* ' + product.brand); }
      L.push('*Price:* ' + product.priceLabel);
      L.push('*Quantity:* ' + qty);
      if (product.price && qty > 1) { L.push('*Total:* ' + money(product.price * qty)); }
    } else {
      L.push('Hello Tabarak Electronics, please help me with the best price.');
      L.push('');
      L.push('*Ref:* ' + ref);
      L.push('*Category:* ' + (T.label || ''));
      if (val(form, 'budget')) { L.push('*Budget:* ' + val(form, 'budget')); }
      if (val(form, 'need')) { L.push('*Looking for:* ' + val(form, 'need')); }
    }
    L.push('');
    L.push('*Name:* ' + val(form, 'name'));
    L.push('*Phone:* +' + normPhone(val(form, 'phone')));
    if (val(form, 'delivery') === 'pickup') {
      L.push('*Delivery:* I will pick up at the shop');
    } else if (val(form, 'location')) {
      L.push('*Deliver to:* ' + val(form, 'location') + (val(form, 'area') ? ', ' + val(form, 'area') : ''));
    }
    if (val(form, 'payment')) { L.push('*Payment:* ' + val(form, 'payment')); }
    if (form.elements.install) { L.push('*Installation:* ' + (val(form, 'install') ? 'Yes please' : 'Not needed')); }
    if (val(form, 'notes')) { L.push('*Note:* ' + val(form, 'notes')); }
    if (product && product.url) { L.push(''); L.push(product.url); }
    else if (source === 'quote' && T.pageUrl) { L.push(''); L.push(T.pageUrl); }
    return L.join('\n');
  }

  function waUrl(text) {
    return 'https://wa.me/' + (T.wa || '') + '?text=' + encodeURIComponent(text);
  }

  function saveLead(form, product, ref, source) {
    if (!T.ajaxUrl) { return; }
    var fd = new FormData();
    fd.append('action', 'tabarak_funnel_lead');
    fd.append('nonce', T.nonce || '');
    fd.append('ref', ref);
    fd.append('funnel', T.funnel || '');
    fd.append('source', source);
    ['name', 'phone', 'location', 'area', 'delivery', 'payment', 'install', 'notes', 'need', 'budget', 'qty', 'company'].forEach(function (k) {
      fd.append(k, val(form, k));
    });
    if (product) {
      fd.append('product_id', product.id || '');
      fd.append('price', product.priceLabel || '');
    }
    try {
      fetch(T.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin', keepalive: true });
    } catch (e) {}
  }

  function openWhatsApp(url) {
    if (isMobile) {
      setTimeout(function () { window.location.href = url; }, 150);
    } else {
      var w = window.open(url, '_blank', 'noopener');
      if (!w) { window.location.href = url; }
    }
  }

  /* ---------- Modal ---------- */
  var modal = document.getElementById('tf-order');
  var mForm = modal && modal.querySelector('form[data-tf-form="modal"]');
  var mDone = modal && modal.querySelector('.tf-done');
  var current = null, lastFocus = null;

  function updateTotal() {
    if (!mForm) { return; }
    var box = mForm.querySelector('[data-tf-total]');
    var qty = Math.max(1, parseInt(val(mForm, 'qty'), 10) || 1);
    if (current && current.price && qty > 1) {
      box.hidden = false;
      box.querySelector('strong').textContent = money(current.price * qty);
    } else { box.hidden = true; }
  }

  function setProgress(step) {
    if (!modal) { return; }
    modal.querySelectorAll('.tf-progress li').forEach(function (li, i) { li.classList.toggle('is-on', i < step); });
  }

  function openModal(product) {
    if (!modal || !mForm) { return false; }
    current = product;
    lastFocus = document.activeElement;
    var pBox = mForm.querySelector('[data-tf-product]');
    var gBox = mForm.querySelector('[data-tf-general]');
    if (product) {
      pBox.hidden = false; gBox.hidden = true;
      var img = pBox.querySelector('.tf-sum__img');
      if (product.img) { img.src = product.img; img.alt = product.name; img.hidden = false; } else { img.hidden = true; }
      pBox.querySelector('.tf-sum__brand').textContent = product.brand || '';
      pBox.querySelector('.tf-sum__name').textContent = product.name;
      pBox.querySelector('.tf-sum__price b').textContent = product.priceLabel;
      pBox.querySelector('.tf-sum__price del').textContent = product.regular || '';
      modal.querySelector('.tf-modal__title').textContent = 'Complete your order';
    } else {
      pBox.hidden = true; gBox.hidden = false;
      modal.querySelector('.tf-modal__title').textContent = 'Tell us what you need';
    }
    mForm.elements.qty.value = 1;
    mForm.hidden = false; mDone.hidden = true;
    setProgress(1);
    prefill(mForm);
    updateTotal();
    modal.hidden = false;
    document.documentElement.classList.add('tf-lock');
    requestAnimationFrame(function () { modal.classList.add('is-open'); });
    var first = mForm.querySelector(product ? 'input[name="name"]' : 'textarea[name="need"]');
    setTimeout(function () { if (first && !isMobile) { first.focus(); } }, 120);
    return true;
  }

  function closeModal() {
    if (!modal || modal.hidden) { return; }
    modal.classList.remove('is-open');
    document.documentElement.classList.remove('tf-lock');
    setTimeout(function () { modal.hidden = true; }, 200);
    if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target.closest('[data-tf-close]')) { e.preventDefault(); closeModal(); }
      var q = e.target.closest('[data-qty]');
      if (q) {
        var inp = mForm.elements.qty;
        inp.value = Math.min(20, Math.max(1, (parseInt(inp.value, 10) || 1) + parseInt(q.getAttribute('data-qty'), 10)));
        updateTotal();
      }
    });
    mForm.elements.qty.addEventListener('input', updateTotal);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { closeModal(); }
      if (e.key === 'Tab' && !modal.hidden) {
        var f = modal.querySelectorAll('button:not([hidden]), [href], input:not([type="hidden"]):not(.tf-hp), select, textarea, summary');
        var vis = [].filter.call(f, function (el) { return el.offsetParent !== null; });
        if (!vis.length) { return; }
        var a = vis[0], z = vis[vis.length - 1];
        if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
        else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
      }
    });
  }

  /* Open buttons (cards, hero, sticky bar). Without JS they fall back to wa.me links. */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-tf-order');
    if (!btn || !modal) { return; }
    var product = null;
    if (btn.getAttribute('data-name')) {
      product = {
        id: btn.getAttribute('data-id'),
        name: btn.getAttribute('data-name'),
        price: parseFloat(btn.getAttribute('data-price')) || 0,
        priceLabel: btn.getAttribute('data-price-label') || '',
        regular: btn.getAttribute('data-regular') || '',
        img: btn.getAttribute('data-img') || '',
        brand: btn.getAttribute('data-brand') || '',
        url: btn.getAttribute('data-url') || ''
      };
    }
    if (openModal(product)) { e.preventDefault(); }
  });

  /* Delivery choice: location only required for delivery */
  document.querySelectorAll('.tf-form').forEach(function (form) {
    form.addEventListener('change', function (e) {
      if (e.target.name === 'delivery') {
        var pick = val(form, 'delivery') === 'pickup';
        form.querySelectorAll('[name="location"], [name="area"]').forEach(function (el) {
          var w = el.closest('.tf-f'); if (w) { w.classList.toggle('is-muted', pick); }
        });
        setErr(form.elements.location, '');
      }
    });
    form.addEventListener('input', function (e) {
      if (e.target.closest('.tf-f.has-error')) { setErr(e.target, ''); }
    });
    if (form.getAttribute('data-tf-form') === 'quote') { prefill(form); }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!validate(form)) { return; }
      var source = form.getAttribute('data-tf-form');
      var product = source === 'modal' ? current : null;
      var ref = makeRef();
      var text = buildMessage(form, product, ref, source);
      var url = waUrl(text);
      remember(form);
      saveLead(form, product, ref, source);
      var btn = form.querySelector('.tf-submit');
      if (btn) { btn.classList.add('is-busy'); setTimeout(function () { btn.classList.remove('is-busy'); }, 1500); }
      openWhatsApp(url);
      if (source === 'modal') {
        mForm.hidden = true; mDone.hidden = false; setProgress(2);
        mDone.querySelector('.tf-done__ref').textContent = ref;
        mDone.querySelector('.tf-done__wa').href = url;
      } else {
        var done = form.querySelector('.tf-inline-done');
        if (done) {
          done.hidden = false;
          done.querySelector('.tf-done__ref').textContent = ref;
          done.querySelector('.tf-done__wa').href = url;
        }
      }
      if (window.gtag) { try { window.gtag('event', 'generate_lead', { method: 'whatsapp', funnel: T.funnel || '' }); } catch (x) {} }
      if (window.fbq) { try { window.fbq('track', 'Lead'); } catch (x) {} }
    });
  });
})();
