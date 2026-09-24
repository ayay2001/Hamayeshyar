/* HAMAYESH-YAR shared layout */
(function () {
  'use strict';

  function getUserSafe() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch (e) { return null; }
  }

  function dashboardFor(role) {
    return role === 'reviewer' ? 'reviewer-dashboard.html'
      : role === 'secretariat' ? 'secretariat-dashboard.html'
      : 'author-dashboard.html';
  }

  function currentPage() {
    return (location.pathname.split('/').pop() || 'index.html').toLowerCase();
  }

  function navLink(href, label, icon) {
    return '<a href="' + href + '"><i class="' + icon + '"></i><span>' + label + '</span></a>';
  }

  function renderHeader() {
    var host = document.getElementById('navbar-placeholder');
    if (!host) return;

    var user = getUserSafe();
    var account = user
      ? '<a class="layout-account" href="' + dashboardFor(user.role) + '"><i class="fa-solid fa-gauge-high"></i><span>پنل من</span></a>' +
        '<a class="layout-profile" href="profile.html" aria-label="پروفایل"><i class="fa-regular fa-circle-user"></i></a>'
      : '<a class="layout-account" href="login.html"><i class="fa-regular fa-user"></i><span>ورود</span></a>' +
        '<a class="layout-register" href="register.html">ثبت‌نام <i class="fa-solid fa-arrow-left"></i></a>';

    var roleNav = '';
    if (user) {
      if (user.role === 'author') {
        roleNav = navLink('author-dashboard.html', 'پنل نویسنده', 'fa-solid fa-file-pen');
      } else if (user.role === 'reviewer') {
        roleNav = navLink('reviewer-dashboard.html', 'پنل داور', 'fa-solid fa-user-check');
      } else if (user.role === 'secretariat') {
        roleNav = navLink('secretariat-dashboard.html', 'پنل دبیرخانه', 'fa-solid fa-building-columns') +
          navLink('add-reviewer.html', 'مدیریت داوران', 'fa-solid fa-user-plus') +
          navLink('schedule-admin.html', 'مدیریت برنامه', 'fa-solid fa-clock') +
          navLink('reports.html', 'گزارش‌ها', 'fa-solid fa-chart-column');
      }
    }

    host.innerHTML =
      '<div class="layout-shell">' +
        '<a class="layout-brand" href="index.html" aria-label="همایش یار">' +
  '<img src="images/logo.png" alt="همایش یار" class="layout-logo">' +
'</a>' +
        '<nav class="layout-nav" id="layoutNav" aria-label="منوی اصلی">' +
          navLink('index.html','خانه','fa-solid fa-house') +
          navLink('events.html','همایش‌ها','fa-solid fa-calendar-days') +
          navLink('schedule.html','برنامه همایش','fa-regular fa-clock') +
          navLink('authors.html','نویسندگان','fa-solid fa-users') +
          navLink('survey.html','نظرسنجی','fa-regular fa-message') +
          roleNav +
        '</nav>' +
        '<div class="layout-actions">' +
          (user ? '<a class="layout-notifications" href="notifications.html" aria-label="اعلان‌ها"><i class="fa-regular fa-bell"></i><span class="layout-notifications-badge" id="layoutNotificationBadge" hidden></span></a>' : '') +
          account +
          '<button class="layout-menu" id="layoutMenu" type="button" aria-label="باز کردن منو"><i class="fa-solid fa-bars"></i></button>' +
        '</div>' +
      '</div>';

    var page = currentPage();
    host.querySelectorAll('.layout-nav a').forEach(function (a) {
      if ((a.getAttribute('href') || '').toLowerCase() === page) a.classList.add('active');
    });

    var menu = document.getElementById('layoutMenu');
    var nav = document.getElementById('layoutNav');
    if (menu && nav) menu.addEventListener('click', function () {
      nav.classList.toggle('open');
      menu.classList.toggle('is-open');
    });

    window.addEventListener('scroll', function () {
      var header = document.querySelector('.main-header, .landing-header');
      if (header) header.classList.toggle('is-scrolled', window.scrollY > 12);
    }, { passive: true });
  }

  function renderFooter() {
    var host = document.getElementById('site-footer');
    if (!host) return;
    var user = getUserSafe();
    host.innerHTML =
      '<footer class="layout-footer">' +
        '<div class="layout-footer-glow"></div>' +
        '<div class="layout-footer-grid">' +
          '<div class="layout-footer-about">' +
            '<a class="layout-footer-brand" href="index.html">' +
  '<img src="images/logo.png" alt="همایش یار" class="layout-logo">' +
'</a>' +
            '<p>یک فضای یکپارچه برای مدیریت همایش، ارسال مقاله، داوری تخصصی و برنامه‌ریزی رویدادهای علمی.</p>' +
            '<div class="layout-socials">' +
              '<a href="#" aria-label="اینستاگرام"><i class="fa-brands fa-instagram"></i></a>' +
              '<a href="#" aria-label="لینکدین"><i class="fa-brands fa-linkedin-in"></i></a>' +
              '<a href="#" aria-label="تلگرام"><i class="fa-brands fa-telegram"></i></a>' +
            '</div>' +
          '</div>' +
          '<div><h4>دسترسی سریع</h4><a href="events.html">همایش‌ها</a><a href="schedule.html">برنامه همایش</a><a href="authors.html">نویسندگان</a><a href="survey.html">نظرسنجی</a></div>' +
          '<div><h4>حساب کاربری</h4>' +
            (user
              ? '<a href="profile.html">پروفایل</a><a href="' + dashboardFor(user.role) + '">پنل من</a><a href="#" onclick="logoutUser(); return false;">خروج</a>'
              : '<a href="login.html">ورود</a><a href="register.html">ثبت‌نام</a>') +
          '</div>' +
          '<div><h4>ارتباط با ما</h4><a href="mailto:uni@hamayesh.ir"><i class="fa-regular fa-envelope"></i> uni@hamayesh.ir</a><span><i class="fa-solid fa-phone"></i> ۰۶۱-۱۲۳۴۵۶۷۸</span><span><i class="fa-solid fa-location-dot"></i> ایران · مرکز همایش‌ها</span></div>' +
        '</div>' +
        '<div class="layout-footer-bottom"><span>© <span id="layoutYear"></span> همایش یار — تمامی حقوق محفوظ است.</span><a href="#top">بازگشت به بالا <i class="fa-solid fa-arrow-up"></i></a></div>' +
      '</footer>';
    var y = document.getElementById('layoutYear');
    if (y) y.textContent = new Date().getFullYear().toString().replace(/\d/g, function(d){return '۰۱۲۳۴۵۶۷۸۹'[d];});
  }

  window.checkLoginAndRedirect = function (target) {
    var user = getUserSafe();
    if (user) location.href = target;
    else {
      localStorage.setItem('postLoginRedirect', target);
      location.href = 'login.html';
    }
  };

  async function refreshNotificationBadge() {
    var badge = document.getElementById('layoutNotificationBadge');
    if (!badge || !window.apiRequest) return;
    try {
      var result = await window.apiRequest('notifications.php?limit=1&unread=1', { method: 'GET' });
      var count = result.ok ? Number(result.unreadCount || 0) : 0;
      badge.hidden = count < 1;
      badge.textContent = count > 99 ? '۹۹+' : String(count).replace(/\d/g, function(d){return '۰۱۲۳۴۵۶۷۸۹'[d];});
    } catch (e) {}
  }

  window.loadNavbar = renderHeader;
  window.loadSharedLayout = function () { renderHeader(); renderFooter(); };

  document.addEventListener('DOMContentLoaded', function () {
    renderHeader();
    renderFooter();
    refreshNotificationBadge();
  });
})();
