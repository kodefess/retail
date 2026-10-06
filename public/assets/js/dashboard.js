/* ==========================================================================
   dashboard.js - chart interaktif di dashboard (Chart.js)
   Alur: ambil JSON dari /api/dashboard?range=N  ->  gambar 3 chart
   ========================================================================== */
(() => {
  const css = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
  const rupiah = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
  const short = v => v >= 1e9 ? (v / 1e9).toFixed(1) + ' M' : v >= 1e6 ? (v / 1e6).toFixed(1) + ' jt' : v >= 1e3 ? (v / 1e3).toFixed(0) + ' rb' : v;

  let range = 7, data = null, charts = [];

  async function load() {
    document.querySelectorAll('.chart-box').forEach(b => b.classList.add('loading'));
    const res = await fetch('/api/dashboard?range=' + range, { headers: { Accept: 'application/json' } });
    data = await res.json();
    document.querySelectorAll('.chart-box').forEach(b => b.classList.remove('loading'));
    document.getElementById('rangeSales').textContent = rupiah(data.totals.sales);
    document.getElementById('rangeProfit').textContent = rupiah(data.totals.profit);
    draw();
  }

  function draw() {
    if (!data) return;
    charts.forEach(c => c.destroy());
    charts = [];

    const accent = css('--accent'), muted = css('--muted'), grid = css('--border'), surface = css('--surface');
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = muted;

    const tooltip = { callbacks: { label: c => ' ' + c.dataset.label + ': ' + rupiah(c.parsed.y ?? c.parsed) }, padding: 10, cornerRadius: 8 };
    const yAxis = { grid: { color: grid }, border: { display: false }, ticks: { callback: short, font: { family: "'Geist Mono', monospace", size: 11 } } };
    const xAxis = { grid: { display: false }, ticks: { maxTicksLimit: 10, font: { size: 11 } } };

    // Chart 1: penjualan (area) & laba (garis)
    const ctx1 = document.getElementById('salesChart').getContext('2d');
    const fill = ctx1.createLinearGradient(0, 0, 0, 300);
    fill.addColorStop(0, accent + '40'); fill.addColorStop(1, accent + '00');
    charts.push(new Chart(ctx1, {
      type: 'line',
      data: { labels: data.labels, datasets: [
        { label: 'Penjualan', data: data.sales, borderColor: accent, backgroundColor: fill, fill: true, tension: .35, borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5 },
        { label: 'Laba', data: data.profit, borderColor: '#f59e0b', borderDash: [5, 4], tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 },
      ] },
      options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip }, scales: { x: xAxis, y: yAxis } },
    }));

    // Chart 2: kas masuk vs keluar
    charts.push(new Chart(document.getElementById('cashChart'), {
      type: 'bar',
      data: { labels: data.labels, datasets: [
        { label: 'Masuk', data: data.income, backgroundColor: accent, borderRadius: 4 },
        { label: 'Keluar', data: data.expense, backgroundColor: '#f87171', borderRadius: 4 },
      ] },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip }, scales: { x: { ...xAxis, stacked: false }, y: yAxis } },
    }));

    // Chart 3: produk terlaris (donat)
    const top = document.getElementById('topChart');
    const empty = document.getElementById('topEmpty');
    if (!data.top.length) { top.classList.add('d-none'); empty.classList.remove('d-none'); return; }
    top.classList.remove('d-none'); empty.classList.add('d-none');
    charts.push(new Chart(top, {
      type: 'doughnut',
      data: { labels: data.top.map(t => t.name), datasets: [{ data: data.top.map(t => +t.revenue), backgroundColor: [accent, '#f59e0b', '#6366f1', '#ec4899', '#94a3b8'], borderColor: surface, borderWidth: 3 }] },
      options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + rupiah(c.parsed) } } } },
    }));
  }

  // tombol 7 / 30 / 90 hari
  document.querySelectorAll('[data-range]').forEach(btn => btn.addEventListener('click', () => {
    document.querySelectorAll('[data-range]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    range = +btn.dataset.range;
    load();
  }));

  document.addEventListener('themechange', draw); // gambar ulang saat tema berganti
  load();
})();