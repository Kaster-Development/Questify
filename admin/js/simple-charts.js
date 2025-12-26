(function (window) {
  'use strict';

  function getCanvas(canvasOrId) {
    if (!canvasOrId) return null;
    if (typeof canvasOrId === 'string') {
      return document.getElementById(canvasOrId);
    }
    return canvasOrId;
  }

  function clear(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);
  }

  function drawLineChart(canvasOrId, labels, values, opts) {
    var canvas = getCanvas(canvasOrId);
    if (!canvas || !canvas.getContext) return;

    var ctx = canvas.getContext('2d');
    var width = canvas.width || canvas.clientWidth;
    var height = canvas.height || canvas.clientHeight;

    // Ensure canvas has drawable size.
    if (!canvas.width) canvas.width = width;
    if (!canvas.height) canvas.height = height;

    clear(ctx, canvas.width, canvas.height);

    var padding = 28;
    var plotW = canvas.width - padding * 2;
    var plotH = canvas.height - padding * 2;

    var maxV = 0;
    for (var i = 0; i < values.length; i++) {
      var v = Number(values[i]) || 0;
      if (v > maxV) maxV = v;
    }
    if (maxV === 0) maxV = 1;

    // Axes
    ctx.strokeStyle = '#ccd0d4';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(padding, padding);
    ctx.lineTo(padding, padding + plotH);
    ctx.lineTo(padding + plotW, padding + plotH);
    ctx.stroke();

    // Line
    var stroke = (opts && opts.borderColor) || '#0073aa';
    var fill = (opts && opts.backgroundColor) || 'rgba(0, 115, 170, 0.1)';

    ctx.beginPath();
    for (var x = 0; x < values.length; x++) {
      var value = Number(values[x]) || 0;
      var px = padding + (values.length === 1 ? 0 : (plotW * x) / (values.length - 1));
      var py = padding + plotH - (plotH * value) / maxV;
      if (x === 0) ctx.moveTo(px, py);
      else ctx.lineTo(px, py);
    }

    // Fill area
    ctx.lineTo(padding + plotW, padding + plotH);
    ctx.lineTo(padding, padding + plotH);
    ctx.closePath();
    ctx.fillStyle = fill;
    ctx.fill();

    // Stroke line on top
    ctx.beginPath();
    for (var x2 = 0; x2 < values.length; x2++) {
      var value2 = Number(values[x2]) || 0;
      var px2 = padding + (values.length === 1 ? 0 : (plotW * x2) / (values.length - 1));
      var py2 = padding + plotH - (plotH * value2) / maxV;
      if (x2 === 0) ctx.moveTo(px2, py2);
      else ctx.lineTo(px2, py2);
    }
    ctx.strokeStyle = stroke;
    ctx.lineWidth = 2;
    ctx.stroke();

    // Minimal x labels (first/last)
    ctx.fillStyle = '#50575e';
    ctx.font = '12px sans-serif';
    if (labels && labels.length) {
      ctx.textAlign = 'left';
      ctx.fillText(String(labels[0]), padding, canvas.height - 8);
      ctx.textAlign = 'right';
      ctx.fillText(String(labels[labels.length - 1]), canvas.width - padding, canvas.height - 8);
    }
  }

  function drawDoughnutChart(canvasOrId, values, colors) {
    var canvas = getCanvas(canvasOrId);
    if (!canvas || !canvas.getContext) return;

    var ctx = canvas.getContext('2d');
    var width = canvas.width || canvas.clientWidth;
    var height = canvas.height || canvas.clientHeight;

    if (!canvas.width) canvas.width = width;
    if (!canvas.height) canvas.height = height;

    clear(ctx, canvas.width, canvas.height);

    var total = 0;
    for (var i = 0; i < values.length; i++) total += Number(values[i]) || 0;
    if (total === 0) total = 1;

    var cx = canvas.width / 2;
    var cy = canvas.height / 2;
    var radius = Math.min(cx, cy) - 10;
    var innerRadius = radius * 0.6;

    var start = -Math.PI / 2;
    for (var s = 0; s < values.length; s++) {
      var val = Number(values[s]) || 0;
      var angle = (val / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.fillStyle = (colors && colors[s]) || '#0073aa';
      ctx.arc(cx, cy, radius, start, start + angle);
      ctx.closePath();
      ctx.fill();
      start += angle;
    }

    // Cut out center
    ctx.globalCompositeOperation = 'destination-out';
    ctx.beginPath();
    ctx.arc(cx, cy, innerRadius, 0, Math.PI * 2);
    ctx.closePath();
    ctx.fill();
    ctx.globalCompositeOperation = 'source-over';
  }

  function drawHorizontalBarChart(canvasOrId, labels, values, opts) {
    var canvas = getCanvas(canvasOrId);
    if (!canvas || !canvas.getContext) return;

    var ctx = canvas.getContext('2d');
    var width = canvas.width || canvas.clientWidth;
    var height = canvas.height || canvas.clientHeight;

    if (!canvas.width) canvas.width = width;
    if (!canvas.height) canvas.height = height;

    clear(ctx, canvas.width, canvas.height);

    var paddingL = 120;
    var paddingR = 18;
    var paddingT = 18;
    var paddingB = 18;

    var plotW = canvas.width - paddingL - paddingR;
    var plotH = canvas.height - paddingT - paddingB;

    var maxV = 0;
    for (var i = 0; i < values.length; i++) {
      var v = Number(values[i]) || 0;
      if (v > maxV) maxV = v;
    }
    if (maxV === 0) maxV = 1;

    var barH = values.length ? Math.max(10, Math.floor(plotH / values.length) - 6) : 10;

    ctx.font = '12px sans-serif';
    ctx.fillStyle = '#50575e';

    for (var r = 0; r < values.length; r++) {
      var y = paddingT + r * (barH + 6);
      var label = labels && labels[r] ? String(labels[r]) : '';
      var value = Number(values[r]) || 0;
      var w = (plotW * value) / maxV;

      // label
      ctx.textAlign = 'right';
      ctx.fillText(label, paddingL - 8, y + barH - 2);

      // bar
      ctx.fillStyle = (opts && opts.backgroundColor) || '#0073aa';
      ctx.fillRect(paddingL, y, w, barH);

      // value
      ctx.fillStyle = '#50575e';
      ctx.textAlign = 'left';
      ctx.fillText(String(value), paddingL + w + 6, y + barH - 2);
    }
  }

  window.QuestifyCharts = {
    line: drawLineChart,
    doughnut: drawDoughnutChart,
    barHorizontal: drawHorizontalBarChart,
  };
})(window);
