/*
 * Sample data and the product card renderer for the prototypes.
 * All brands and products are fictional. Images are authored for this project (see design/ASSETS.fa.md).
 * One renderer (UM.card) produces all six card designs, as the architecture requires.
 */
(function () {
  'use strict';

  var UM = (window.UM = window.UM || {});
  var script = document.currentScript;
  UM.base = script ? script.src.replace(/assets\/js\/data\.js.*$/, '') : '';

  var FA = '۰۱۲۳۴۵۶۷۸۹';
  UM.fa = function (value) { return String(value).replace(/\d/g, function (d) { return FA[d]; }); };
  UM.money = function (n) { return Number(n).toLocaleString('fa-IR'); };
  UM.img = function (name) { return UM.base + 'assets/img/products/' + name + '.svg'; };
  UM.off = function (p) { return p.old ? Math.round(((p.old - p.price) / p.old) * 100) : 0; };
  UM.icon = function (name, cls) { return '<svg class="i ' + (cls || '') + '" aria-hidden="true"><use href="#i-' + name + '"/></svg>'; };

  UM.products = [
    { id: 1, title: 'گوشی موبایل نوویکس مدل X12 ظرفیت ۲۵۶ گیگابایت رم ۸ گیگابایت', brand: 'نوویکس', img: 'phone', price: 18490000, old: 21900000, rating: 4.4, reviews: 1240, colors: ['#23252e', '#8c86ff', '#e8e3d9'], stock: 2, sold: 68, label: 'hot', cat: 'موبایل', feats: ['حافظه داخلی: ۲۵۶ گیگابایت', 'دوربین اصلی: ۵۰ مگاپیکسل', 'باتری: ۵۰۰۰ میلی‌آمپر'] },
    { id: 2, title: 'لپ‌تاپ ۱۵.۶ اینچی زنترا مدل ProBook Z5', brand: 'زنترا', img: 'laptop', price: 54900000, old: 59000000, rating: 4.6, reviews: 312, colors: ['#2d2f38', '#c5c8d1'], sold: 41, cat: 'لپ‌تاپ', feats: ['پردازنده: ۸ هسته‌ای', 'حافظه رم: ۱۶ گیگابایت', 'حافظه: SSD یک ترابایت'] },
    { id: 3, title: 'هدفون بی‌سیم لومو مدل Air Pro با حذف نویز فعال', brand: 'لومو', img: 'headphones', price: 3250000, old: 4600000, rating: 4.2, reviews: 2380, colors: ['#2b2d36', '#ff9a1f', '#f4f5f7'], sold: 84, label: 'freeship', cat: 'صوتی', feats: ['اتصال: بلوتوث ۵.۳', 'زمان پخش: ۴۰ ساعت', 'حذف نویز فعال'] },
    { id: 4, title: 'ساعت هوشمند اوربیس مدل Fit 4 با نمایشگر آمولد', brand: 'اوربیس', img: 'watch', price: 6780000, rating: 4.5, reviews: 860, colors: ['#149a9c', '#23252e'], label: 'new', cat: 'پوشیدنی', feats: ['ضد آب: ۵ اتمسفر', 'سنسور ضربان قلب', 'باتری: ۱۰ روز'] },
    { id: 5, title: 'کفش پیاده‌روی مردانه کاویرا مدل Trail', brand: 'کاویرا', img: 'sneaker', price: 2190000, old: 2890000, rating: 4.1, reviews: 418, colors: ['#6158e6', '#23252e', '#c5c8d1'], sizes: ['۴۰', '۴۱', '۴۲', '۴۳', '۴۴'], out: ['۴۴'], sold: 55, cat: 'کفش', feats: ['جنس رویه: پارچه مش', 'زیره: لاستیک', 'مناسب پیاده‌روی'] },
    { id: 6, title: 'تی‌شرت نخی آستین کوتاه زنانه سپینو', brand: 'سپینو', img: 'tshirt', price: 489000, old: 690000, rating: 4.3, reviews: 205, colors: ['#ff9a1f', '#6158e6', '#f4f5f7', '#23252e'], sizes: ['S', 'M', 'L', 'XL'], out: ['S'], sold: 72, cat: 'پوشاک', feats: ['جنس: نخ پنبه', 'یقه گرد', 'قابل شستشو با ماشین'] },
    { id: 7, title: 'ادو پرفیوم زنانه تیرنگ مدل Velvet حجم ۱۰۰ میلی‌لیتر', brand: 'تیرنگ', img: 'perfume', price: 3950000, rating: 4.7, reviews: 96, label: 'new', cat: 'عطر', feats: ['حجم: ۱۰۰ میلی‌لیتر', 'رایحه: گرم و شیرین', 'ماندگاری: بالا'] },
    { id: 8, title: 'کرم آبرسان صورت لومو مدل Hydra حجم ۵۰ میلی‌لیتر', brand: 'لومو', img: 'cream', price: 685000, old: 820000, rating: 4.0, reviews: 1510, sold: 61, cat: 'مراقبت پوست', unit: '۵۰ میلی‌لیتر', feats: ['مناسب پوست خشک', 'فاقد پارابن', 'حجم ۵۰ میلی‌لیتر'] },
    { id: 9, title: 'قهوه اسپرسو آسیاب‌شده سپینو ۲۵۰ گرمی', brand: 'سپینو', img: 'coffee', price: 412000, old: 475000, rating: 4.4, reviews: 733, sold: 77, cat: 'نوشیدنی', unit: '۲۵۰ گرم', feats: ['ترکیب ۷۰٪ عربیکا', 'رست متوسط', 'وزن ۲۵۰ گرم'] },
    { id: 10, title: 'کتری برقی زنترا مدل K20 ظرفیت ۱.۷ لیتر', brand: 'زنترا', img: 'kettle', price: 2640000, old: 3100000, rating: 4.2, reviews: 342, stock: 3, sold: 49, cat: 'آشپزخانه', feats: ['ظرفیت: ۱.۷ لیتر', 'توان: ۲۲۰۰ وات', 'خاموشی خودکار'] },
    { id: 11, title: 'کوله‌پشتی لپ‌تاپ کاویرا مدل Urban ضد آب', brand: 'کاویرا', img: 'backpack', price: 1480000, rating: 4.5, reviews: 154, colors: ['#0e7f82', '#23252e'], cat: 'کیف', feats: ['مناسب لپ‌تاپ ۱۵.۶ اینچ', 'ضد آب', 'درگاه USB'] },
    { id: 12, title: 'دوربین عکاسی بدون آینه اوربیس مدل M50 با لنز ۱۵-۴۵', brand: 'اوربیس', img: 'camera', price: 62500000, old: 66000000, rating: 4.8, reviews: 58, sold: 23, cat: 'دوربین', feats: ['حسگر: APS-C', 'فیلم‌برداری 4K', 'لنز ۱۵-۴۵ میلی‌متر'] },
    { id: 13, title: 'اسپیکر بلوتوثی قابل حمل نوویکس مدل Boom Mini', brand: 'نوویکس', img: 'speaker', price: 1890000, old: 2450000, rating: 4.3, reviews: 912, colors: ['#ff9a1f', '#23252e'], sold: 81, label: 'freeship', cat: 'صوتی', feats: ['توان: ۲۰ وات', 'ضد آب IPX7', 'باتری: ۱۲ ساعت'] },
    { id: 14, title: 'آبمیوه طبیعی پرتقال سپینو ۱ لیتری', brand: 'سپینو', img: 'juice', price: 98000, old: 115000, rating: 4.1, reviews: 288, sold: 90, cat: 'نوشیدنی', unit: '۱ لیتر', feats: ['بدون شکر افزوده', 'حجم ۱ لیتر', 'نگهداری در یخچال'] }
  ];
  UM.byId = function (id) { return UM.products.filter(function (p) { return p.id === id; })[0]; };
  UM.pick = function (ids) { return ids.map(UM.byId); };

  var LABELS = {
    hot: '<span class="badge badge--hot">' + UM.icon('fire', 'i--sm') + 'پرفروش</span>',
    new: '<span class="badge badge--new">جدید</span>',
    freeship: '<span class="badge badge--brand">' + UM.icon('truck', 'i--sm') + 'ارسال رایگان</span>'
  };

  function price(p, extraClass) {
    var off = UM.off(p);
    return '<div class="price ' + (extraClass || '') + '">' +
      '<div class="price__row">' + (off ? '<span class="badge badge--off num">' + UM.fa(off) + '٪</span>' : '') +
      '<span class="price__now num">' + UM.money(p.price) + '<span class="price__cur">تومان</span></span></div>' +
      (p.old ? '<del class="price__old num">' + UM.money(p.old) + '</del>' : '') + '</div>';
  }
  UM.price = price;

  function rating(p) {
    return '<span class="rating">' + UM.icon('star') + '<span class="num">' + UM.fa(p.rating.toFixed(1)) + '</span></span>';
  }
  function img(p, cls) {
    return '<img class="' + (cls || '') + '" src="' + UM.img(p.img) + '" alt="' + p.title + '" loading="lazy" width="400" height="400">';
  }
  function title(p) { return '<h3 class="pc__title"><a href="' + UM.base + 'pdp.html">' + p.title + '</a></h3>'; }
  function dots(p) {
    if (!p.colors) return '';
    return '<div class="pc__colors" aria-label="رنگ‌های موجود">' + p.colors.map(function (c) { return '<span class="swatch swatch--sm" style="background:' + c + '"></span>'; }).join('') + '</div>';
  }

  var RENDER = {
    classic: function (p) {
      return '<article class="pc pc--classic">' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p) + dots(p) + '</a>' +
        '<div class="pc__body">' + title(p) +
        '<div class="pc__meta">' + (p.stock ? '<span class="pc__stock">تنها ' + UM.fa(p.stock) + ' عدد در انبار</span>' : '<span class="pc__ship">' + UM.icon('truck') + 'ارسال امروز</span>') + rating(p) + '</div>' +
        '<div class="pc__price">' + price(p) + '</div></div></article>';
    },
    hover: function (p) {
      return '<article class="pc pc--hover">' +
        '<div class="pc__labels">' + (p.label ? LABELS[p.label] : '') + '</div>' +
        '<div class="pc__actions">' +
        '<button class="icon-btn" type="button" data-wish aria-label="افزودن به علاقه‌مندی">' + UM.icon('heart') + '</button>' +
        '<button class="icon-btn" type="button" data-toast="کالا به فهرست مقایسه اضافه شد" aria-label="مقایسه">' + UM.icon('compare') + '</button>' +
        '<button class="icon-btn" type="button" data-quickview="' + p.id + '" aria-label="نمایش سریع">' + UM.icon('eye') + '</button></div>' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p, 'pc__main') + img(p, 'pc__alt') + '</a>' +
        '<button class="btn btn--sm pc__quickadd" type="button" data-add-cart>' + UM.icon('cart', 'i--sm') + 'افزودن به سبد</button>' +
        '<div class="pc__body">' + title(p) +
        '<div class="pc__meta"><span class="xsmall faint">' + p.brand + '</span>' + rating(p) + '</div>' +
        '<div class="pc__price">' + price(p) + '</div></div></article>';
    },
    deal: function (p) {
      var sold = p.sold || 50;
      return '<article class="pc pc--deal">' +
        '<div class="pc__timer"><span>' + UM.icon('clock', 'i--sm') + '</span><span class="countdown countdown--sm countdown--deal" data-countdown="' + (20000 + p.id * 997) + '"></span></div>' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p) + '</a>' +
        '<div class="pc__body">' + title(p) +
        '<div class="pc__price">' + price(p) + '</div>' +
        '<div><div class="progress progress--deal"><i style="width:' + sold + '%"></i></div>' +
        '<div class="pc__sold"><span>' + UM.fa(sold) + '٪ فروخته شده</span><span>' + UM.fa(100 - sold) + '٪ باقی</span></div></div>' +
        '</div></article>';
    },
    swatch: function (p) {
      var colors = p.colors || ['#23252e'];
      var sizes = p.sizes ? '<div class="pc__sizes">' + p.sizes.map(function (s) { return '<span class="' + (p.out && p.out.indexOf(s) > -1 ? 'is-out' : '') + '">' + s + '</span>'; }).join('') + '</div>' : '';
      return '<article class="pc pc--swatch">' +
        '<div class="pc__labels">' + (UM.off(p) ? '<span class="badge badge--off num">' + UM.fa(UM.off(p)) + '٪ تخفیف</span>' : '') + '</div>' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p) + '</a>' +
        '<div class="pc__body">' +
        '<div class="pc__swatches" data-swatches>' + colors.map(function (c, i) { return '<button type="button" class="swatch' + (i === 0 ? ' is-active' : '') + '" style="background:' + c + '" aria-label="رنگ ' + (i + 1) + '"></button>'; }).join('') + '<small>' + UM.fa(colors.length) + ' رنگ</small></div>' +
        title(p) + sizes +
        '<div class="pc__price">' + price(p) + '</div>' +
        '<button type="button" class="btn btn--outline btn--sm btn--block" data-add-cart>' + UM.icon('cart', 'i--sm') + 'افزودن به سبد</button>' +
        '</div></article>';
    },
    list: function (p) {
      return '<article class="pc pc--list">' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p) + '</a>' +
        '<div class="pc__body">' + title(p) +
        '<div class="row xsmall faint">' + rating(p) + '<span>(' + UM.fa(UM.money(p.reviews)) + ' دیدگاه)</span><span>·</span><span>برند: ' + p.brand + '</span></div>' +
        '<ul class="pc__feats">' + (p.feats || []).map(function (f) { return '<li>' + f + '</li>'; }).join('') + '</ul>' +
        '<div class="pc__price hide-desktop hide-tablet">' + price(p) + '</div>' +
        '</div>' +
        '<div class="pc__side">' + price(p) +
        '<span class="pc__ship">' + UM.icon('truck') + 'ارسال از ۱ روز کاری آینده</span>' +
        '<button type="button" class="btn btn--sm" data-add-cart>افزودن به سبد</button></div></article>';
    },
    mini: function (p) {
      return '<article class="pc pc--mini">' +
        '<a class="pc__media" href="' + UM.base + 'pdp.html">' + img(p) + '</a>' +
        '<div class="pc__body">' + title(p) +
        '<span class="pc__unit">' + (p.unit || '۱ عدد') + '</span>' +
        '<div class="pc__buy">' + price(p) +
        '<span data-mini-add><button type="button" class="add-round" aria-label="افزودن">' + UM.icon('plus') + '</button></span></div>' +
        '</div></article>';
    }
  };

  UM.card = function (p, variant) { return (RENDER[variant] || RENDER.classic)(p); };

  /* Fill every [data-products] container: data-products="1,2,3" data-variant="hover" */
  UM.renderProducts = function (root) {
    (root || document).querySelectorAll('[data-products]').forEach(function (el) {
      var ids = el.getAttribute('data-products').split(',').map(Number);
      var variant = el.getAttribute('data-variant') || 'classic';
      var wrap = el.getAttribute('data-wrap');
      el.innerHTML = UM.pick(ids).map(function (p) {
        var html = UM.card(p, variant);
        return wrap ? '<div class="' + wrap + '">' + html + '</div>' : html;
      }).join('');
    });
  };

  UM.categories = [
    { name: 'موبایل و تبلت', icon: 'phone', subs: ['گوشی موبایل', 'تبلت', 'قاب و محافظ', 'شارژر و کابل', 'پاوربانک', 'هولدر موبایل'] },
    { name: 'کالای دیجیتال', icon: 'laptop', subs: ['لپ‌تاپ', 'هدفون و هندزفری', 'ساعت هوشمند', 'اسپیکر', 'دوربین', 'لوازم جانبی'] },
    { name: 'خانه و آشپزخانه', icon: 'kettle', subs: ['لوازم برقی کوچک', 'ظروف پخت', 'سرو و پذیرایی', 'دکوراسیون', 'شستشو و نظافت'] },
    { name: 'مد و پوشاک', icon: 'tshirt', subs: ['لباس زنانه', 'لباس مردانه', 'کفش', 'کیف و کوله', 'اکسسوری'] },
    { name: 'آرایشی و بهداشتی', icon: 'cream', subs: ['مراقبت پوست', 'عطر و ادکلن', 'آرایش صورت', 'مراقبت مو', 'بهداشت شخصی'] },
    { name: 'سوپرمارکت', icon: 'juice', subs: ['نوشیدنی', 'لبنیات', 'کالاهای اساسی', 'تنقلات', 'دسر و شیرینی'] },
    { name: 'ورزش و سفر', icon: 'backpack', subs: ['پوشاک ورزشی', 'کفش ورزشی', 'تجهیزات کمپینگ', 'لوازم سفر'] },
    { name: 'کتاب و لوازم تحریر', icon: 'coffee', subs: ['کتاب چاپی', 'لوازم تحریر', 'لوازم هنری'] }
  ];
})();
