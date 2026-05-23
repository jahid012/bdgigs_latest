<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.Chart) {
            return;
        }

        document.querySelectorAll('[data-admin-line-chart]').forEach((canvas) => {
            const labels = JSON.parse(canvas.dataset.labels || '[]');
            const rawDatasets = JSON.parse(canvas.dataset.datasets || '[]');
            const suggestedMax = Number(canvas.dataset.max || 0);

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: rawDatasets.map((dataset) => ({
                        label: dataset.label,
                        data: dataset.values,
                        borderColor: dataset.color,
                        backgroundColor: dataset.fill || dataset.color,
                        fill: Boolean(dataset.fill),
                        tension: 0.38,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: dataset.color,
                        pointBorderWidth: 2,
                        valuePrefix: dataset.valuePrefix || '',
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: '#222326',
                            borderColor: 'rgba(255, 255, 255, 0.12)',
                            borderWidth: 1,
                            displayColors: true,
                            padding: 10,
                            callbacks: {
                                label: (context) => {
                                    const prefix = context.dataset.valuePrefix || '';
                                    return `${context.dataset.label}: ${prefix}${Number(context.parsed.y || 0).toLocaleString()}`;
                                },
                            },
                            titleFont: {
                                size: 12,
                                weight: '800',
                            },
                            bodyFont: {
                                size: 12,
                                weight: '700',
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            border: {
                                display: false,
                            },
                            ticks: {
                                color: '#62646a',
                                font: {
                                    size: 11,
                                    weight: '700',
                                },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: suggestedMax || undefined,
                            grid: {
                                color: '#eef0f2',
                            },
                            border: {
                                display: false,
                            },
                            ticks: {
                                color: '#62646a',
                                font: {
                                    size: 11,
                                    weight: '700',
                                },
                            },
                        },
                    },
                },
            });
        });
    });
</script>
