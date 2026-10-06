/* Tabarak Electronics front-end interactions. Vanilla JS, no dependencies. */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu toggle.
    var toggle = document.querySelector('.tabarak-menu-toggle');
    var nav = document.getElementById('site-navigation');
    if (toggle && nav) {
      toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }

    // Back to top.
    var toTop = document.querySelector('.tabarak-back-to-top');
    if (toTop) {
      var onScroll = function () {
        if (window.pageYOffset > 480) {
          toTop.classList.add('is-visible');
        } else {
          toTop.classList.remove('is-visible');
        }
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
      toTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  });
})();
