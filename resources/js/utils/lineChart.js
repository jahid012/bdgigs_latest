export function buildLineChartGeometry(data, config) {
    const { width, height, paddingX, paddingY, gridLineCount = 4 } = config;
    const values = data.map((item) => item.value);
    const maxValue = Math.max(...values);
    const minValue = Math.min(...values);
    const valueRange = maxValue - minValue || 1;
    const horizontalRange = width - paddingX * 2;
    const verticalRange = height - paddingY * 2;
    const lastIndex = Math.max(data.length - 1, 1);

    const points = data.map((item, index) => {
        const x = paddingX + (index / lastIndex) * horizontalRange;
        const y =
            height -
            paddingY -
            ((item.value - minValue) / valueRange) * verticalRange;

        return { ...item, x, y };
    });

    const linePath = points.map((point) => `${point.x},${point.y}`).join(" ");
    const areaPath = `${paddingX},${height - paddingY} ${linePath} ${width - paddingX},${height - paddingY}`;
    const gridLines = Array.from({ length: gridLineCount }, (_, index) => {
        const step = verticalRange / Math.max(gridLineCount - 1, 1);
        return paddingY + index * step;
    });

    return { areaPath, gridLines, linePath, points };
}
