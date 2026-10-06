/* Tabarak advanced shop UX: slider, carousels, live search, add-to-cart modal. */
(function () {
  'use strict';
  var D = window.tabarakShop || {};

  function ready(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

  /* ---- Hero slider ---- */
  function initSlider(){
    var slider = document.querySelector('.tabarak-slider');
    if(!slider) return;
    var slides = Array.prototype.slice.call(slider.querySelectorAll('.tabarak-slide'));
    if(slides.length < 2){ return; }
    var dots = slider.querySelector('.tabarak-slider__dots');
    var i = 0, timer = null, delay = parseInt(slider.getAttribute('data-autoplay'),10) || 6000;
    slides.forEach(function(s, idx){
      var b = document.createElement('button');
      b.className = 'tabarak-dot' + (idx===0?' is-active':'');
      b.setAttribute('aria-label','Go to slide '+(idx+1));
      b.addEventListener('click', function(){ go(idx); });
      if(dots) dots.appendChild(b);
    });
    function go(n){
      slides[i].classList.remove('is-active');
      if(dots) dots.children[i].classList.remove('is-active');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('is-active');
      if(dots) dots.children[i].classList.add('is-active');
    }
    function next(){ go(i+1); }
    function prev(){ go(i-1); }
    function start(){ stop(); timer = setInterval(next, delay); }
    function stop(){ if(timer){ clearInterval(timer); timer=null; } }
    var nx = slider.querySelector('.tabarak-slider__nav--next');
    var pv = slider.querySelector('.tabarak-slider__nav--prev');
    if(nx) nx.addEventListener('click', function(){ next(); start(); });
    if(pv) pv.addEventListener('click', function(){ prev(); start(); });
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    var sx = 0;
    slider.addEventListener('touchstart', function(e){ sx = e.touches[0].clientX; stop(); }, {passive:true});
    slider.addEventListener('touchend', function(e){
      var dx = e.changedTouches[0].clientX - sx;
      if(Math.abs(dx) > 40){ if(dx < 0){ next(); } else { prev(); } }
      start();
    }, {passive:true});
    start();
  }

  /* ---- Carousel arrows ---- */
  function initCarousels(){
    document.querySelectorAll('.tabarak-carousel').forEach(function(track){
      if(track.dataset.armed) return;
      track.dataset.armed = '1';
      var wrap = document.createElement('div');
      wrap.className = 'tabarak-carousel-wrap';
      track.parentNode.insertBefore(wrap, track);
      wrap.appendChild(track);
      ['prev','next'].forEach(function(dir){
        var btn = document.createElement('button');
        btn.className = 'tabarak-carousel__nav tabarak-carousel__nav--'+dir;
        btn.setAttribute('aria-label', dir==='prev'?'Scroll left':'Scroll right');
        btn.innerHTML = dir==='prev' ? '&lsaquo;' : '&rsaquo;';
        btn.addEventListener('click', function(){
          var amount = Math.round(track.clientWidth * 0.85);
          track.scrollBy({ left: dir==='prev' ? -amount : amount, behavior:'smooth' });
        });
        wrap.appendChild(btn);
      });
    });
  }

  /* ---- Category grid toggle ---- */
  function initCatToggle(){
    var btn = document.getElementById('tabarak-cat-toggle');
    var grid = document.getElementById('tabarak-cat-grid');
    if(!btn || !grid) return;
    btn.addEventListener('click', function(){
      var collapsed = grid.classList.toggle('is-collapsed');
      btn.textContent = collapsed ? btn.getAttribute('data-more') : btn.getAttribute('data-less');
    });
  }

  /* ---- Live AJAX search ---- */
  function initSearch(){
    var input = document.querySelector('[data-tabarak-search]');
    var box = document.getElementById('tabarak-search-results');
    if(!input || !box || !D.ajaxUrl) return;
    var t = null, controller = null;
    function hide(){ box.hidden = true; box.innerHTML=''; input.setAttribute('aria-expanded','false'); }
    function render(data){ var items=(data&&data.items)||[]; var more=(data&&data.more)||'';
      if(!items.length){ box.innerHTML = '<div class="tabarak-sr-empty">'+(D.i18n&&D.i18n.noResults||'No products found')+'</div>'; box.hidden=false; return; }
      var html = items.map(function(it){
        var ph = D.placeholder || '';
      var img = it.img ? '<img src="'+it.img+'" alt="" loading="lazy">' : (ph ? '<img src="'+ph+'" alt="" loading="lazy">' : '<span class="tabarak-sr-noimg"></span>');
        return '<a class="tabarak-sr-item" href="'+it.url+'">'+img+'<span class="tabarak-sr-meta"><span class="tabarak-sr-title">'+it.title+'</span>'+(it.cat?'<span class="tabarak-sr-cat">'+it.cat+'</span>':'')+'<span class="tabarak-sr-price">'+(it.price||'')+'</span></span></a>';
      }).join('');
      box.innerHTML = html + (more ? '<a class="tabarak-sr-all" href="'+more+'">'+((D.i18n&&D.i18n.seeAll)||'See all results')+'</a>' : ''); box.hidden=false; input.setAttribute('aria-expanded','true');
    }
    input.addEventListener('input', function(){
      var q = input.value.trim();
      if(t) clearTimeout(t);
      if(q.length < 2){ hide(); return; }
      t = setTimeout(function(){
        if(controller) controller.abort();
        controller = ('AbortController' in window) ? new AbortController() : null;
        box.innerHTML = '<div class="tabarak-sr-empty">'+(D.i18n&&D.i18n.searching||'Searching...')+'</div>'; box.hidden=false;
        var url = D.ajaxUrl+'?action=tabarak_search&nonce='+encodeURIComponent(D.searchNonce)+'&q='+encodeURIComponent(q);
        fetch(url, { credentials:'same-origin', signal: controller?controller.signal:undefined })
          .then(function(r){ return r.json(); })
          .then(function(res){ if(res&&res.success){ render(res.data||{}); } })
          .catch(function(){});
      }, 180);
    });
    document.addEventListener('click', function(e){ if(!box.contains(e.target) && e.target!==input){ hide(); } });
  }

  /* ---- Add to cart modal ---- */
  function showCartModal(){
    var existing = document.getElementById('tabarak-cart-modal');
    if(existing){ existing.classList.add('is-open'); return; }
    var m = document.createElement('div');
    m.id = 'tabarak-cart-modal';
    m.className = 'tabarak-modal is-open';
    m.innerHTML = '<div class="tabarak-modal__box" role="dialog" aria-modal="true">'+
      '<p class="tabarak-modal__msg">'+(D.i18n&&D.i18n.added||'Added to your cart')+'</p>'+
      '<div class="tabarak-modal__actions">'+
        '<a class="tabarak-btn tabarak-btn--primary" href="'+(D.checkoutUrl||'#')+'">'+(D.i18n&&D.i18n.checkout||'Checkout')+'</a>'+
        '<button class="tabarak-btn tabarak-btn--ghost" data-close>'+(D.i18n&&D.i18n.continue||'Continue shopping')+'</button>'+
      '</div></div>';
    document.body.appendChild(m);
    m.addEventListener('click', function(e){ if(e.target===m || e.target.hasAttribute('data-close')){ m.classList.remove('is-open'); } });
  }
  function initCartModal(){
    if(window.jQuery){
      window.jQuery(document.body).on('added_to_cart', function(){ showCartModal(); });
    }
  }


  /* ---- Category rail dropdown (mobile) ---- */
  function initCatRail(){
    var rail = document.querySelector('.tabarak-catrail');
    var btn = rail ? rail.querySelector('.tabarak-catrail__toggle') : null;
    if(!rail || !btn) return;
    btn.addEventListener('click', function(){
      var open = rail.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* ---- Off-canvas drawer (mobile menu + categories) ---- */
  function initDrawer(){
    var drawer = document.getElementById('tabarak-drawer');
    if(!drawer) return;
    var openers = document.querySelectorAll('.tabarak-menu-toggle, .tabarak-bottomnav__menu');
    var closers = drawer.querySelectorAll('.tabarak-drawer__close, .tabarak-drawer__backdrop');
    function open(){
      drawer.classList.add('is-open');
      drawer.setAttribute('aria-hidden','false');
      document.body.style.overflow='hidden';
      Array.prototype.forEach.call(openers, function(o){ o.setAttribute('aria-expanded','true'); });
    }
    function close(){
      drawer.classList.remove('is-open');
      drawer.setAttribute('aria-hidden','true');
      document.body.style.overflow='';
      Array.prototype.forEach.call(openers, function(o){ o.setAttribute('aria-expanded','false'); });
    }
    Array.prototype.forEach.call(openers, function(o){ o.addEventListener('click', function(e){ e.preventDefault(); open(); }); });
    Array.prototype.forEach.call(closers, function(c){ c.addEventListener('click', function(e){ e.preventDefault(); close(); }); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ close(); } });
  }

  /* ---- Back to top ---- */
  function initToTop(){
    var b = document.querySelector('.tabarak-back-to-top');
    if(!b) return;
    function s(){ if(window.pageYOffset>480){ b.classList.add('is-visible'); } else { b.classList.remove('is-visible'); } }
    window.addEventListener('scroll', s, {passive:true}); s();
    b.addEventListener('click', function(){ window.scrollTo({top:0,behavior:'smooth'}); });
  }

  /* ---- Filter panel toggle ---- */
  function initFilters(){
    var f = document.getElementById('tabarak-filters');
    if(f){
      var t = f.querySelector('.tabarak-filters__toggle');
      if(t){
        t.addEventListener('click', function(){
          var open = f.classList.toggle('is-open');
          t.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      }
    }
    // Instant filter: checking a brand (or availability) applies immediately.
    var form = document.querySelector('.tabarak-filters__panel[data-autosubmit]');
    if(form && form.dataset.tabAutobound !== '1'){
      form.dataset.tabAutobound = '1';
      var checks = form.querySelectorAll('input[type="checkbox"]');
      Array.prototype.forEach.call(checks, function(c){
        c.addEventListener('change', function(){
          form.classList.add('is-loading');
          if(typeof form.requestSubmit === 'function'){ form.requestSubmit(); } else { form.submit(); }
        });
      });
    }
  }

  /* ---- FAQ accordion ---- */
  function initFaq(){
    if(!document.body.classList.contains('tabarak-faq-page')) return;
    var root = document.querySelector('.entry-content') || document.querySelector('article .entry-content') || document.querySelector('.page-content');
    if(!root) return;
    var kids = Array.prototype.slice.call(root.children);
    var qs = kids.filter(function(n){ return /^H[234]$/.test(n.tagName); });
    if(!qs.length) return;
    qs.forEach(function(h){
      var acc = document.createElement('div'); acc.className = 'tabarak-acc';
      var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'tabarak-acc__q'; btn.innerHTML = h.innerHTML;
      var ans = document.createElement('div'); ans.className = 'tabarak-acc__a';
      var sib = h.nextElementSibling;
      while(sib && !/^H[234]$/.test(sib.tagName)){ var nx = sib.nextElementSibling; ans.appendChild(sib); sib = nx; }
      acc.appendChild(btn); acc.appendChild(ans);
      h.parentNode.insertBefore(acc, h);
      h.parentNode.removeChild(h);
      btn.addEventListener('click', function(){ acc.classList.toggle('is-open'); });
    });
  }

  /* ---- Light / dark theme toggle (persisted) ---- */
  function initTheme(){
    if(initTheme._bound){ return; }
    initTheme._bound = true;
    var root = document.documentElement;
    var KEY = 'tabarak-theme';
    function apply(mode){
      root.setAttribute('data-theme', mode);
      var pressed = mode === 'dark';
      Array.prototype.forEach.call(document.querySelectorAll('.tabarak-theme-toggle'), function(btn){
        btn.setAttribute('aria-pressed', pressed ? 'true' : 'false');
      });
    }
    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch(e){}
    var initial = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    apply(initial);
    var toggles = document.querySelectorAll('.tabarak-theme-toggle');
    Array.prototype.forEach.call(toggles, function(btn){
      btn.addEventListener('click', function(){
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        apply(next);
        try { localStorage.setItem(KEY, next); } catch(e){}
      });
    });
  }

  /* ---- Brand logo strip arrows ---- */
  function initBrandStrip(){
    document.querySelectorAll('.tabarak-brands__wrap').forEach(function(wrap){
      var track = wrap.querySelector('.tabarak-brands__track');
      if(!track) return;
      var prev = wrap.querySelector('.tabarak-brands__nav--prev');
      var next = wrap.querySelector('.tabarak-brands__nav--next');
      function step(dir){
        var amount = Math.round(track.clientWidth * 0.8);
        track.scrollBy({ left: dir === 'prev' ? -amount : amount, behavior:'smooth' });
      }
      if(prev) prev.addEventListener('click', function(){ step('prev'); });
      if(next) next.addEventListener('click', function(){ step('next'); });
      function sync(){
        var max = track.scrollWidth - track.clientWidth - 2;
        if(prev) prev.classList.toggle('is-hidden', track.scrollLeft <= 2);
        if(next) next.classList.toggle('is-hidden', track.scrollLeft >= max);
      }
      track.addEventListener('scroll', sync, {passive:true});
      window.addEventListener('resize', sync);
      sync();
    });
  }

  /* ---- Recently viewed (localStorage, no backend) ---- */
  function initRecent(){
    var KEY='tabarak_recent_v2';
    var list=[];
    try{ list=JSON.parse(localStorage.getItem(KEY))||[]; }catch(e){ list=[]; }
    if(!Array.isArray(list)) list=[];
    var cur=(D.recent&&D.recent.id)?D.recent:null;
    if(cur){
      list=list.filter(function(it){ return it&&it.id&&it.id!==cur.id; });
      list.unshift({id:cur.id,title:cur.title,url:cur.url,img:cur.img,price:cur.price,regular:cur.regular});
      list=list.slice(0,12);
      try{ localStorage.setItem(KEY,JSON.stringify(list)); }catch(e){}
    }
    var sec=document.getElementById('tabarak-recent');
    var track=document.getElementById('tabarak-recent-track');
    if(!sec||!track) return;
    var show=list.filter(function(it){ return it&&it.id&&(!cur||it.id!==cur.id); });
    if(show.length<1) return;
    var esc=function(t){ var d=document.createElement('div'); d.textContent=(t==null)?'':String(t); return d.innerHTML; };
    var ph=D.placeholder||'';
    var html='';
    show.forEach(function(it){
      var img=it.img||ph;
      var pr=(it.regular?('<del>'+esc(it.regular)+'</del> '):'')+esc(it.price||'');
      html+='<li class="tabarak-pcard">'
        +'<a class="tabarak-pcard__media" href="'+esc(it.url)+'"><img class="tabarak-pcard__phimg" src="'+esc(img)+'" alt="'+esc(it.title)+'" loading="lazy" width="400" height="400" /></a>'
        +'<div class="tabarak-pcard__body">'
        +'<a class="tabarak-pcard__title" href="'+esc(it.url)+'">'+esc(it.title)+'</a>'
        +'<div class="tabarak-pcard__price">'+pr+'</div>'
        +'</div></li>';
    });
    track.innerHTML=html;
    sec.hidden=false;
    if(typeof initCarousels==='function'){ try{ initCarousels(); }catch(e){} }
  }

  initTheme();
  ready(function(){ initTheme(); initSlider(); initCarousels(); initCatToggle(); initCatRail(); initDrawer(); initSearch(); initCartModal(); initToTop(); initFilters(); initFaq(); initBrandStrip(); initRecent(); });
})();
