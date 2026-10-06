/* report.js - grafik penjualan harian di halaman Laporan */
(() => {
  const el = document.getElementById('reportChart');
  if (!el) return;
  const rows = JSON.parse(el.dataset.rows);
  const css = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
  let chart;

  function draw() {
    chart?.destroy();
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = css('--muted');
    chart = new Chart(el, {
      type: 'bar',
      data: { labels: rows.map(r => r.d.slice(8) + '/' + r.d.slice(5, 7)), datasets: [{ label: 'Penjualan', data: rows.map(r => +r.v), backgroundColor: css('--accent'), borderRadius: 5 }] },
      options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' Rp ' + new Intl.NumberFormat('id-ID').format(c.parsed.y) } } },
        scales: { x: { grid: { display: false } }, y: { grid: { color: css('--border') }, border: { display: false }, ticks: { font: { family: "'Geist Mono', monospace", size: 11 }, callback: v => v >= 1e6 ? (v / 1e6) + ' jt' : v >= 1e3 ? (v / 1e3) + ' rb' : v } } },
      },
    });
  }
  document.addEventListener('themechange', draw);
  draw();
})();