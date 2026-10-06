/* Tabarak Core - Sales funnel interactions */
(function () {
  'use strict';
  var bar = document.querySelector('.tf-sticky');
  var hero = document.querySelector('.tf-hero');
  if (bar && hero && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (en) {
      bar.classList.toggle('is-visible', \!en[0].isIntersecting);
    }).observe(hero);
  }
  document.querySelectorAll('.tf a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var t = document.querySelector(a.getAttribute('href'));
      if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });
})();
