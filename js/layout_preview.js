/*****
layout_preview.js — live client-side mock of envelope / label-sheet layouts.

Re-runs the same millimetre arithmetic that print_addr.php (\put) and print_label.php (textpos)
use to place blocks on the page, scaled mm->px into an inline SVG, so the preview redraws
instantly as the user edits a layout preset. It is faithful to POSITIONING; exact LaTeX font
metrics / line wrapping differ slightly (the batch print flow produces the real PDF).

Public API (window.LayoutPreview):
  renderLabelSheet(params, sample, containerEl)   -- label sheets  (Phase 1)
  renderEnvelope(params, sample, containerEl)     -- envelopes     (Phase 2, TODO)

`params` are the numeric labelprint/addrprint fields (mm / pt), `sample` is the text to show.
*****/
window.LayoutPreview = (function () {
  var PT_TO_MM = 25.4 / 72;                 // LaTeX font sizes are in points
  var PAPER = { a4: { w: 210, h: 297 }, letter: { w: 215.9, h: 279.4 } };
  var SVG = 'http://www.w3.org/2000/svg';
  var XHTML = 'http://www.w3.org/1999/xhtml';

  function num(v, d) { v = parseFloat(v); return isNaN(v) ? (d || 0) : v; }

  // Fit the paper into the pane; returns px-per-mm.
  function fitScale(paper, opts) {
    var maxW = (opts && opts.maxWidth) || 460;
    var maxH = (opts && opts.maxHeight) || 640;
    return Math.min(maxW / paper.w, maxH / paper.h);
  }

  function svgRect(x, y, w, h, attrs) {
    var r = document.createElementNS(SVG, 'rect');
    r.setAttribute('x', x); r.setAttribute('y', y);
    r.setAttribute('width', Math.max(0, w)); r.setAttribute('height', Math.max(0, h));
    for (var k in attrs) r.setAttribute(k, attrs[k]);
    return r;
  }

  // A wrapping/vertical text block via <foreignObject> (real CSS layout inside SVG).
  // opts: {fontPx, vertical, vAlign:'center'|'top', align} -- returns {node, div}.
  function textBlock(x, y, w, h, html, opts) {
    var fo = document.createElementNS(SVG, 'foreignObject');
    fo.setAttribute('x', x); fo.setAttribute('y', y);
    fo.setAttribute('width', Math.max(0, w)); fo.setAttribute('height', Math.max(0, h));
    var div = document.createElementNS(XHTML, 'div');
    div.setAttribute('class', 'lp-text' + (opts.vertical ? ' lp-tategaki' : ''));
    div.style.fontSize = opts.fontPx + 'px';
    div.style.lineHeight = '1.2';
    div.style.height = '100%';
    div.style.boxSizing = 'border-box';
    div.style.display = 'flex';
    div.style.flexDirection = 'column';
    div.style.justifyContent = (opts.vAlign === 'top') ? 'flex-start' : 'center';
    if (opts.vertical) {
      div.style.writingMode = 'vertical-rl';
      div.style.flexDirection = 'row';
      div.style.justifyContent = 'flex-start';
    }
    div.innerHTML = html;
    fo.appendChild(div);
    return { node: fo, div: div };
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function splitLines(s) { return String(s == null ? '' : s).split(/\r\n|\r|\n/); }

  // One logical line rendered flush-first / hanging-indented (mirrors print_label.php's
  // per-line \hangindent). hangPx indents wrapped continuation lines only.
  function hLine(text, hangPx) {
    return '<div class="lp-line" style="padding-left:' + hangPx + 'px;text-indent:-' + hangPx + 'px">'
      + esc(text) + '</div>';
  }

  // Build the label text for one cell, honouring the same JP / non-JP split, postal-code line,
  // and hanging indents as print_label.php.
  function labelCellHTML(p, sample, japan, hangPx, wrapPc) {
    var out = '';
    if (japan) {
      var parts = splitLines(sample.address);
      var first = parts.shift() || '';
      var geo = (sample.prefecture || '') + (sample.shikucho || '');
      var pc = sample.postalcode ? '〒' + sample.postalcode : '';
      if (wrapPc && pc) {
        out += hLine(pc, hangPx);
        out += hLine(geo + first, hangPx);
      } else {
        out += hLine((pc ? pc + ' ' : '') + geo + first, hangPx);
      }
      parts.forEach(function (l) { out += hLine(l, hangPx); });
      var nameEm = num(p.NamePointSize, 15) / num(p.AddrPointSize, 12);   // name is larger
      var nm = splitLines(sample.name).map(function (l) { return hLine(l, hangPx); }).join('');
      out += '<div class="lp-name" style="font-size:' + nameEm + 'em">' + nm + '</div>';
    } else {
      splitLines(sample.name).forEach(function (l) { out += hLine(l, hangPx); });
      out += '<div class="lp-gap"></div>';
      splitLines(sample.address).forEach(function (l) { out += hLine(l, hangPx); });
    }
    return out;
  }

  function clear(el) { while (el.firstChild) el.removeChild(el.firstChild); }

  /* ---- LABEL SHEETS ---- */
  // `samples` may be a single sample object or an array; cells cycle through the array.
  function renderLabelSheet(p, samples, container, opts) {
    clear(container);
    if (!Array.isArray(samples)) samples = [samples];
    if (!samples.length) samples = [{ japan: true, name: '', address: '' }];

    var paper = PAPER[(p.PaperSize || 'a4').toLowerCase()] || PAPER.a4;
    var s = fitScale(paper, opts);
    var mm = function (v) { return v * s; };

    var svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('class', 'lp-svg');
    svg.setAttribute('width', mm(paper.w));
    svg.setAttribute('height', mm(paper.h));
    svg.appendChild(svgRect(0, 0, mm(paper.w), mm(paper.h), { 'class': 'lp-paper' }));

    var rows = Math.max(0, Math.round(num(p.NumRows)));
    var cols = Math.max(0, Math.round(num(p.NumCols)));
    var lw = num(p.LabelWidth), lh = num(p.LabelHeight);
    var pitchX = lw + num(p.GutterX), pitchY = lh + num(p.GutterY);
    var mL = num(p.PageMarginLeft), mT = num(p.PageMarginTop);
    var insetL = num(p.AddrMarginLeft), insetR = num(p.AddrMarginRight);
    var hangPx = mm(Math.floor(num(p.AddrPointSize, 12) * 0.7));   // print_label.php line 22
    var wrapPc = !!(opts && opts.wrapPc);

    var overflowNodes = [];
    for (var r = 0; r < rows; r++) {
      for (var c = 0; c < cols; c++) {
        var sample = samples[(r * cols + c) % samples.length];
        var japan = sample.japan !== false;
        var fontPx = mm((japan ? num(p.AddrPointSize, 12) : num(p.NJAddrPointSize, 12)) * PT_TO_MM);
        var lx = mL + c * pitchX, ly = mT + r * pitchY;
        var cell = svgRect(mm(lx), mm(ly), mm(lw), mm(lh), { 'class': 'lp-label' });
        svg.appendChild(cell);
        var tb = textBlock(mm(lx + insetL), mm(ly), mm(lw - insetL - insetR), mm(lh),
                           labelCellHTML(p, sample, japan, hangPx, wrapPc),
                           { fontPx: fontPx, vertical: false, vAlign: 'center' });
        svg.appendChild(tb.node);
        overflowNodes.push({ cell: cell, div: tb.div });
      }
    }

    container.appendChild(svg);
    flagOverflow(overflowNodes);
    return svg;
  }

  // After the SVG is in the DOM, outline any block whose text overflows its box.
  function flagOverflow(nodes) {
    nodes.forEach(function (n) {
      var d = n.div;
      if (d.scrollHeight > d.clientHeight + 1 || d.scrollWidth > d.clientWidth + 1) {
        n.cell.setAttribute('class', n.cell.getAttribute('class') + ' lp-overflow');
      }
    });
  }

  /* ---- ENVELOPES / POSTCARDS ---- */

  var KNUM = { '0':'〇','1':'一','2':'二','3':'三','4':'四','5':'五','6':'六','7':'七','8':'八','9':'九','-':'の' };
  function toKanjiNum(s) { return String(s == null ? '' : s).replace(/[0-9-]/g, function (c) { return KNUM[c] || c; }); }

  // Crudely render raw-LaTeX return-address content as plain text for the mock.
  function stripLatex(s) {
    s = String(s == null ? '' : s)
      .replace(/\\\\/g, '\n')                                        // \\ line breaks
      .replace(/%[^\n]*/g, '')                                       // comments
      .replace(/\\begin\{[^}]*\}(<[a-z]>)?(\[[^\]]*\])?(\{[^}]*\})?/gi, '')  // \begin{minipage}<t>[t]{50mm}
      .replace(/\\end\{[^}]*\}/gi, '')                               // \end{...}
      .replace(/\\put\s*\([^)]*\)\s*\{([^}]*)\}/g, '$1')             // \put(x,y){d} -> d
      .replace(/<[a-z]>/gi, '')                                      // stray minipage<t>
      .replace(/\\[a-zA-Z@]+\*?/g, ' ')                              // other control words
      .replace(/\([-\d.,\s]*\)/g, '')                               // (w,h) / (x,y) groups
      .replace(/\[[a-z]{1,3}\]/gi, '')                               // [rt] etc.
      .replace(/[{}]/g, '')                                          // braces
      .replace(/[ \t]+/g, ' ');
    // drop leftover coordinate/number-only lines
    return s.split(/\n/).map(function (l) { return l.trim(); })
      .filter(function (l) { return l && !/^[\d.\s]+$/.test(l); }).join('\n');
  }

  // extract a \includegraphics{path} (+ width/height in mm, rotation angle) from return-address LaTeX
  function parseGraphic(s) {
    var m = /\\includegraphics\s*(\[[^\]]*\])?\s*\{([^}]*)\}/.exec(String(s == null ? '' : s));
    if (!m) return null;
    var o = m[1] || '';
    var wm = /width\s*=\s*([\d.]+)\s*mm/i.exec(o), hm = /height\s*=\s*([\d.]+)\s*mm/i.exec(o);
    var am = /angle\s*=\s*(-?[\d.]+)/i.exec(o);
    // graphicx applies keys left-to-right: angle BEFORE a size key => the size is the final
    // (post-rotation) dimension; a size key BEFORE angle => it scales the un-rotated image, then rotates.
    var sIdx = o.search(/\b(?:width|height)\s*=/i), aIdx = o.search(/\bangle\s*=/i);
    return { path: m[2].trim(), width: wm ? parseFloat(wm[1]) : 0, height: hm ? parseFloat(hm[1]) : 0,
             angle: am ? parseFloat(am[1]) : 0, rotateFirst: aIdx >= 0 && sIdx >= 0 && aIdx < sIdx };
  }

  // first \fontsize{N}{..} in return-address LaTeX -> point size, or 0 if none
  function parseFontSize(s) {
    var m = /\\fontsize\s*\{\s*([\d.]+)\s*\}/.exec(String(s == null ? '' : s));
    return m ? parseFloat(m[1]) : 0;
  }

  // first \makebox(w,h) in return-address LaTeX -> {w,h} in mm, or null
  function parseMakebox(s) {
    var m = /\\makebox\s*\(\s*([\d.]+)\s*,\s*([\d.]+)\s*\)/.exec(String(s == null ? '' : s));
    return m ? { w: parseFloat(m[1]), h: parseFloat(m[2]) } : null;
  }

  // Read a PNG's pixel size + DPI (IHDR/pHYs) so an un-sized \includegraphics matches dvipdfmx,
  // which honors the file's pHYs (defaulting to 72dpi when absent). cb({w,h,dpi}) or cb(null).
  function pngInfo(url, cb) {
    fetch(url).then(function (r) { return r.arrayBuffer(); }).then(function (buf) {
      var dv = new DataView(buf);
      if (dv.byteLength < 24 || dv.getUint32(0) !== 0x89504E47) { cb(null); return; }
      var w = 0, h = 0, dpi = 72, off = 8;
      while (off + 12 <= dv.byteLength) {
        var len = dv.getUint32(off);
        var t = String.fromCharCode(dv.getUint8(off + 4), dv.getUint8(off + 5), dv.getUint8(off + 6), dv.getUint8(off + 7));
        if (t === 'IHDR') { w = dv.getUint32(off + 8); h = dv.getUint32(off + 12); }
        else if (t === 'pHYs') { if (dv.getUint8(off + 16) === 1) dpi = dv.getUint32(off + 8) * 0.0254; }
        else if (t === 'IDAT') break;                  // pHYs (if any) precedes IDAT
        off += 12 + len;
      }
      cb({ w: w, h: h, dpi: dpi });
    }).catch(function () { cb(null); });
  }

  // post-office indicia images (print_addr.php): file, y-drop below PCTopMargin, aspect (h/w from bb)
  var STAMPS = {
    betsunou:         { file: 'po_betsunou.png',         dy: 18, ar: 452 / 520 },
    yuumail_betsunou: { file: 'po_yuumail_betsunou.png', dy: 22, ar: 600 / 520 },
    kounou:           { file: 'po_kounou.png',           dy: 18, ar: 452 / 520 },
    yuumail_kounou:   { file: 'po_yuumail_kounou.png',   dy: 22, ar: 600 / 520 }
  };

  var IMG_INFO = {};                                 // cache of return-address image natural sizes (mm)

  // one foreignObject + inner div at px bounds; returns {node, div}
  function foDiv(left, top, w, h) {
    var f = document.createElementNS(SVG, 'foreignObject');
    f.setAttribute('x', left); f.setAttribute('y', top);
    f.setAttribute('width', Math.max(0, w)); f.setAttribute('height', Math.max(0, h));
    var d = document.createElementNS(XHTML, 'div');
    d.style.width = '100%'; d.style.height = '100%'; d.style.boxSizing = 'border-box';
    f.appendChild(d);
    return { node: f, div: d };
  }

  // each logical line -> its own block child (columns in vertical mode, rows in horizontal)
  function lineDivs(text) {
    return splitLines(text).map(function (l) { return '<div>' + esc(l) + '</div>'; }).join('');
  }

  function renderEnvelope(p, sample, container, opts) {
    clear(container);
    opts = opts || {};
    var pw = num(p.PaperWidth, 120), ph = num(p.PaperHeight, 235);
    if (pw <= 0) pw = 120; if (ph <= 0) ph = 235;
    var s = fitScale({ w: pw, h: ph }, opts);
    // Mirrors print_addr.php's \begin{picture}(W,H)(3,3): every \put is shifted 3mm toward the
    // bottom-left corner. Tracks print_addr — set to 0 if/when that (3,3) is removed.
    var OFF = 3;
    var pt = function (v) { return num(v) * PT_TO_MM * s; };
    var scrX = function (x) { return (num(x) - OFF) * s; };
    var scrYb = function (y) { return (ph - (num(y) - OFF)) * s; };   // screen-y of a from-bottom point

    var svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('class', 'lp-svg');
    svg.setAttribute('width', pw * s);
    svg.setAttribute('height', ph * s);
    svg.appendChild(svgRect(0, 0, pw * s, ph * s, { 'class': 'lp-paper' }));

    var over = [];
    // a dashed guide box + text layer for a positioned block; returns the inner div.
    // rectClass adds a class to the guide box so the address/name/foreign boxes can be coloured.
    function block(xL, yBottom, w, h, rectClass) {
      var top = scrYb(yBottom) - h * s;
      var rect = svgRect(scrX(xL), top, w * s, h * s, { 'class': 'lp-block' + (rectClass ? ' ' + rectClass : '') });
      svg.appendChild(rect);
      var fo = foDiv(scrX(xL), top, w * s, h * s);
      svg.appendChild(fo.node);
      over.push({ cell: rect, div: fo.div });
      return fo.div;
    }
    // return address, placed at print_addr.php's \put(xL,yBottom) (the block's lower-left):
    //  - a \includegraphics image: anchored lower-left, sized from width= or natural px (~72dpi),
    //    rotated by \includegraphics angle (its rotated bounding box's lower-left stays at the anchor)
    //  - else the raw LaTeX shown as stripped text, [rt]-justified, honoring \fontsize{N}
    function retBlock(xL, yBottom, latex, sideways) {
      var ax = scrX(xL), ay = scrYb(yBottom);              // \put anchor = block lower-left
      var g = opts.imgBase ? parseGraphic(latex) : null;
      if (g) {
        var href = opts.imgBase + encodeURIComponent(g.path);
        var rect = svgRect(ax, ay, 0, 0, { 'class': 'lp-block lp-ret' });  // sized once we know dims
        svg.appendChild(rect);
        var im = document.createElementNS(SVG, 'image');
        im.setAttribute('href', href);
        im.setAttributeNS('http://www.w3.org/1999/xlink', 'href', href);
        im.setAttribute('preserveAspectRatio', 'none');
        im.setAttribute('width', 0); im.setAttribute('height', 0);   // hidden until sized (no flash)
        svg.appendChild(im);
        // Place the un-rotated image with its lower-left at the \put point, then rotate about that
        // point — graphicx rotates a graphic about its reference (lower-left) corner. So the anchor
        // is the graphic's reference, and the rotated content falls where print_addr.php puts it.
        var place = function (wmm, hmm) {
          im.setAttribute('x', ax); im.setAttribute('y', ay - hmm * s);
          im.setAttribute('width', wmm * s); im.setAttribute('height', hmm * s);
          if (g.angle) im.setAttribute('transform', 'rotate(' + (-g.angle) + ' ' + ax + ' ' + ay + ')');
          // guide box = bounding box of the rotated corners (relative to the anchor)
          var rad = -num(g.angle) * Math.PI / 180, co = Math.cos(rad), si = Math.sin(rad), xs = [], ys = [];
          [[0, 0], [wmm * s, 0], [0, -hmm * s], [wmm * s, -hmm * s]].forEach(function (c) {
            xs.push(c[0] * co - c[1] * si); ys.push(c[0] * si + c[1] * co);
          });
          var minX = Math.min.apply(null, xs), minY = Math.min.apply(null, ys);
          rect.setAttribute('x', ax + minX); rect.setAttribute('y', ay + minY);
          rect.setAttribute('width', Math.max.apply(null, xs) - minX);
          rect.setAttribute('height', Math.max.apply(null, ys) - minY);
        };
        // width=/height= scale against the natural ROTATED bounding box when angle came first
        // (post-rotation size), else against the un-rotated image (graphicx applies keys in order).
        var apply = function (nw, nh) {
          var rad = num(g.angle) * Math.PI / 180, c = Math.abs(Math.cos(rad)), sn = Math.abs(Math.sin(rad));
          var refW = g.rotateFirst ? nw * c + nh * sn : nw;   // what width= scales
          var refH = g.rotateFirst ? nw * sn + nh * c : nh;   // what height= scales
          var scale = (g.width && g.height) ? Math.min(g.width / refW, g.height / refH)
                    : g.width ? g.width / refW : g.height ? g.height / refH : 1;
          place(nw * scale, nh * scale);
        };
        if (IMG_INFO[href]) {                              // cached natural size -> synchronous
          apply(IMG_INFO[href].w, IMG_INFO[href].h);
        } else if (/\.png(\?|$)/i.test(g.path)) {          // PNG: honor pHYs DPI like dvipdfmx
          pngInfo(href, function (info) {
            if (!info || !info.w) return;
            IMG_INFO[href] = { w: info.w / info.dpi * 25.4, h: info.h / info.dpi * 25.4 };
            apply(IMG_INFO[href].w, IMG_INFO[href].h);
          });
        } else {                                           // other formats: assume 72dpi
          var probe = new Image();
          probe.onload = function () {
            IMG_INFO[href] = { w: probe.naturalWidth * 25.4 / 72, h: probe.naturalHeight * 25.4 / 72 };
            apply(IMG_INFO[href].w, IMG_INFO[href].h);
          };
          probe.src = href;
        }
      } else {
        var mb = parseMakebox(latex);
        var bw = mb ? mb.w : (sideways ? 40 : 60), bh = mb ? mb.h : (sideways ? 80 : 30);
        var top = ay - bh * s;
        svg.appendChild(svgRect(ax, top, bw * s, bh * s, { 'class': 'lp-block lp-ret' }));
        var fo = foDiv(ax, top, bw * s, bh * s);
        fo.div.className = 'lp-rettext' + (sideways ? ' lp-sideways' : '');
        if (!sideways) fo.div.style.textAlign = 'right';   // [rt] justify horizontal text
        var fpt = parseFontSize(latex);
        if (fpt) fo.div.style.fontSize = (fpt * PT_TO_MM * s) + 'px';   // honor \fontsize{N}
        fo.div.textContent = stripLatex(latex) || '(return address)';
        svg.appendChild(fo.node);
      }
    }

    var japan = sample.japan !== false;
    var tate = num(p.Tategaki, 1) == 1;

    if (japan) {
      // postal-code digits: SVG <text> so the baseline sits exactly on PCTopMargin (black,
      // default variable-width sans-serif — matches print_addr.php, which spaces the digits by
      // position rather than a monospace font).
      var digits = String(sample.postalcode || '').replace(/[^0-9]/g, '').slice(0, 7).split('');
      var pcFont = pt(p.PCPointSize);
      var sp = num(p.PCSpacing), ex = num(p.PCExtraSpace), lm = num(p.PCLeftMargin), tm = num(p.PCTopMargin);
      for (var i = 0; i < 7; i++) {
        if (!digits[i]) continue;
        var dx = lm + i * sp + (i >= 3 ? ex : 0);
        var t = document.createElementNS(SVG, 'text');
        t.setAttribute('x', scrX(dx)); t.setAttribute('y', scrYb(tm));   // y = baseline
        t.setAttribute('class', 'lp-pcdigit'); t.setAttribute('font-size', pcFont);
        t.textContent = digits[i];
        svg.appendChild(t);
      }

      // post-office indicia stamp (print_addr.php: \put(PaperLeftMargin, PCTopMargin-dy), 30mm wide)
      var st = STAMPS[p.DefaultStamp];
      if (st && opts.stampBase) {
        var stW = 30, stH = 30 * st.ar;
        var si = document.createElementNS(SVG, 'image');
        si.setAttribute('x', scrX(p.PaperLeftMargin));
        si.setAttribute('y', scrYb(num(p.PCTopMargin) - st.dy) - stH * s);
        si.setAttribute('width', stW * s); si.setAttribute('height', stH * s);
        si.setAttribute('href', opts.stampBase + st.file);
        si.setAttributeNS('http://www.w3.org/1999/xlink', 'href', opts.stampBase + st.file);
        svg.appendChild(si);
      }

      // address: prefecture+city join the FIRST address line in one continuous column (print_addr.php
      // keeps them together); only explicit address line breaks start new columns. Kanji numerals
      // apply to the address part only, not the prefecture/city.
      var geo = (sample.prefecture || '') + (sample.shikucho || '');
      var addr = sample.address || '';
      if (opts.kanjiNumbers) addr = toKanjiNum(addr);
      var aLines = addr ? addr.split(/\r\n|\r|\n/) : [];
      var addrText = geo + (aLines.shift() || '') + (aLines.length ? '\n' + aLines.join('\n') : '');

      if (tate) {
        var aDiv = block(num(p.PaperLeftMargin), num(p.AddrPositionY) - num(p.AddrLineLength),
                         num(p.AddrPositionX) - num(p.PaperLeftMargin), num(p.AddrLineLength), 'lp-addr');
        aDiv.className = 'lp-text lp-vwrap'; aDiv.style.fontSize = pt(p.AddrPointSize) + 'px';
        aDiv.innerHTML = lineDivs(addrText);
        var nDiv = block(num(p.NamePositionX) - num(p.NameWidth) / 2, num(p.NamePositionY) - num(p.NameLineLength),
                         num(p.NameWidth), num(p.NameLineLength), 'lp-name');
        nDiv.className = 'lp-text lp-vname'; nDiv.style.fontSize = pt(p.NamePointSize) + 'px';
        nDiv.innerHTML = '<div class="lp-vwrap">' + lineDivs(sample.name) + '</div>';
      } else {                                       // yokogaki (print_addr.php 178-201)
        var addrH = num(p.AddrPositionY) - num(p.NamePositionX);
        var ah = block(num(p.AddrPositionX), num(p.AddrPositionY) - addrH, num(p.AddrLineLength), addrH, 'lp-addr');
        ah.className = 'lp-text'; ah.style.fontSize = pt(p.AddrPointSize) + 'px'; ah.innerHTML = lineDivs(addrText);
        var nh = block(num(p.NamePositionX), num(p.NamePositionY) - ph / 2, num(p.NameLineLength), ph / 2, 'lp-name');
        nh.className = 'lp-text'; nh.style.fontSize = pt(p.NamePointSize) + 'px'; nh.innerHTML = lineDivs(sample.name);
      }
      retBlock(num(p.PaperLeftMargin), num(p.PaperBottomMargin), p.RetAddrContent, false);
    } else {
      // Non-Japan: the envelope is addressed in landscape, so the recipient + return blocks are
      // rotated (writing-mode:vertical-rl; text-orientation:sideways).
      var njDiv = block(num(p.PaperLeftMargin), num(p.NJAddrPositionY) - num(p.NJAddrHeight),
                        num(p.NJAddrPositionX) - num(p.PaperLeftMargin), num(p.NJAddrHeight), 'lp-nj');
      njDiv.className = 'lp-text lp-sideways'; njDiv.style.fontSize = pt(p.NJAddrPointSize) + 'px';
      njDiv.innerHTML = lineDivs((sample.name || '') + '\n' + (sample.address || ''));
      retBlock(num(p.NJRetAddrLeftMargin), num(p.NJRetAddrTopMargin), p.NJRetAddrContent, true);
    }

    container.appendChild(svg);
    flagOverflow(over);
    return svg;
  }

  return { renderLabelSheet: renderLabelSheet, renderEnvelope: renderEnvelope, PAPER: PAPER, PT_TO_MM: PT_TO_MM };
})();
