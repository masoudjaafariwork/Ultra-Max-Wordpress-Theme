/*
 * Helpers for the document prototypes: Persian number words, and visual-only barcode / QR drawings.
 * The barcode and QR here are decorative placeholders; phase 6 generates real, scannable codes.
 */
(function () {
  'use strict';
  var UM = window.UM;

  var ONES = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
  var TENS = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
  var HUNDREDS = ['', 'یکصد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
  var SCALES = ['', 'هزار', 'میلیون', 'میلیارد', 'هزار میلیارد'];
  function three(n) {
    var parts = [];
    if (n >= 100) { parts.push(HUNDREDS[Math.floor(n / 100)]); n %= 100; }
    if (n >= 20) { parts.push(TENS[Math.floor(n / 10)]); n %= 10; }
    if (n > 0) parts.push(ONES[n]);
    return parts.join(' و ');
  }
  UM.words = function (n) {
    if (n === 0) return 'صفر';
    var groups = [];
    var i = 0;
    while (n > 0) {
      var g = n % 1000;
      if (g) groups.unshift(three(g) + (SCALES[i] ? ' ' + SCALES[i] : ''));
      n = Math.floor(n / 1000); i++;
    }
    return groups.join(' و ');
  };

  function seeded(str) {
    var h = 2166136261;
    for (var i = 0; i < str.length; i++) { h ^= str.charCodeAt(i); h = Math.imul(h, 16777619); }
    return function () { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return ((h >>> 0) % 1000) / 1000; };
  }

  UM.barcode = function (text, w, h) {
    var rnd = seeded(text);
    var x = 6, bars = '';
    while (x < w - 6) {
      var bw = 1 + Math.floor(rnd() * 3);
      if (rnd() > 0.45) bars += '<rect x="' + x + '" y="0" width="' + bw + '" height="' + h + '"/>';
      x += bw + 1;
    }
    return '<svg width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '" fill="#000" aria-label="بارکد نمونه">' + bars + '</svg>';
  };

  UM.qr = function (text, size) {
    var n = 25, c = size / n, rnd = seeded(text), out = '';
    function finder(x, y) {
      return '<rect x="' + x * c + '" y="' + y * c + '" width="' + 7 * c + '" height="' + 7 * c + '"/><rect x="' + (x + 1) * c + '" y="' + (y + 1) * c + '" width="' + 5 * c + '" height="' + 5 * c + '" fill="#fff"/><rect x="' + (x + 2) * c + '" y="' + (y + 2) * c + '" width="' + 3 * c + '" height="' + 3 * c + '"/>';
    }
    for (var y = 0; y < n; y++) for (var x = 0; x < n; x++) {
      var inFinder = (x < 8 && y < 8) || (x > n - 9 && y < 8) || (x < 8 && y > n - 9);
      if (!inFinder && rnd() > 0.52) out += '<rect x="' + x * c + '" y="' + y * c + '" width="' + c + '" height="' + c + '"/>';
    }
    return '<svg width="' + size + '" height="' + size + '" viewBox="0 0 ' + size + ' ' + size + '" fill="#000" aria-label="کد QR نمونه">' + out + finder(0, 0) + finder(n - 7, 0) + finder(0, n - 7) + '</svg>';
  };

  /* Fit the paper to narrow screens. */
  UM.fitPaper = function () {
    var paper = document.querySelector('.paper');
    if (!paper) return;
    function fit() {
      paper.style.zoom = '';
      var avail = document.querySelector('.doc-stage').clientWidth - 16;
      var w = paper.offsetWidth;
      if (w > avail) paper.style.zoom = (avail / w).toFixed(3);
    }
    fit();
    window.addEventListener('resize', fit);
  };
})();
