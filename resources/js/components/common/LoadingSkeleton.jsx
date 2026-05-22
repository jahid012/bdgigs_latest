function LoadingSkeleton({ className = "", as: Component = "span" }) {
    const skeletonClassName = ["ui-skeleton", className]
        .filter(Boolean)
        .join(" ");

    return <Component aria-hidden="true" className={skeletonClassName} />;
}

export default LoadingSkeleton;
