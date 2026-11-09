/*
 * Ultra Max prototype shell.
 * Injects the icon sprite, header, footer, mobile bottom bar, drawers and shared behaviour.
 * Icons are drawn for this project (24px grid, 1.8px stroke). Load after data.js.
 */
(function () {
  'use strict';

  var UM = window.UM;
  var ic = UM.icon;
  var B = UM.base;

  /* ------------------------------------------------------------------ icons */
  var ICONS = {
    search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
    cart: '<path d="M3 4h2l2.2 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
    heart: '<path d="M12 20s-7.5-4.6-9-9.3C2 7.3 4.2 4.5 7.3 4.5c2 0 3.6 1.1 4.7 2.8 1.1-1.7 2.7-2.8 4.7-2.8 3.1 0 5.3 2.8 4.3 6.2C19.5 15.4 12 20 12 20z"/>',
    compare: '<path d="M7 4L3 8l4 4M3 8h13M17 12l4 4-4 4M21 16H8"/>',
    eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    'eye-off': '<path d="M3 3l18 18M10.6 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6C3.9 8.3 2 12 2 12s3.6 7 10 7c1.8 0 3.4-.5 4.8-1.3M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    close: '<path d="M6 6l12 12M18 6L6 18"/>',
    'chev-left': '<path d="M15 5l-7 7 7 7"/>',
    'chev-right': '<path d="M9 5l7 7-7 7"/>',
    'chev-down': '<path d="M5 9l7 7 7-7"/>',
    'chev-up': '<path d="M5 15l7-7 7 7"/>',
    'arrow-right': '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'arrow-up': '<path d="M12 20V5M6 11l6-6 6 6"/>',
    home: '<path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
    grid: '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
    list: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
    pin: '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    truck: '<path d="M2.5 6.5h10.5v9.5H2.5zM13 9.5h4.3l3.2 3.4v3.1H13"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
    shield: '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
    return: '<path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
    card: '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6 15h4"/>',
    cash: '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/><path d="M6 9.5v5M18 9.5v5"/>',
    star: '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
    share: '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="M8.2 10.8l7.6-4.1M8.2 13.2l7.6 4.1"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    trash: '<path d="M4 7h16M9 7V4.5h6V7M6 7l1 13h10l1-13M10 11v5M14 11v5"/>',
    bell: '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0"/>',
    filter: '<path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/>',
    sort: '<path d="M4 6h10M4 12h7M4 18h4M17 5v14M14 16l3 3 3-3"/>',
    check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.2"/>',
    alert: '<path d="M12 3.5l9.5 16.5h-19z"/><path d="M12 10v4.5M12 17.3v.2"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
    gift: '<rect x="3" y="8" width="18" height="4.5" rx="1"/><path d="M5 12.5V21h14v-8.5M12 8v13M12 8S10.5 3.5 8 3.5a2.25 2.25 0 0 0 0 4.5zM12 8s1.5-4.5 4-4.5a2.25 2.25 0 0 1 0 4.5z"/>',
    tag: '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
    percent: '<path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
    box: '<path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5L12 12l8.5-4.5M12 12v9"/>',
    logout: '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M14 8l4 4-4 4M18 12H9"/>',
    sliders: '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
    gear: '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
    download: '<path d="M12 4v11M7 10.5l5 5 5-5M4 20h16"/>',
    upload: '<path d="M12 16V5M7 9.5l5-5 5 5M4 20h16"/>',
    print: '<path d="M7 9V3.5h10V9M7 17H4.5A1.5 1.5 0 0 1 3 15.5v-5A1.5 1.5 0 0 1 4.5 9h15a1.5 1.5 0 0 1 1.5 1.5v5a1.5 1.5 0 0 1-1.5 1.5H17"/><path d="M7 14h10v6.5H7z"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6l8.5 7 8.5-7"/>',
    chat: '<path d="M4 5h16v11H9l-5 4z"/><path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/>',
    send: '<path d="M21 3L3 10.5l7 3 3 7z"/><path d="M10 13.5L21 3"/>',
    insta: '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17 7h.01"/>',
    phone: '<path d="M5 3.5h3.5l1.8 4.5-2.3 1.5a11 11 0 0 0 6.5 6.5l1.5-2.3 4.5 1.8V19a1.5 1.5 0 0 1-1.6 1.5C10.7 20 4 13.3 3.5 5.1A1.5 1.5 0 0 1 5 3.5z"/>',
    mobile: '<rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M11 18h2"/>',
    lock: '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
    file: '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/>',
    receipt: '<path d="M5.5 3h13v18l-2.2-1.5L14 21l-2-1.5L10 21l-2.3-1.5L5.5 21z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
    zap: '<path d="M13 2.5L4.5 13.5H12l-1 8 8.5-11H12z"/>',
    puzzle: '<path d="M10 3.5a2 2 0 0 1 4 0V6h4v4h-1.5a2 2 0 0 0 0 4H18v4h-4v-1.5a2 2 0 0 0-4 0V18H6v-4h1.5a2 2 0 0 0 0-4H6V6h4z"/>',
    palette: '<path d="M12 3a9 9 0 0 0 0 18c1.4 0 2-.9 2-1.8 0-1.4-1.2-1.7-1.2-3 0-1 .8-1.7 1.8-1.7H17a4 4 0 0 0 4-4C21 6.3 17 3 12 3z"/><path d="M7.5 11h.01M10 7h.01M15 7.5h.01"/>',
    code: '<path d="M8 7l-5 5 5 5M16 7l5 5-5 5M13.5 4.5l-3 15"/>',
    activity: '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5v.7M12 17.2v.2"/>',
    store: '<path d="M3.5 9l1.5-5h14l1.5 5M3.5 9a2.8 2.8 0 0 0 5.7 0 2.8 2.8 0 0 0 5.6 0 2.8 2.8 0 0 0 5.7 0M5 11.5V20h14v-8.5M10 20v-5h4v5"/>',
    play: '<path d="M8 5v14l11-7z"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="M21 16l-5.5-5.5L5 20"/>',
    ruler: '<path d="M3 16.5L16.5 3 21 7.5 7.5 21z"/><path d="M7 12.5l2 2M10 9.5l2 2M13 6.5l2 2"/>',
    calendar: '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    sparkles: '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
    fire: '<path d="M12 21c-4 0-7-2.8-7-6.6 0-3.4 2.6-5.4 3.6-8.4.4 2 1.5 3 2.4 3.4C11 6 12.5 3.8 14.5 3c-.4 2.6.6 4.4 2 6 1.3 1.4 2.5 3 2.5 5.4 0 3.8-3 6.6-7 6.6z"/>',
    headset: '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13.5" width="4" height="6" rx="1.5"/><rect x="17" y="13.5" width="4" height="6" rx="1.5"/><path d="M19 19.5c0 1-1.5 2-4 2h-2"/>',
    verified: '<path d="M12 2.8l2.4 1.7 2.9-.1.9 2.8 2.3 1.8-.9 2.8.9 2.8-2.3 1.8-.9 2.8-2.9-.1L12 21.2l-2.4-1.7-2.9.1-.9-2.8-2.3-1.8.9-2.8-.9-2.8 2.3-1.8.9-2.8 2.9.1z"/><path d="M8.5 12l2.4 2.4 4.6-4.8"/>',
    history: '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5M3.5 4v4.5H8"/><path d="M12 7.5V12l3 2"/>',
    layers: '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
    copy: '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5.5A1.5 1.5 0 0 0 14.5 4h-9A1.5 1.5 0 0 0 4 5.5v9A1.5 1.5 0 0 0 5.5 16H8"/>',
    link: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
    key: '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l2.5 2.5M14.5 8.5l2 2"/>',
    server: '<rect x="3.5" y="4" width="17" height="7" rx="1.5"/><rect x="3.5" y="13" width="17" height="7" rx="1.5"/><path d="M7 7.5h.01M7 16.5h.01"/>',
    chart: '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 4.8a3.5 3.5 0 0 1 0 6.4M18.5 14.5c1.9.8 3 2.7 3 5.5"/>',
    wrench: '<path d="M14.5 4.5a4.5 4.5 0 0 0-5.6 5.6L3.5 15.5l5 5 5.4-5.4a4.5 4.5 0 0 0 5.6-5.6l-2.8 2.8-3-1-1-3z"/>',
    gauge: '<path d="M3.5 17a8.5 8.5 0 1 1 17 0"/><path d="M12 17l4-5"/>',
    globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
    'thumb-up': '<path d="M7 11v9H4v-9zM7 11l4-7.5c1.3 0 2.5 1 2.3 2.4L12.8 10H19a2 2 0 0 1 2 2.3l-1.2 6A2 2 0 0 1 17.8 20H7"/>',
    'thumb-down': '<path d="M7 13V4H4v9zM7 13l4 7.5c1.3 0 2.5-1 2.3-2.4L12.8 14H19a2 2 0 0 0 2-2.3l-1.2-6A2 2 0 0 0 17.8 4H7"/>',
    dots: '<path d="M5 12h.01M12 12h.01M19 12h.01"/>',
    scan: '<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/>',
    building: '<path d="M4 21V4h11v17M15 9h5v12M2.5 21h19M8 8h3M8 12h3M8 16h3"/>'
  };
  var sprite = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">' +
    Object.keys(ICONS).map(function (k) { return '<symbol id="i-' + k + '" viewBox="0 0 24 24">' + ICONS[k] + '</symbol>'; }).join('') + '</svg>';
  document.body.insertAdjacentHTML('afterbegin', sprite);

  var body = document.body;
  var shell = body.getAttribute('data-shell');
  var nav = body.getAttribute('data-nav') || '';

  /* ------------------------------------------------------------------ logo */
  UM.logo = function (cls) {
    return '<a class="logo ' + (cls || '') + '" href="' + B + 'home.html" aria-label="صفحه اصلی">' +
      '<svg class="logo__mark" viewBox="0 0 40 40" aria-hidden="true"><defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--um-brand-500)"/><stop offset="1" stop-color="var(--um-brand-800)"/></linearGradient></defs>' +
      '<rect width="40" height="40" rx="12" fill="url(#lg)"/><path d="M12 13v8a8 8 0 0 0 16 0v-8" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/><circle cx="28" cy="29" r="3.2" fill="var(--um-accent-500)"/></svg>' +
      '<span class="logo__text">الترا<b>مکس</b></span></a>';
  };

  /* ------------------------------------------------------------------ header */
  var catList = UM.categories.map(function (c, i) {
    return '<li><a href="' + B + 'plp.html" class="' + (i === 1 ? 'is-active' : '') + '" data-cat="' + i + '"><img src="' + UM.img(c.icon) + '" alt="" width="28" height="28">' + c.name + ic('chev-left', 'i--sm') + '</a></li>';
  }).join('');
  function catPanel(i) {
    var c = UM.categories[i];
    return '<div class="mega__head"><a class="link" href="' + B + 'plp.html">همه کالاهای ' + c.name + ic('chev-left', 'i--sm') + '</a></div>' +
      '<div class="mega__cols">' + c.subs.map(function (s, j) {
        return '<div class="mega__col"><a class="mega__title" href="' + B + 'plp.html">' + s + ic('chev-left', 'i--sm') + '</a>' +
          ['پرفروش‌ها', 'تازه‌ها', 'اقتصادی', 'برندهای محبوب'].slice(0, 2 + (j % 3)).map(function (t) { return '<a href="' + B + 'plp.html">' + s + ' ' + t + '</a>'; }).join('') + '</div>';
      }).join('') + '</div>' +
      '<a class="mega__promo" href="' + B + 'plp.html"><img src="' + UM.img(c.icon) + '" alt=""><span><b>تا ۳۰٪ تخفیف</b><small>' + c.name + '</small></span></a>';
  }

  function header() {
    if (shell === 'checkout') {
      return '<header class="hdr hdr--min"><div class="container hdr__min">' +
        '<a class="icon-btn" href="' + B + 'cart.html" aria-label="بازگشت به سبد">' + ic('arrow-right') + '</a>' + UM.logo() +
        '<span class="hdr__secure">' + ic('lock', 'i--sm') + 'پرداخت امن</span></div></header>';
    }
    return '' +
      '<div class="topbar" id="topbar"><div class="container topbar__in">' +
      '<span>' + ic('gift', 'i--sm') + '<b>جشنواره پاییز:</b> ارسال رایگان سفارش‌های بالای ۵۰۰ هزار تومان تا ۲۵ آبان</span>' +
      '<a class="topbar__cta" href="' + B + 'plp.html">مشاهده</a>' +
      '<button class="topbar__close" type="button" data-close-topbar aria-label="بستن">' + ic('close', 'i--sm') + '</button></div></div>' +
      '<header class="hdr" id="hdr">' +
      '<div class="container hdr__main">' +
      '<button class="icon-btn hdr__burger hide-desktop" type="button" data-open="drawer-menu" aria-label="منو">' + ic('menu') + '</button>' +
      UM.logo('hdr__logo') +
      '<div class="hdr__search" data-open="search-panel" role="search"><span class="hdr__search-ic">' + ic('search') + '</span>' +
      '<span class="hdr__search-ph">جستجو در <b>الترا مکس</b></span></div>' +
      '<div class="hdr__actions">' +
      '<a class="btn btn--neutral btn--sm hdr__login hide-mobile" href="' + B + 'login.html">' + ic('user', 'i--sm') + 'ورود | ثبت‌نام</a>' +
      '<a class="icon-btn hdr__acc hide-desktop hide-tablet" href="' + B + 'login.html" aria-label="ورود">' + ic('user') + '</a>' +
      '<span class="hdr__sep hide-mobile"></span>' +
      '<a class="icon-btn hide-mobile" href="' + B + 'account.html" aria-label="علاقه‌مندی‌ها">' + ic('heart') + '</a>' +
      '<button class="icon-btn hide-mobile" type="button" data-open="mini-cart" aria-label="سبد خرید">' + ic('cart') + '<span class="count num">۴</span></button>' +
      '</div></div>' +
      '<nav class="hdr__nav" aria-label="منوی اصلی"><div class="container hdr__nav-in">' +
      '<div class="mega"><button class="mega__btn" type="button">' + ic('menu') + 'دسته‌بندی کالاها</button>' +
      '<div class="mega__panel"><ul class="mega__list">' + catList + '</ul><div class="mega__body" id="mega-body">' + catPanel(1) + '</div></div></div>' +
      '<span class="hdr__nav-sep"></span>' +
      '<a href="' + B + 'home.html#deals">' + ic('fire', 'i--sm') + 'شگفت‌انگیزها</a>' +
      '<a href="' + B + 'plp.html">' + ic('store', 'i--sm') + 'سوپرمارکت</a>' +
      '<a href="' + B + 'plp.html">' + ic('percent', 'i--sm') + 'تخفیف‌ها و پیشنهادها</a>' +
      '<a href="' + B + 'plp.html">' + ic('star', 'i--sm') + 'پرفروش‌ترین‌ها <span class="badge badge--hot">داغ</span></a>' +
      '<a href="' + B + 'login.html#track">' + ic('box', 'i--sm') + 'پیگیری سفارش</a>' +
      '<button class="hdr__city" type="button" data-open="city-modal">' + ic('pin', 'i--sm') + 'ارسال به: <b>تهران</b>' + ic('chev-down', 'i--sm') + '</button>' +
      '</div></nav>' +
      '<div class="hdr__mcity hide-desktop hide-tablet"><button type="button" data-open="city-modal">' + ic('pin', 'i--sm') + 'ارسال به تهران، خیابان ولیعصر' + ic('chev-left', 'i--sm') + '</button></div>' +
      '</header>';
  }

  /* ------------------------------------------------------------------ footer */
  function footer() {
    if (shell === 'checkout') {
      return '<footer class="ftr ftr--min"><div class="container">' +
        '<p>' + ic('shield', 'i--sm') + 'اطلاعات پرداخت شما فقط در درگاه بانکی وارد می‌شود. استفاده از این فروشگاه به معنی پذیرش قوانین آن است.</p></div></footer>';
    }
    var cols = [
      ['راهنمای خرید', ['نحوه ثبت سفارش', 'رویه ارسال سفارش', 'شیوه‌های پرداخت', 'پیگیری سفارش']],
      ['خدمات مشتریان', ['پاسخ به پرسش‌های متداول', 'رویه بازگرداندن کالا', 'شرایط استفاده', 'حریم خصوصی']],
      ['با ما', ['درباره فروشگاه', 'تماس با ما', 'وبلاگ', 'همکاری با ما']]
    ];
    return '<footer class="ftr">' +
      '<div class="container">' +
      '<div class="ftr__top">' + UM.logo() +
      '<div class="ftr__contact"><span>' + ic('headset', 'i--sm') + 'پشتیبانی: شماره تماس فروشگاه شما</span><span class="hide-mobile">|</span><span>هر روز ساعت ۹ تا ۲۱</span></div>' +
      '<button class="btn btn--neutral btn--sm" type="button" data-scroll-top>بازگشت به بالا' + ic('chev-up', 'i--sm') + '</button></div>' +
      '<ul class="ftr__perks">' +
      [['truck', 'ارسال سریع', 'به سراسر کشور'], ['cash', 'پرداخت در محل', 'در شهرهای منتخب'], ['return', '۷ روز ضمانت بازگشت', 'بدون پرسش'], ['verified', 'ضمانت اصل بودن کالا', 'از برند یا نماینده رسمی'], ['headset', 'پشتیبانی ۷ روز هفته', 'تلفن و گفتگو']]
        .map(function (p) { return '<li><span class="ftr__perk-ic">' + ic(p[0], 'i--lg') + '</span><span><b>' + p[1] + '</b><small>' + p[2] + '</small></span></li>'; }).join('') + '</ul>' +
      '<div class="ftr__grid">' +
      cols.map(function (c) { return '<div class="ftr__col"><h3>' + c[0] + '</h3><ul>' + c[1].map(function (l) { return '<li><a href="#">' + l + '</a></li>'; }).join('') + '</ul></div>'; }).join('') +
      '<div class="ftr__col ftr__col--wide"><h3>از تخفیف‌ها و جدیدترین‌ها باخبر شوید</h3>' +
      '<form class="ftr__news" onsubmit="return false"><input class="input" type="email" placeholder="ایمیل یا شماره موبایل" aria-label="ایمیل یا شماره موبایل"><button class="btn" type="submit">ثبت</button></form>' +
      '<h3 class="ftr__social-title">ما را در شبکه‌های اجتماعی دنبال کنید</h3><div class="ftr__social">' +
      ['insta', 'send', 'chat', 'globe'].map(function (s) { return '<a class="icon-btn" href="#" aria-label="شبکه اجتماعی">' + ic(s) + '</a>'; }).join('') + '</div></div></div>' +
      '<div class="ftr__about"><div><h3>فروشگاه اینترنتی الترا مکس، خرید آسان و مطمئن</h3><p class="muted small">این متن نمونه است و در قالب نهایی از پنل تنظیمات قابل ویرایش است. فروشگاه شما می‌تواند در این بخش خلاصه‌ای از خدمات، ضمانت‌ها و شیوه ارسال خود را برای خریداران و موتورهای جستجو بنویسد.</p></div>' +
      '<div class="ftr__trust"><span class="trustmark">نماد اعتماد الکترونیکی</span><span class="trustmark">نشان ملی ثبت</span><span class="trustmark">مجوز کسب‌وکار</span></div></div>' +
      '<div class="ftr__apps"><span class="small bold">اپلیکیشن فروشگاه:</span><span class="ftr__app">' + ic('mobile', 'i--sm') + 'نصب روی صفحه گوشی</span><span class="ftr__app">' + ic('download', 'i--sm') + 'دریافت مستقیم</span></div>' +
      '</div>' +
      '<div class="ftr__copy"><div class="container">برای استفاده از مطالب این فروشگاه، ذکر منبع کافی است. تمام حقوق برای صاحب فروشگاه محفوظ است. ۱۴۰۵</div></div>' +
      '</footer>';
  }

  /* ------------------------------------------------------------------ bottom nav (HDR-10) */
  function bottomNav() {
    var items = [['home', 'خانه', 'home.html', 'home'], ['grid', 'دسته‌بندی', '#', 'cats'], ['cart', 'سبد خرید', 'cart.html', 'cart'], ['heart', 'علاقه‌مندی', 'account.html', 'wish'], ['user', 'حساب من', 'account.html', 'account']];
    return '<nav class="bnav hide-desktop" aria-label="ناوبری پایین">' + items.map(function (it) {
      var attrs = it[3] === 'cats' ? ' data-open="drawer-menu"' : '';
      return '<a href="' + (it[2] === '#' ? '#' : B + it[2]) + '"' + attrs + ' class="' + (nav === it[3] ? 'is-active' : '') + '">' +
        '<span class="bnav__ic">' + ic(it[0], 'i--lg') + (it[3] === 'cart' ? '<span class="count num">۴</span>' : '') + '</span>' + it[1] + '</a>';
    }).join('') + '</nav>';
  }

  /* ------------------------------------------------------------------ drawers & overlays */
  function cartLine(p, q) {
    return '<li class="mc__item"><img src="' + UM.img(p.img) + '" alt=""><div class="grow"><a class="mc__title" href="' + B + 'pdp.html">' + p.title + '</a>' +
      '<div class="xsmall faint">' + (p.colors ? '<span class="swatch swatch--sm" style="display:inline-block;vertical-align:middle;background:' + p.colors[0] + '"></span> مشکی · ' : '') + 'گارانتی ۱۸ ماهه</div>' +
      '<div class="row row--between"><div class="qty qty--sm" data-qty><button type="button" data-inc aria-label="افزایش">' + ic('plus', 'i--sm') + '</button><output class="num">' + UM.fa(q) + '</output><button type="button" data-dec aria-label="کاهش">' + ic(q > 1 ? 'minus' : 'trash', 'i--sm') + '</button></div>' +
      '<span class="bold num">' + UM.money(p.price * q) + ' <small class="faint">تومان</small></span></div></div></li>';
  }
  function drawers() {
    var cats = UM.categories.map(function (c) {
      return '<details class="acc"><summary><img src="' + UM.img(c.icon) + '" alt="" width="28" height="28">' + c.name + ic('chev-down', 'i--sm') + '</summary><ul>' +
        '<li><a href="' + B + 'plp.html" class="link">همه کالاهای ' + c.name + '</a></li>' + c.subs.map(function (s) { return '<li><a href="' + B + 'plp.html">' + s + '</a></li>'; }).join('') + '</ul></details>';
    }).join('');
    var sp = UM.pick([1, 3, 10]);
    return '' +
      '<div class="overlay" data-overlay></div>' +
      '<aside class="drawer drawer--right" id="drawer-menu" aria-label="منوی موبایل"><div class="drawer__head">' + UM.logo() + '<button class="icon-btn" type="button" data-close aria-label="بستن">' + ic('close') + '</button></div>' +
      '<div class="drawer__body mnav"><a class="mnav__login" href="' + B + 'login.html">' + ic('user') + '<span><b>ورود یا ثبت‌نام</b><small>برای پیگیری سفارش‌ها وارد شوید</small></span>' + ic('chev-left', 'i--sm') + '</a>' +
      '<div class="mnav__quick"><a href="' + B + 'home.html#deals">' + ic('fire') + 'شگفت‌انگیز</a><a href="' + B + 'plp.html">' + ic('percent') + 'تخفیف‌ها</a><a href="' + B + 'plp.html">' + ic('star') + 'پرفروش‌ها</a><a href="' + B + 'login.html#track">' + ic('box') + 'پیگیری</a></div>' +
      '<h3 class="mnav__h">دسته‌بندی کالاها</h3>' + cats + '</div></aside>' +

      '<aside class="drawer drawer--left" id="mini-cart" aria-label="سبد خرید"><div class="drawer__head"><h2>سبد خرید <span class="badge num">۴ کالا</span></h2><button class="icon-btn" type="button" data-close aria-label="بستن">' + ic('close') + '</button></div>' +
      '<div class="drawer__body"><div class="freeship"><div class="freeship__text">' + ic('truck', 'i--sm') + '<span>ارسال این سفارش <b>رایگان</b> است</span></div><div class="progress progress--success"><i style="width:100%"></i></div></div>' +
      '<ul class="mc">' + cartLine(sp[0], 1) + cartLine(sp[1], 2) + cartLine(sp[2], 1) + '</ul></div>' +
      '<div class="drawer__foot"><div class="row row--between small"><span class="muted">سود شما از این خرید</span><b class="num" style="color:var(--um-primary)">۶٬۵۷۰٬۰۰۰ تومان</b></div>' +
      '<div class="row row--between" style="margin:6px 0 12px"><span class="bold">جمع سبد خرید</span><b class="num" style="font-size:18px">۲۷٬۶۳۰٬۰۰۰ <small class="faint">تومان</small></b></div>' +
      '<div class="row"><a class="btn btn--neutral grow" href="' + B + 'cart.html">مشاهده سبد</a><a class="btn grow" href="' + B + 'checkout.html">ثبت سفارش</a></div></div></aside>' +

      '<div class="search-panel" id="search-panel" role="dialog" aria-label="جستجو"><div class="search-panel__box">' +
      '<div class="search-panel__field"><button class="icon-btn hide-desktop" type="button" data-close aria-label="بازگشت">' + ic('arrow-right') + '</button>' + ic('search') +
      '<input class="grow" type="search" value="هدفون" aria-label="عبارت جستجو"><button class="icon-btn" type="button" data-close aria-label="بستن">' + ic('close', 'i--sm') + '</button></div>' +
      '<div class="search-panel__body">' +
      '<div class="sp-sec"><div class="row row--between"><h4>' + ic('history', 'i--sm') + 'جستجوهای اخیر</h4><button class="icon-btn" type="button" aria-label="پاک کردن">' + ic('trash', 'i--sm') + '</button></div>' +
      '<div class="row row--wrap"><a class="chip" href="#">گوشی نوویکس' + ic('chev-left', 'i--sm') + '</a><a class="chip" href="#">کرم آبرسان' + ic('chev-left', 'i--sm') + '</a><a class="chip" href="#">کتری برقی' + ic('chev-left', 'i--sm') + '</a></div></div>' +
      '<div class="sp-sec"><h4>' + ic('fire', 'i--sm') + 'جستجوهای پرطرفدار</h4><div class="row row--wrap">' + ['هدفون بی‌سیم', 'ساعت هوشمند', 'قهوه', 'کفش پیاده‌روی', 'عطر زنانه', 'لپ‌تاپ'].map(function (t) { return '<a class="chip" href="#">' + t + '</a>'; }).join('') + '</div></div>' +
      '<div class="sp-sec"><h4>' + ic('search', 'i--sm') + 'نتیجه‌های «هدفون»</h4><ul class="sp-res">' +
      '<li><a href="' + B + 'plp.html">' + ic('grid', 'i--sm') + '<span>هدفون در دسته <b>کالای دیجیتال</b></span></a></li>' +
      '<li><a href="' + B + 'plp.html">' + ic('tag', 'i--sm') + '<span>برند <b>لومو</b> <span class="faint ltr">Lumo</span></span></a></li>' +
      [3, 13].map(function (id) { var p = UM.byId(id); return '<li><a href="' + B + 'pdp.html"><img src="' + UM.img(p.img) + '" alt="" width="44" height="44"><span class="grow">' + p.title + '</span><b class="num small">' + UM.money(p.price) + '</b></a></li>'; }).join('') +
      '</ul><a class="link small" href="' + B + 'plp.html">مشاهده همه ۴۸ نتیجه' + ic('chev-left', 'i--sm') + '</a></div>' +
      '</div></div></div>' +

      '<div class="modal" id="city-modal" style="--modal-w:440px" role="dialog" aria-label="انتخاب شهر"><div class="drawer__head"><h2>انتخاب شهر برای ارسال</h2><button class="icon-btn" type="button" data-close aria-label="بستن">' + ic('close') + '</button></div>' +
      '<div class="drawer__body stack"><p class="muted small">زمان و هزینه ارسال کالاها بر اساس شهر شما نمایش داده می‌شود.</p>' +
      '<div class="field"><label class="field__label">استان</label><select class="select"><option>تهران</option><option>اصفهان</option><option>خراسان رضوی</option><option>فارس</option></select></div>' +
      '<div class="field"><label class="field__label">شهر</label><select class="select"><option>تهران</option><option>شهریار</option><option>اسلامشهر</option></select></div>' +
      '<button class="btn btn--block" type="button" data-close>تأیید</button></div></div>' +

      '<div class="modal" id="quickview" style="--modal-w:860px" role="dialog" aria-label="نمایش سریع"><div class="drawer__head"><h2>نمایش سریع</h2><button class="icon-btn" type="button" data-close aria-label="بستن">' + ic('close') + '</button></div><div class="drawer__body" id="quickview-body"></div></div>' +

      '<div class="toast" id="toast" role="status">' + ic('check') + '<span id="toast-text"></span><a href="' + B + 'cart.html">مشاهده سبد</a></div>';
  }

  /* ------------------------------------------------------------------ floating contact (CORE-11) */
  function floating() {
    return '<div class="fab" id="fab">' +
      '<div class="fab__list">' +
      [['chat', 'واتساپ'], ['send', 'تلگرام'], ['chat', 'ایتا'], ['chat', 'بله'], ['phone', 'تماس تلفنی']].map(function (c) { return '<a href="#" class="fab__item">' + c[1] + '<span>' + ic(c[0]) + '</span></a>'; }).join('') +
      '</div><button class="fab__btn" type="button" data-fab aria-label="تماس با ما">' + ic('headset', 'i--lg') + '</button></div>' +
      '<button class="totop" type="button" data-scroll-top aria-label="بازگشت به بالا">' + ic('arrow-up') + '</button>';
  }

  /* ------------------------------------------------------------------ mount */
  var hSlot = document.querySelector('[data-um-header]');
  var fSlot = document.querySelector('[data-um-footer]');
  if (hSlot) hSlot.outerHTML = header();
  if (fSlot) fSlot.outerHTML = footer();
  if (shell === 'store') {
    body.insertAdjacentHTML('beforeend', bottomNav() + floating());
    body.classList.add('has-bottom-nav');
  }
  if (shell) body.insertAdjacentHTML('beforeend', drawers());
  UM.renderProducts();

  /* ------------------------------------------------------------------ behaviour */
  var overlay = document.querySelector('[data-overlay]');
  var openEl = null;
  UM.open = function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    UM.close();
    el.classList.add('is-open');
    if (overlay) overlay.classList.add('is-open');
    openEl = el;
    body.style.overflow = 'hidden';
    var input = el.querySelector('input[type=search]');
    if (input) setTimeout(function () { input.focus(); }, 50);
  };
  UM.close = function () {
    if (openEl) openEl.classList.remove('is-open');
    if (overlay) overlay.classList.remove('is-open');
    openEl = null;
    body.style.overflow = '';
  };

  var toastTimer;
  UM.toast = function (text) {
    var t = document.getElementById('toast');
    if (!t) return;
    document.getElementById('toast-text').textContent = text;
    t.classList.add('is-open');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('is-open'); }, 2600);
  };

  function quickview(id) {
    var p = UM.byId(id);
    document.getElementById('quickview-body').innerHTML =
      '<div class="qv"><div class="qv__media"><img src="' + UM.img(p.img) + '" alt=""></div><div class="qv__info stack">' +
      '<span class="badge badge--brand">' + p.brand + '</span><h3 style="font-size:18px">' + p.title + '</h3>' +
      '<div class="row small">' + '<span class="rating">' + ic('star') + '<span class="num">' + UM.fa(p.rating.toFixed(1)) + '</span></span><span class="faint">(' + UM.fa(UM.money(p.reviews)) + ' دیدگاه)</span></div>' +
      '<ul class="qv__feats">' + (p.feats || []).map(function (f) { return '<li>' + f + '</li>'; }).join('') + '</ul>' +
      (p.colors ? '<div class="row">' + p.colors.map(function (c, i) { return '<span class="swatch' + (i ? '' : ' is-active') + '" style="background:' + c + '"></span>'; }).join('') + '</div>' : '') +
      UM.price(p, 'price--lg') +
      '<div class="row"><div class="qty" data-qty><button type="button" data-inc aria-label="افزایش">' + ic('plus', 'i--sm') + '</button><output class="num">۱</output><button type="button" data-dec aria-label="کاهش">' + ic('trash', 'i--sm') + '</button></div>' +
      '<button class="btn grow" type="button" data-add-cart>افزودن به سبد</button></div>' +
      '<a class="link small" href="' + B + 'pdp.html">مشاهده جزئیات کامل' + ic('chev-left', 'i--sm') + '</a></div></div>';
    UM.open('quickview');
  }

  document.addEventListener('click', function (e) {
    var t = e.target;
    var opener = t.closest('[data-open]');
    if (opener) { e.preventDefault(); UM.open(opener.getAttribute('data-open')); return; }
    if (t.closest('[data-close]') || t === overlay) { UM.close(); if (!t.closest('[data-toast]')) return; }
    var qv = t.closest('[data-quickview]');
    if (qv) { quickview(Number(qv.getAttribute('data-quickview'))); return; }
    if (t.closest('[data-add-cart]')) { UM.close(); UM.toast('کالا به سبد خرید اضافه شد'); return; }
    var tt = t.closest('[data-toast]');
    if (tt) { UM.toast(tt.getAttribute('data-toast')); return; }
    var w = t.closest('[data-wish]');
    if (w) { w.classList.toggle('is-on'); UM.toast(w.classList.contains('is-on') ? 'به علاقه‌مندی‌ها اضافه شد' : 'از علاقه‌مندی‌ها حذف شد'); return; }
    if (t.closest('[data-close-topbar]')) { document.getElementById('topbar').remove(); return; }
    if (t.closest('[data-scroll-top]')) { window.scrollTo({ top: 0 }); return; }
    if (t.closest('[data-fab]')) { document.getElementById('fab').classList.toggle('is-open'); return; }
    var sw = t.closest('[data-swatches] .swatch, [data-swatch-group] .swatch');
    if (sw) { sw.parentNode.querySelectorAll('.swatch').forEach(function (s) { s.classList.remove('is-active'); }); sw.classList.add('is-active'); return; }
    var chip = t.closest('[data-chip-group] .chip');
    if (chip) { chip.parentNode.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('is-active'); }); chip.classList.add('is-active'); return; }
    var mini = t.closest('[data-mini-add] .add-round');
    if (mini) {
      mini.parentNode.innerHTML = '<div class="qty qty--sm" data-qty><button type="button" data-inc aria-label="افزایش">' + ic('plus', 'i--sm') + '</button><output class="num">۱</output><button type="button" data-dec aria-label="کاهش">' + ic('trash', 'i--sm') + '</button></div>';
      return;
    }
    var q = t.closest('[data-qty] button');
    if (q) {
      var box = q.closest('[data-qty]');
      var out = box.querySelector('output');
      var n = Number(out.textContent.replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); }));
      n = q.hasAttribute('data-inc') ? Math.min(n + 1, 5) : Math.max(n - 1, 1);
      out.textContent = UM.fa(n);
      box.querySelector('[data-dec]').innerHTML = ic(n > 1 ? 'minus' : 'trash', 'i--sm');
      return;
    }
    var tab = t.closest('[data-tabs] [data-tab]');
    if (tab) {
      var group = tab.closest('[data-tabs]');
      var name = group.getAttribute('data-tabs');
      group.querySelectorAll('[data-tab]').forEach(function (x) { x.classList.toggle('is-active', x === tab); });
      document.querySelectorAll('[data-tab-panel^="' + name + ':"]').forEach(function (p) { p.hidden = p.getAttribute('data-tab-panel') !== name + ':' + tab.getAttribute('data-tab'); });
      return;
    }
    var nav = t.closest('[data-carousel-nav]');
    if (nav) {
      var track = nav.closest('.carousel').querySelector('.carousel__track');
      var dir = nav.getAttribute('data-carousel-nav') === 'next' ? -1 : 1;
      track.scrollBy({ left: dir * track.clientWidth * 0.8 });
    }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') UM.close(); });

  /* Mega menu: hover a category to swap the panel (HDR-07). */
  var megaBody = document.getElementById('mega-body');
  document.querySelectorAll('[data-cat]').forEach(function (a) {
    a.addEventListener('mouseenter', function () {
      document.querySelectorAll('[data-cat]').forEach(function (x) { x.classList.toggle('is-active', x === a); });
      megaBody.innerHTML = catPanel(Number(a.getAttribute('data-cat')));
    });
  });

  /* Smart sticky header (HDR-05): hide the nav row when scrolling down. */
  var hdr = document.getElementById('hdr');
  var lastY = 0;
  var totop = document.querySelector('.totop');
  window.addEventListener('scroll', function () {
    var y = window.scrollY;
    if (hdr) hdr.classList.toggle('is-compact', y > 160 && y > lastY);
    if (totop) totop.classList.toggle('is-visible', y > 700);
    lastY = y;
  }, { passive: true });

  /* Countdowns: data-countdown="seconds". */
  var counters = [].slice.call(document.querySelectorAll('[data-countdown]')).map(function (el) {
    return { el: el, end: Date.now() + Number(el.getAttribute('data-countdown')) * 1000 };
  });
  function pad(n) { return UM.fa(String(n).padStart(2, '0')); }
  function tick() {
    counters.forEach(function (c) {
      var s = Math.max(0, Math.floor((c.end - Date.now()) / 1000));
      c.el.innerHTML = '<span>' + pad(Math.floor(s / 3600)) + '</span><b>:</b><span>' + pad(Math.floor(s / 60) % 60) + '</span><b>:</b><span>' + pad(s % 60) + '</span>';
    });
  }
  if (counters.length) { tick(); setInterval(tick, 1000); }

  /* Carousels: disable arrows at the ends. RTL scrollLeft runs from 0 to negative. */
  document.querySelectorAll('.carousel').forEach(function (c) {
    var track = c.querySelector('.carousel__track');
    var prev = c.querySelector('[data-carousel-nav="prev"]');
    var next = c.querySelector('[data-carousel-nav="next"]');
    if (!track || !prev) return;
    function update() {
      var max = track.scrollWidth - track.clientWidth;
      var pos = Math.abs(track.scrollLeft);
      prev.disabled = pos < 4;
      next.disabled = pos > max - 4;
    }
    track.addEventListener('scroll', update, { passive: true });
    update();
  });
})();
