/* GoPath Exam Portal — Analytics Charts Engine */
(function($){
    'use strict';

    if (typeof GEP_Analytics_Data === 'undefined') return;

    const data = GEP_Analytics_Data;

    // ── Score Gauge (doughnut) ──────────────────────────────────────────────
    const gaugeCtx = document.getElementById('gep-score-gauge');
    if (gaugeCtx) {
        const pct = parseFloat(data.percentage) || 0;
        new Chart(gaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [pct, 100 - pct],
                    backgroundColor: [
                        pct >= 80 ? '#10b981' : pct >= 50 ? '#f59e0b' : '#ef4444',
                        '#1e293b'
                    ],
                    borderWidth: 0,
                    cutout: '80%'
                }]
            },
            plugins: [{
                id: 'centerText',
                afterDraw(chart) {
                    const { ctx, chartArea: {top, bottom, left, right} } = chart;
                    const cx = (left+right)/2, cy = (top+bottom)/2;
                    ctx.save();
                    ctx.font = 'bold 28px Inter, sans-serif';
                    ctx.fillStyle = '#f1f5f9';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(pct.toFixed(1)+'%', cx, cy);
                    ctx.restore();
                }
            }],
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, animation: { duration: 800 } }
        });
    }

    // ── Correct / Wrong / Skipped Pie ──────────────────────────────────────
    const pieCtx = document.getElementById('gep-cws-pie');
    if (pieCtx && data.correct !== undefined) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Wrong', 'Skipped'],
                datasets: [{
                    data: [data.correct, data.wrong, data.skipped],
                    backgroundColor: ['#10b981','#ef4444','#94a3b8'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 12 } } }
                },
                animation: { duration: 800 }
            }
        });
    }

    // ── Subject-wise Score Bar ─────────────────────────────────────────────
    const subjectCtx = document.getElementById('gep-subject-bar');
    if (subjectCtx && data.section_scores && data.section_scores.length) {
        const labels = data.section_scores.map(s => s.name);
        const scores = data.section_scores.map(s => parseFloat(s.score)||0);
        const maxes  = data.section_scores.map(s => parseInt(s.total_marks)||0);
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Your Score', data: scores, backgroundColor: '#6366f1', borderRadius: 8 },
                    { label: 'Max Marks',  data: maxes,  backgroundColor: '#1e293b', borderRadius: 8 }
                ]
            },
            plugins: [{
                id: 'barLabels',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const val = dataset.data[index];
                            const x = bar.x;
                            const y = bar.y - 6;
                            ctx.save();
                            ctx.font = 'bold 10px Inter, sans-serif';
                            ctx.fillStyle = '#94a3b8';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(val, x, y);
                            ctx.restore();
                        });
                    });
                }
            }],
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#94a3b8' } } },
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: '#334155' } }
                },
                animation: { duration: 800 }
            }
        });
    }

    // ── Progress Line Chart (attempt history) ──────────────────────────────
    const progressCtx = document.getElementById('gep-progress-line');
    if (progressCtx && data.progress && data.progress.length) {
        const labels = data.progress.map((p, i) => 'Attempt ' + (i+1));
        const scores = data.progress.map(p => parseFloat(p.percentage)||0);
        new Chart(progressCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Score %',
                    data: scores,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.12)',
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } },
                    y: { min: 0, max: 100, ticks: { color: '#94a3b8', callback: v => v+'%' }, grid: { color: '#334155' } }
                },
                plugins: { legend: { display: false } },
                animation: { duration: 800 }
            }
        });
    }

    // ── Topic Heatmap Bars ──────────────────────────────────────────────────
    const heatCtx = document.getElementById('gep-topic-heatmap');
    if (heatCtx && data.topic_stats) {
        const topics = Object.values(data.topic_stats);
        if (topics.length) {
            const labels = topics.map(t => t.name || 'Topic');
            const accuracies = topics.map(t => t.total > 0 ? Math.round((t.correct/t.total)*100) : 0);
            const colors = accuracies.map(a => a >= 70 ? '#10b981' : a >= 40 ? '#f59e0b' : '#ef4444');
            new Chart(heatCtx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{ label: 'Accuracy %', data: accuracies, backgroundColor: colors, borderRadius: 6 }]
                },
                plugins: [{
                    id: 'valueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea: { left } } = chart;
                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            meta.data.forEach((bar, index) => {
                                const val = dataset.data[index];
                                const x = Math.max(bar.x + 5, left + 5);
                                const y = bar.y;
                                ctx.save();
                                ctx.font = 'bold 11px Inter, sans-serif';
                                ctx.fillStyle = '#94a3b8';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(val + '%', x, y);
                                ctx.restore();
                            });
                        });
                    }
                }],
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { min: 0, max: 100, ticks: { color: '#94a3b8', callback: v => v+'%' }, grid: { color: '#334155' } },
                        y: { ticks: { color: '#94a3b8' }, grid: { color: '#1e293b' } }
                    },
                    animation: { duration: 800 }
                }
            });
        }
    }

})(jQuery);
