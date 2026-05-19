import {
    sellerChartData,
    sellerDashboardHighlights,
    sellerMessages,
    sellerOrders,
    sellerPipeline,
    sellerServices,
    sellerStats,
} from "../data/dashboardData.js";
import DashboardPageHeader from "../components/dashboard/DashboardPageHeader.jsx";
import SellerEarningsLineChart from "../components/dashboard/earnings/SellerEarningsLineChart.jsx";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useTranslation } from "react-i18next";
function SellerStatsGrid() {
    const { t } = useTranslation();
    return (
        <section
            className="stats-grid"
            aria-label={t("pages.sellerdashboardpage.sellerDashboardStats")}
        >
            {sellerStats.map((stat) => (
                <article className="card stat-card" key={stat.label}>
                    <span className="stat-icon" aria-hidden="true">
                        <Icon name={stat.icon} />
                    </span>
                    <div>
                        <span>{stat.label}</span>
                        <strong>{stat.value}</strong>
                    </div>
                    <span className="stat-trend">{stat.trend}</span>
                </article>
            ))}
        </section>
    );
}
function SellerOrders({ onNavigate }) {
    const { t } = useTranslation();
    return (
        <article className="card dashboard-card orders-card seller-orders-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.sellerdashboardpage.orderQueue")}
                    </span>
                    <h2>{t("pages.sellerdashboardpage.recentSellerOrders")}</h2>
                </div>
                <a
                    href="/dashboard/seller/orders"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("seller-orders");
                    }}
                >
                    {" "}
                    {t("pages.sellerdashboardpage.manageOrders")}{" "}
                </a>
            </div>
            <div className="orders-table-wrap">
                <table className="orders-table">
                    <thead>
                        <tr>
                            <th>{t("pages.sellerdashboardpage.orderId")}</th>
                            <th>{t("pages.sellerdashboardpage.service")}</th>
                            <th>{t("pages.sellerdashboardpage.buyer")}</th>
                            <th>{t("pages.sellerdashboardpage.status")}</th>
                            <th>{t("pages.sellerdashboardpage.dueDate")}</th>
                            <th>{t("pages.sellerdashboardpage.earnings")}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sellerOrders.map((order) => (
                            <tr key={order.id}>
                                <td data-label="Order ID">
                                    <strong>{order.id}</strong>
                                </td>
                                <td data-label="Service">{order.service}</td>
                                <td data-label="Buyer">{order.buyer}</td>
                                <td data-label="Status">
                                    <span
                                        className={`status-badge ${order.statusClass}`}
                                    >
                                        {order.status}
                                    </span>
                                </td>
                                <td data-label="Due Date">{order.dueDate}</td>
                                <td data-label="Earnings">{order.earnings}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </article>
    );
}
function SellerChartCard({ onNavigate }) {
    const { t } = useTranslation();
    const topValue = Math.max(...sellerChartData.map((bar) => bar.value));
    return (
        <article className="card dashboard-card chart-card seller-chart-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.sellerdashboardpage.revenue")}
                    </span>
                    <h2>{t("pages.sellerdashboardpage.earningsSnapshot")}</h2>
                </div>
                <a
                    href="/dashboard/seller/earnings"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("seller-earnings");
                    }}
                >
                    {" "}
                    {t("pages.sellerdashboardpage.payouts")}{" "}
                </a>
            </div>
            <div className="chart-summary">
                <div>
                    <strong>{t("pages.sellerdashboardpage.5420")}</strong>
                    <span>
                        {t("pages.sellerdashboardpage.earnedThisMonth")}
                    </span>
                </div>
                <span className="status-badge status-completed">
                    {t("pages.sellerdashboardpage.18")}
                </span>
            </div>
            <div className="chart-note">
                <span>
                    {t("pages.sellerdashboardpage.monthlyEarningsTrend")}
                </span>
                <strong>
                    {t("pages.sellerdashboardpage.peak")}{" "}
                    {
                        sellerChartData.find((bar) => bar.value === topValue)
                            ?.label
                    }
                </strong>
            </div>
            <SellerEarningsLineChart
                ariaLabel="Monthly seller earnings trend"
                chartConfig={{
                    width: 640,
                    height: 170,
                    paddingX: 22,
                    paddingY: 20,
                }}
                className="dashboard-line-chart"
                gradientId="snapshotEarningsLineGradient"
                showHeader={false}
            />
        </article>
    );
}
function SellerMessagesPreview({ onNavigate }) {
    const { t } = useTranslation();
    return (
        <article className="card dashboard-card messages-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.sellerdashboardpage.buyerMessages")}
                    </span>
                    <h2>{t("pages.sellerdashboardpage.inboxPreview")}</h2>
                </div>
                <a
                    href="/dashboard/seller/messages"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("seller-messages");
                    }}
                >
                    {" "}
                    {t("pages.sellerdashboardpage.openInbox")}{" "}
                </a>
            </div>
            <div className="messages-list">
                {sellerMessages.map((message) => (
                    <article className="message-item" key={message.name}>
                        <span className="avatar">{message.initials}</span>
                        <div>
                            <h3>{message.name}</h3>
                            <p>{message.message}</p>
                            <span className="message-time">{message.time}</span>
                        </div>
                    </article>
                ))}
            </div>
        </article>
    );
}
function SellerPipelineCard({ onNavigate }) {
    const { t } = useTranslation();
    return (
        <article className="card dashboard-card seller-pipeline-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.sellerdashboardpage.deliveryFocus")}
                    </span>
                    <h2>{t("pages.sellerdashboardpage.nextMilestones")}</h2>
                </div>
                <a
                    href="/dashboard/seller/orders"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("seller-orders");
                    }}
                >
                    {" "}
                    {t("pages.sellerdashboardpage.calendar")}{" "}
                </a>
            </div>
            <div className="seller-pipeline-list">
                {sellerPipeline.map((item) => (
                    <article className="seller-pipeline-item" key={item.title}>
                        <div className="seller-pipeline-top">
                            <div>
                                <h3>{item.title}</h3>
                                <p>{item.detail}</p>
                            </div>
                            <span>{item.due}</span>
                        </div>
                        <div
                            className="seller-progress-track"
                            aria-label={`${item.title} progress`}
                        >
                            <span
                                className="seller-progress-fill"
                                style={{
                                    "--progress": `${item.progress}%`,
                                }}
                            ></span>
                        </div>
                    </article>
                ))}
            </div>
        </article>
    );
}
function SellerServices({ onNavigate }) {
    const { t } = useTranslation();
    return (
        <article className="card dashboard-card recommend-card seller-services-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.sellerdashboardpage.gigPerformance")}
                    </span>
                    <h2>{t("pages.sellerdashboardpage.activeServices")}</h2>
                </div>
                <a
                    href="/dashboard/seller/services"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("seller-services");
                    }}
                >
                    {" "}
                    {t("pages.sellerdashboardpage.manageGigs")}{" "}
                </a>
            </div>
            <div className="recommend-grid">
                {sellerServices.map((service) => (
                    <article
                        className="mini-service seller-service"
                        key={service.title}
                    >
                        <div className="mini-thumb">
                            <img
                                src={service.image}
                                alt={`${service.title} preview`}
                                loading="lazy"
                                decoding="async"
                            />
                            <span>{service.tag}</span>
                        </div>
                        <div>
                            <h3>{service.title}</h3>
                            <p>
                                {service.category}{" "}
                                <Rating value={service.rating} />
                            </p>
                            <div className="seller-service-meta">
                                <span>{service.orders}</span>
                                <span>{service.conversion}</span>
                            </div>
                        </div>
                        <div className="mini-service-footer">
                            <span className="price">
                                <span>
                                    {t("pages.sellerdashboardpage.startsAt")}
                                </span>{" "}
                                <strong>{service.price}</strong>
                            </span>
                            <span className="mini-delivery">
                                {service.delivery}
                            </span>
                            <span
                                className={`status-badge ${service.statusClass}`}
                            >
                                {service.status}
                            </span>
                        </div>
                    </article>
                ))}
            </div>
        </article>
    );
}
function SellerDashboardPage({ onNavigate }) {
    const { t } = useTranslation();
    return (
        <main className="dashboard-content marketplace-dashboard-content">
            <DashboardPageHeader
                className="dashboard-overview-hero seller-overview-hero"
                eyebrow="Seller workspace"
                title={t("pages.sellerdashboardpage.welcomeBackJahid")}
                titleId="sellerDashboardTitle"
                description="Monitor active orders, protect delivery momentum, and optimize your best-selling services from one focused seller hub."
                stats={sellerDashboardHighlights}
                actions={
                    <>
                        <a
                            className="btn btn-primary"
                            href="/dashboard/seller/services/create"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate("seller-gig-create");
                            }}
                        >
                            {" "}
                            {t("pages.sellerdashboardpage.createNewGig")}{" "}
                        </a>
                        <a
                            className="btn btn-light"
                            href="/"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate("home");
                            }}
                        >
                            {" "}
                            {t(
                                "pages.sellerdashboardpage.viewMarketplace",
                            )}{" "}
                        </a>
                    </>
                }
            />

            <SellerStatsGrid />

            <section className="dashboard-grid seller-grid">
                <SellerOrders onNavigate={onNavigate} />
                <SellerChartCard onNavigate={onNavigate} />
                <SellerMessagesPreview onNavigate={onNavigate} />
                <SellerPipelineCard onNavigate={onNavigate} />
                <SellerServices onNavigate={onNavigate} />
            </section>
        </main>
    );
}
export default SellerDashboardPage;
