/* SEO prototype helpers: Google result preview (SEO-01, SEO-06). Load after data.js. */
(function () {
  'use strict';
  var UM = window.UM;
  UM.serp = function (o) {
    var stars = '<span class="stars">' + [1, 2, 3, 4, 5].map(function (n) { return '<svg class="i' + (n <= 4 ? ' on' : '') + '"><use href="#i-star"/></svg>'; }).join('') + '</span>';
    return '<div class="serp' + (o.mobile ? ' serp--mobile' : '') + '">' +
      '<div class="serp__site"><span class="serp__fav">' + UM.logo().match(/<svg[\s\S]*?<\/svg>/)[0] + '</span><span><div class="serp__name">فروشگاه نمونه</div><div class="serp__url">https://shop.example.ir › product › novix-x12</div></span></div>' +
      '<a class="serp__title" href="#">' + o.title + '</a>' +
      '<div class="serp__desc">' + o.desc + '</div>' +
      '<div class="serp__rich">' + stars + '<span class="num">امتیاز ۴٫۴ (۱٬۲۴۰)</span><span>·</span><span class="num">۱۸٬۴۹۰٬۰۰۰ تومان</span><span>·</span><span>موجود</span><span>·</span><span>ارسال رایگان</span></div>' +
      '</div>';
  };
})();
