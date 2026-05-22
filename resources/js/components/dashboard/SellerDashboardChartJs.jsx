import Chart from "chart.js/auto";
import { useEffect, useRef } from "react";

function SellerDashboardChartJs({ ariaLabel, data = [] }) {
    const canvasRef = useRef(null);

    useEffect(() => {
        if (!canvasRef.current) return undefined;

        const chart = new Chart(canvasRef.current, {
            type: "line",
            data: {
                labels: data.map((item) => item.label),
                datasets: [
                    {
                        data: data.map((item) => item.value),
                        borderColor: "#1dbf73",
                        backgroundColor: "rgba(29, 191, 115, 0.14)",
                        borderWidth: 3,
                        fill: true,
                        pointBackgroundColor: "#ffffff",
                        pointBorderColor: "#1dbf73",
                        pointBorderWidth: 3,
                        pointHoverRadius: 6,
                        pointRadius: 4,
                        tension: 0.34,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: "index",
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) =>
                                `$${Number(context.parsed.y || 0).toLocaleString()}`,
                        },
                    },
                },
                scales: {
                    x: {
                        border: {
                            display: false,
                        },
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: "#64748b",
                            font: {
                                size: 11,
                                weight: 700,
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false,
                        },
                        grid: {
                            color: "rgba(203, 213, 225, 0.72)",
                            borderDash: [6, 6],
                        },
                        ticks: {
                            color: "#64748b",
                            callback: (value) =>
                                `$${Number(value || 0).toLocaleString()}`,
                            font: {
                                size: 11,
                                weight: 700,
                            },
                        },
                    },
                },
            },
        });

        return () => chart.destroy();
    }, [data]);

    return (
        <div className="seller-dashboard-chartjs">
            <canvas aria-label={ariaLabel} ref={canvasRef} role="img" />
        </div>
    );
}

export default SellerDashboardChartJs;
