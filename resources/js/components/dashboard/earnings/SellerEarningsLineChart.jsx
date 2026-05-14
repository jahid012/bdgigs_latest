import { sellerChartData } from "../../../data/dashboardData.js";
import { buildLineChartGeometry } from "../../../utils/lineChart.js";

const defaultChartConfig = {
    width: 640,
    height: 230,
    paddingX: 34,
    paddingY: 30,
};

function SellerEarningsLineChart({
    ariaLabel = "Seller earnings line chart for the last seven months",
    chartConfig = defaultChartConfig,
    className,
    data = sellerChartData,
    gradientId = "earningsLineGradient",
    showHeader = true,
    summaryLabel = "Net earnings trend",
    summaryValue = "$1,040.80",
    trendLabel = "+18% vs. previous period",
}) {
    const { areaPath, gridLines, linePath, points } = buildLineChartGeometry(
        data,
        chartConfig,
    );
    const chartClassName = ["finance-line-chart", className]
        .filter(Boolean)
        .join(" ");

    return (
        <div className={chartClassName} role="img" aria-label={ariaLabel}>
            {showHeader ? (
                <div className="finance-line-chart-head">
                    <div>
                        <span>{summaryLabel}</span>
                        <strong>{summaryValue}</strong>
                    </div>
                    <p>{trendLabel}</p>
                </div>
            ) : null}
            <svg
                viewBox={`0 0 ${chartConfig.width} ${chartConfig.height}`}
                preserveAspectRatio="none"
            >
                <defs>
                    <linearGradient id={gradientId} x1="0" x2="0" y1="0" y2="1">
                        <stop
                            offset="0%"
                            stopColor="rgba(29, 191, 115, 0.28)"
                        />
                        <stop
                            offset="100%"
                            stopColor="rgba(29, 191, 115, 0.02)"
                        />
                    </linearGradient>
                </defs>
                {gridLines.map((y) => (
                    <path
                        className="finance-line-grid"
                        d={`M${chartConfig.paddingX} ${y}H${chartConfig.width - chartConfig.paddingX}`}
                        key={y}
                    />
                ))}
                <polygon
                    className="finance-line-area"
                    points={areaPath}
                    style={{ fill: `url(#${gradientId})` }}
                />
                <polyline className="finance-line-path" points={linePath} />
                {points.map((point) => (
                    <circle
                        className="finance-line-point"
                        cx={point.x}
                        cy={point.y}
                        key={point.label}
                        r="5"
                    />
                ))}
            </svg>
            <div className="finance-line-labels" aria-hidden="true">
                {data.map((item) => (
                    <span key={item.label}>{item.label}</span>
                ))}
            </div>
        </div>
    );
}

export default SellerEarningsLineChart;
