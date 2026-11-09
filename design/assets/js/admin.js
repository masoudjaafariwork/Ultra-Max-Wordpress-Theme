/*
 * Admin prototype frame: imitates the WordPress admin bar and side menu around the Ultra Max panel.
 * Load after data.js and shell.js. Body attribute data-admin="dashboard|settings|modules" marks the current page.
 */
(function () {
  'use strict';
  var UM = window.UM, ic = UM.icon;
  var current = document.body.getAttribute('data-admin');
  var slot = document.querySelector('[data-wp-chrome]');
  if (!slot) return;

  var wpMenu = [['gauge', 'پیشخوان'], ['file', 'نوشته‌ها'], ['image', 'رسانه'], ['file', 'برگه‌ها'], ['chat', 'دیدگاه‌ها'], ['store', 'ووکامرس'], ['box', 'محصولات'], ['chart', 'تجزیه و تحلیل']];
  var umMenu = [['dashboard', 'داشبورد', 'dashboard.html'], ['settings', 'تنظیمات', 'settings.html'], ['modules', 'ماژول‌ها', 'modules.html'], ['layouts', 'چیدمان‌ها', '#'], ['auth', 'ورود و پیامک', '#'], ['invoice', 'فاکتور و برچسب', '#'], ['wizard', 'جادوی راه‌اندازی', 'wizard.html'], ['help', 'راهنما', '#']];
  var after = [['palette', 'نمایش'], ['puzzle', 'افزونه‌ها'], ['users', 'کاربران'], ['wrench', 'ابزارها'], ['sliders', 'تنظیمات']];

  function item(i) { return '<li><a href="#">' + ic(i[0], 'i--sm') + '<span>' + i[1] + '</span></a></li>'; }
  var side = '<ul class="wp-menu">' + wpMenu.map(item).join('') +
    '<li class="wp-menu__sep"></li>' +
    '<li class="is-current"><a href="dashboard.html"><span class="wp-menu__um">U</span><span>الترا مکس</span></a><ul class="wp-sub">' +
    umMenu.map(function (m) { return '<li class="' + (m[0] === current ? 'is-current' : '') + '"><a href="' + m[2] + '">' + m[1] + '</a></li>'; }).join('') + '</ul></li>' +
    '<li class="wp-menu__sep"></li>' + after.map(item).join('') + '</ul>';

  var bar = '<div class="wp-bar"><div class="wp-bar__r"><button class="wp-bar__burger" type="button" data-wp-toggle aria-label="منو">' + ic('menu', 'i--sm') + '</button>' +
    '<a href="#" class="wp-bar__site">' + ic('home', 'i--sm') + '<span>فروشگاه نمونه</span></a><a href="#" class="hide-mobile">' + ic('chat', 'i--sm') + '<span class="num">۳</span></a><a href="#" class="hide-mobile">' + ic('plus', 'i--sm') + 'تازه</a></div>' +
    '<div class="wp-bar__l"><span class="hide-mobile">سلام، مدیر فروشگاه</span><span class="wp-bar__av">م</span></div></div>';

  var content = slot.innerHTML;
  slot.outerHTML = bar + '<div class="wp-shell"><nav class="wp-side" aria-label="منوی پیشخوان">' + side + '</nav><div class="wp-main">' + content + '</div></div>';

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-wp-toggle]')) document.body.classList.toggle('wp-side-open');
  });
})();
