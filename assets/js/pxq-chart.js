(function (w) {
    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function f2(n) { return Number(n).toFixed(2); }
    function fmtD(v, long) {
        var d = new Date(v * 1000), dd = ('0' + d.getUTCDate()).slice(-2) + '.' + ('0' + (d.getUTCMonth() + 1)).slice(-2);
        return long ? dd + '.' + d.getUTCFullYear() : dd;
    }

    w.PxqChart = function (o) {
        var el = document.getElementById(o.el), empty = document.getElementById(o.empty), stats = document.getElementById(o.stats);
        var u = null, tip = null, ro = null, data = null, btns = o.buttons ? document.querySelectorAll(o.buttons) : [];
        if (!el) return null;

        function setStats(d) {
            if (!stats || !d[1].length) return;
            var b = d[1], mn = Math.min.apply(null, b), mx = Math.max.apply(null, b), ch = (b[b.length - 1] - b[0]) / b[0] * 100;
            var set = function (id, v) { var n = document.getElementById(id); if (n) n.textContent = v; };
            set(o.stats + '-min', f2(mn)); set(o.stats + '-max', f2(mx));
            var c = document.getElementById(o.stats + '-chg');
            if (c) {
                c.textContent = (ch > 0 ? '+' : '') + ch.toFixed(2) + '%';
                c.className = 'font-extrabold tabular-nums ' + (ch > 0 ? 'text-green-600' : ch < 0 ? 'text-red-600' : 'text-slate-900');
            }
        }

        function draw(points) {
            if (u) { u.destroy(); u = null; }
            if (ro) { ro.disconnect(); ro = null; }
            el.innerHTML = '';
            if (!points || points.length < 2 || typeof uPlot === 'undefined') {
                el.classList.add('hidden'); if (stats) stats.classList.add('hidden'); if (empty) empty.classList.remove('hidden');
                return;
            }
            el.classList.remove('hidden'); if (stats) stats.classList.remove('hidden'); if (empty) empty.classList.add('hidden');
            data = [points.map(function (p) { return Date.parse(p.date + 'T00:00:00Z') / 1000; }), points.map(function (p) { return p.buy; }), points.map(function (p) { return p.sell; })];
            setStats(data);
            tip = document.createElement('div');
            tip.className = 'pointer-events-none absolute z-10 hidden whitespace-nowrap rounded-xl border border-slate-100 bg-white px-3 py-2 text-xs shadow-[0_10px_30px_rgba(15,23,42,.15)]';
            el.appendChild(tip);
            var h = el.clientHeight || 300, font = '600 12px Inter, "Noto Sans Armenian", sans-serif';
            var grad = function (rgb) {
                return function (uu) {
                    var g = uu.ctx.createLinearGradient(0, uu.bbox.top, 0, uu.bbox.top + uu.bbox.height);
                    g.addColorStop(0, 'rgba(' + rgb + ',.22)'); g.addColorStop(1, 'rgba(' + rgb + ',0)');
                    return g;
                };
            };
            u = new uPlot({
                width: el.clientWidth, height: h, legend: { show: false }, padding: [12, 8, 0, 0],
                cursor: { drag: { x: false, y: false }, points: { size: 9, fill: '#fff', width: 2 } },
                scales: { x: { time: true }, y: { range: function (uu, mn, mx) { var p = (mx - mn) * 0.15 || 1; return [mn - p, mx + p]; } } },
                axes: [
                    { stroke: '#0f172a', font: font, grid: { stroke: '#eef0f4', width: 1 }, ticks: { show: false }, gap: 8, values: function (uu, s) { return s.map(function (v) { return fmtD(v, false); }); } },
                    { stroke: '#0f172a', font: font, grid: { stroke: '#eef0f4', width: 1 }, ticks: { show: false }, size: 56, values: function (uu, s) { return s.map(function (v) { return f2(v); }); } }
                ],
                series: [{}, { label: o.t.buy, stroke: '#2563eb', width: 2.5, fill: grad('37,99,235'), points: { show: false } },
                             { label: o.t.sell, stroke: '#6366f1', width: 2, dash: [6, 5], points: { show: false } }],
                hooks: { setCursor: [function (uu) {
                    var i = uu.cursor.idx;
                    if (i == null) { tip.classList.add('hidden'); return; }
                    tip.classList.remove('hidden');
                    tip.innerHTML = '<div class="font-bold text-slate-900">' + fmtD(data[0][i], true) + '</div>' +
                        '<div class="mt-0.5 flex items-center gap-1.5"><i class="inline-block h-2 w-2 rounded-full bg-blue-600"></i>' + esc(o.t.buy) + '<b class="ml-auto pl-3 tabular-nums">' + f2(data[1][i]) + '</b></div>' +
                        '<div class="flex items-center gap-1.5"><i class="inline-block h-2 w-2 rounded-full bg-indigo-500"></i>' + esc(o.t.sell) + '<b class="ml-auto pl-3 tabular-nums">' + f2(data[2][i]) + '</b></div>';
                    var x = uu.valToPos(data[0][i], 'x'), y = uu.valToPos(data[1][i], 'y'), tw = tip.offsetWidth;
                    tip.style.left = Math.max(4, Math.min(el.clientWidth - tw - 4, x - tw / 2)) + 'px';
                    tip.style.top = Math.max(0, y - 78) + 'px';
                }] }
            }, data, el);
            ro = new ResizeObserver(function () { if (u) u.setSize({ width: el.clientWidth, height: el.clientHeight || h }); });
            ro.observe(el);
        }

        function load(days) {
            var q = 'cur=' + encodeURIComponent(o.cur) + '&days=' + days + (o.bank ? '&bank=' + encodeURIComponent(o.bank) : '');
            fetch('/history.php?' + q, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); }).then(function (j) { draw(j.points || []); }).catch(function () { draw([]); });
        }

        Array.prototype.forEach.call(btns, function (b) {
            b.addEventListener('click', function () {
                Array.prototype.forEach.call(btns, function (x) {
                    var on = x === b;
                    x.setAttribute('aria-pressed', on ? 'true' : 'false');
                    x.classList.toggle('bg-white', on); x.classList.toggle('shadow', on);
                });
                load(+b.getAttribute('data-days'));
            });
        });

        var ready = (document.fonts && document.fonts.load) ? document.fonts.load('600 12px Inter').catch(function () {}) : Promise.resolve();
        ready.then(function () { if (o.points && o.points.length) draw(o.points); else load(o.days || 30); });
        return { setCur: function (c, bank) { o.cur = c; if (bank !== undefined) o.bank = bank; load(o.days || 30); } };
    };
})(window);
