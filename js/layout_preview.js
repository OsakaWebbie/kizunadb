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

  // post-office indicia images (print_addr.php): file + aspect (h/w from the bounding box)
  var STAMPS = {
    betsunou:         { file: 'po_betsunou.png',         ar: 452 / 520 },
    yuumail_betsunou: { file: 'po_yuumail_betsunou.png', ar: 600 / 520 },
    kounou:           { file: 'po_kounou.png',           ar: 452 / 520 },
    yuumail_kounou:   { file: 'po_yuumail_kounou.png',   ar: 600 / 520 }
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
    var japan = sample.japan !== false;
    var tate = num(p.Tategaki, 1) == 1;
    var pwP = num(p.PaperWidth, 120), phP = num(p.PaperHeight, 235);
    if (pwP <= 0) pwP = 120; if (phP <= 0) phP = 235;
    // Non-Japan is authored/viewed in landscape (long edge horizontal), so swap the paper dims.
    var pw = japan ? pwP : phP, ph = japan ? phP : pwP;
    var s = fitScale({ w: pw, h: ph }, opts);
    // Positions are true mm from the edge each element hugs; print_addr.php's picture origin is the
    // paper's bottom-left corner (no offset).
    var pt = function (v) { return num(v) * PT_TO_MM * s; };
    var physX = function (x) { return num(x) * s; };                 // mm from left edge
    var physYt = function (y) { return num(y) * s; };                // mm from top edge
    var physYb = function (y) { return (ph - num(y)) * s; };         // mm from bottom edge

    var svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('class', 'lp-svg');
    svg.setAttribute('width', pw * s);
    svg.setAttribute('height', ph * s);
    svg.appendChild(svgRect(0, 0, pw * s, ph * s, { 'class': 'lp-paper' }));

    var over = [];
    // a dashed guide box + text layer for a positioned block; returns the inner div.
    // rectClass adds a class to the guide box so the address/name/foreign boxes can be coloured.
    function block(xL, yBottom, w, h, rectClass) {
      var top = physYb(yBottom) - h * s;
      var rect = svgRect(physX(xL), top, w * s, h * s, { 'class': 'lp-block' + (rectClass ? ' ' + rectClass : '') });
      svg.appendChild(rect);
      var fo = foDiv(physX(xL), top, w * s, h * s);
      svg.appendChild(fo.node);
      over.push({ cell: rect, div: fo.div });
      return fo.div;
    }
    // block positioned by its top edge measured from the paper's top (top-anchored elements)
    function blockT(xL, yTop, w, h, rectClass) { return block(xL, ph - yTop - h, w, h, rectClass); }
    // structured return address: a client graphic (if named) else plain text. Anchored at (ax, ay);
    // `down` = grow down from a top-left anchor (NJ, landscape), else grow up from a bottom-left
    // anchor (JP). Graphic scales to imgWmm (proportional; 0 = natural); text wraps at textWmm.
    function retStructured(ax, ay, graphic, text, ptSize, imgWmm, textWmm, down) {
      if (opts.imgBase && graphic) {
        var href = opts.imgBase + encodeURIComponent(graphic);
        var rect = svgRect(ax, ay, 0, 0, { 'class': 'lp-block lp-ret' });
        svg.appendChild(rect);
        var im = document.createElementNS(SVG, 'image');
        im.setAttribute('href', href);
        im.setAttributeNS('http://www.w3.org/1999/xlink', 'href', href);
        im.setAttribute('preserveAspectRatio', 'none');
        im.setAttribute('width', 0); im.setAttribute('height', 0);
        svg.appendChild(im);
        var place = function (natW, natH) {
          var w = (imgWmm > 0 ? imgWmm : natW) * s, h = (imgWmm > 0 ? natH * imgWmm / natW : natH) * s;
          var y = down ? ay : ay - h;
          im.setAttribute('x', ax); im.setAttribute('y', y);
          im.setAttribute('width', w); im.setAttribute('height', h);
          rect.setAttribute('x', ax); rect.setAttribute('y', y);
          rect.setAttribute('width', w); rect.setAttribute('height', h);
        };
        if (IMG_INFO[href]) place(IMG_INFO[href].w, IMG_INFO[href].h);
        else if (/\.png(\?|$)/i.test(graphic)) pngInfo(href, function (info) {
          if (!info || !info.w) return;
          IMG_INFO[href] = { w: info.w / info.dpi * 25.4, h: info.h / info.dpi * 25.4 };
          place(IMG_INFO[href].w, IMG_INFO[href].h);
        });
        else { var probe = new Image(); probe.onload = function () {
          IMG_INFO[href] = { w: probe.naturalWidth * 25.4 / 72, h: probe.naturalHeight * 25.4 / 72 };
          place(IMG_INFO[href].w, IMG_INFO[href].h); }; probe.src = href; }
      } else {
        var lines = splitLines(text).length || 1;
        var bh = Math.max(4, lines * ptSize * 1.25 * PT_TO_MM);
        var top = down ? ay : ay - bh * s;
        svg.appendChild(svgRect(ax, top, textWmm * s, bh * s, { 'class': 'lp-block lp-ret' }));
        var fo = foDiv(ax, top, textWmm * s, bh * s);
        fo.div.className = 'lp-rettext';
        fo.div.style.fontSize = (ptSize * PT_TO_MM * s) + 'px';
        fo.div.innerHTML = lineDivs(text);
        svg.appendChild(fo.node);
      }
    }

    if (japan) {
      // postal-code digits: SVG <text> so the baseline sits exactly on PCY (black,
      // default variable-width sans-serif — matches print_addr.php, which spaces the digits by
      // position rather than a monospace font).
      var digits = String(sample.postalcode || '').replace(/[^0-9]/g, '').slice(0, 7).split('');
      var pcFont = pt(p.PCPointSize);
      var sp = num(p.PCSpacing), ex = num(p.PCExtraSpace), lm = num(p.PCX), tm = num(p.PCY);
      for (var i = 0; i < 7; i++) {
        if (!digits[i]) continue;
        var dx = lm + i * sp + (i >= 3 ? ex : 0);
        var t = document.createElementNS(SVG, 'text');
        t.setAttribute('x', physX(dx)); t.setAttribute('y', physYt(tm));   // y = baseline from top
        t.setAttribute('class', 'lp-pcdigit'); t.setAttribute('font-size', pcFont);
        t.textContent = digits[i];
        svg.appendChild(t);
      }

      // post-office indicia stamp (print_addr.php: top-left at StampX/StampY, 30mm wide)
      var st = STAMPS[p.DefaultStamp];
      if (st && opts.stampBase) {
        var stW = 30, stH = 30 * st.ar;
        var si = document.createElementNS(SVG, 'image');
        si.setAttribute('x', physX(p.StampX));
        si.setAttribute('y', physYt(p.StampY));
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

      // Recipient block: address + name flow inside one box (RecipX/Y/Width/Height). The name follows
      // the address (can't overlap), offset by NameGap along the flow and NameIndent across it.
      var box = blockT(num(p.RecipX), num(p.RecipY), num(p.RecipWidth), num(p.RecipHeight), 'lp-addr');
      box.className = 'lp-text';
      var addrHTML = '<div style="font-size:' + pt(p.AddrPointSize) + 'px">' + lineDivs(addrText) + '</div>';
      if (tate) {
        box.style.writingMode = 'vertical-rl'; box.style.textOrientation = 'mixed';
        box.style.display = 'flex'; box.style.flexDirection = 'column'; box.style.gap = num(p.NameGap) * s + 'px';
        box.innerHTML = addrHTML +
          '<div style="font-size:' + pt(p.NamePointSize) + 'px;margin-top:' + num(p.NameIndent) * s + 'px">'
          + lineDivs(sample.name) + '</div>';
      } else {                                       // yokogaki
        box.innerHTML = addrHTML +
          '<div style="font-size:' + pt(p.NamePointSize) + 'px;margin-top:' + num(p.NameGap) * s
          + 'px;margin-left:' + num(p.NameIndent) * s + 'px">' + lineDivs(sample.name) + '</div>';
      }
      var retRaw = num(p.RetAddrWidth);
      var retTextW = retRaw > 0 ? retRaw : (pw - num(p.RetAddrX));   // text wraps to the right edge if 0
      retStructured(physX(p.RetAddrX), physYb(p.RetAddrY), p.RetAddrGraphic, p.RetAddrText,
                    num(p.RetAddrPointSize) || 10, retRaw, retTextW);
    } else {
      // Non-Japan: authored/shown in landscape (paper dims swapped above); normal horizontal text.
      // Recipient = name then address, one font size; return address is top-left (Western style).
      var njBox = blockT(num(p.NJRecipX), num(p.NJRecipY), num(p.NJRecipWidth), num(p.NJRecipHeight), 'lp-nj');
      njBox.className = 'lp-text'; njBox.style.fontSize = pt(p.NJAddrPointSize) + 'px';
      njBox.innerHTML = lineDivs(sample.name) + lineDivs(sample.address);   // one block, uniform spacing
      var njRaw = num(p.NJRetAddrWidth);
      var njRetW = njRaw > 0 ? njRaw : (pw - num(p.NJRetAddrX));   // to the right edge if 0
      retStructured(physX(p.NJRetAddrX), physYt(p.NJRetAddrY), p.NJRetAddrGraphic, p.NJRetAddrText,
                    num(p.NJRetAddrPointSize) || 10, njRaw, njRetW, true);
    }

    container.appendChild(svg);
    flagOverflow(over);
    return svg;
  }

  return { renderLabelSheet: renderLabelSheet, renderEnvelope: renderEnvelope, PAPER: PAPER, PT_TO_MM: PT_TO_MM };
})();
