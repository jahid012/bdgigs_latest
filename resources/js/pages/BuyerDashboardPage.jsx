import DashboardPageHeader from "../components/dashboard/DashboardPageHeader.jsx";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useTranslation } from "react-i18next";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useEffect } from "react";
function StatsGrid() {
    const { t } = useTranslation();
    const stats = useDashboardStore((state) => state.stats);
    return (
        <section
            className="stats-grid"
            aria-label={t("pages.buyerdashboardpage.dashboardStats")}
        >
            {stats.map((stat) => (
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
function RecentOrders({ onNavigate }) {
    const { t } = useTranslation();
    const orders = useDashboardStore((state) => state.orders);
    return (
        <article className="card dashboard-card orders-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.buyerdashboardpage.orderActivity")}
                    </span>
                    <h2>{t("pages.buyerdashboardpage.recentOrders")}</h2>
                </div>
                <a
                    href="/dashboard/orders"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("orders");
                    }}
                >
                    {" "}
                    {t("pages.buyerdashboardpage.viewAll")}{" "}
                </a>
            </div>
            <div className="orders-table-wrap">
                <table className="orders-table">
                    <thead>
                        <tr>
                            <th>{t("pages.buyerdashboardpage.orderId")}</th>
                            <th>{t("pages.buyerdashboardpage.service")}</th>
                            <th>{t("pages.buyerdashboardpage.seller")}</th>
                            <th>{t("pages.buyerdashboardpage.status")}</th>
                            <th>{t("pages.buyerdashboardpage.dueDate")}</th>
                            <th>{t("pages.buyerdashboardpage.price")}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.map((order) => (
                            <tr key={order.id}>
                                <td data-label="Order ID">
                                    <strong>{order.id}</strong>
                                </td>
                                <td data-label="Service">{order.service}</td>
                                <td data-label="Seller">{order.seller}</td>
                                <td data-label="Status">
                                    <span
                                        className={`status-badge ${order.statusClass}`}
                                    >
                                        {order.status}
                                    </span>
                                </td>
                                <td data-label="Due Date">{order.dueDate}</td>
                                <td data-label="Price">{order.price}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </article>
    );
}
function ChartCard({ onNavigate }) {
    const { t } = useTranslation();
    const chartData = useDashboardStore((state) => state.chartData);
    const topValue = Math.max(...chartData.map((bar) => bar.value));
    return (
        <article className="card dashboard-card chart-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.buyerdashboardpage.cashFlow")}
                    </span>
                    <h2>{t("pages.buyerdashboardpage.earningsSpending")}</h2>
                </div>
                <a
                    href="/dashboard/payments"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("payments");
                    }}
                >
                    {" "}
                    {t("pages.buyerdashboardpage.report")}{" "}
                </a>
            </div>
            <div className="chart-summary">
                <div>
                    <strong>{t("pages.buyerdashboardpage.2860")}</strong>
                    <span>{t("pages.buyerdashboardpage.spentThisPeriod")}</span>
                </div>
                <span className="status-badge status-completed">
                    {t("pages.buyerdashboardpage.14")}
                </span>
            </div>
        </article>
    );
}
function MessagesPreview({ onNavigate }) {
    const { t } = useTranslation();
    const messages = useDashboardStore((state) => state.messages);
    return (
        <article className="card dashboard-card messages-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.buyerdashboardpage.sellerReplies")}
                    </span>
                    <h2>{t("pages.buyerdashboardpage.messagesPreview")}</h2>
                </div>
                <a
                    href="/dashboard/messages"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("messages");
                    }}
                >
                    {" "}
                    {t("pages.buyerdashboardpage.inbox")}{" "}
                </a>
            </div>
            <div className="messages-list">
                {messages.map((message) => (
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
function RecommendedServices({ onNavigate }) {
    const { t } = useTranslation();
    const recommendedServices = useDashboardStore(
        (state) => state.recommendedServices,
    );
    return (
        <article className="card dashboard-card recommend-card">
            <div className="card-heading">
                <div>
                    <span className="card-kicker">
                        {t("pages.buyerdashboardpage.matchedForYou")}
                    </span>
                    <h2>{t("pages.buyerdashboardpage.recommendedServices")}</h2>
                </div>
                <a
                    href="/#services"
                    onClick={(event) => {
                        event.preventDefault();
                        onNavigate("home", "#services");
                    }}
                >
                    {" "}
                    {t("pages.buyerdashboardpage.browseMore")}{" "}
                </a>
            </div>
            <div className="recommend-grid">
                {recommendedServices.map((service) => (
                    <article className="mini-service" key={service.title}>
                        <div className="mini-thumb">
                            <img
                                src={service.image}
                                alt={`${service.title} service preview`}
                                loading="lazy"
                                decoding="async"
                            />
                            <span>{service.tag}</span>
                        </div>
                        <div>
                            <h3>{service.title}</h3>
                            <p>
                                {service.seller}{" "}
                                <Rating value={service.rating} />
                            </p>
                        </div>
                        <div className="mini-service-footer">
                            <span className="price">
                                <span>
                                    {t("pages.buyerdashboardpage.from")}
                                </span>{" "}
                                <strong>{service.price}</strong>
                            </span>
                            <span className="mini-delivery">
                                {service.delivery}
                            </span>
                            <a className="tag" href="#">
                                {" "}
                                {t("pages.buyerdashboardpage.save")}{" "}
                            </a>
                        </div>
                    </article>
                ))}
            </div>
        </article>
    );
}
function BuyerDashboardPage({ onNavigate }) {
    const { t } = useTranslation();
    const dashboardHighlights = useDashboardStore(
        (state) => state.dashboardHighlights,
    );
    const fetchOrders = useDashboardStore((state) => state.fetchOrders);
    const fetchConversations = useDashboardStore(
        (state) => state.fetchConversations,
    );

    useEffect(() => {
        fetchOrders("buyer");
        fetchConversations("buyer");
    }, [fetchConversations, fetchOrders]);

    return (
        <main className="dashboard-content marketplace-dashboard-content">
            <DashboardPageHeader
                className="dashboard-overview-hero buyer-overview-hero"
                eyebrow="Buyer workspace"
                title={t("pages.buyerdashboardpage.welcomeBackJahid")}
                titleId="dashboardTitle"
                description="Track priority orders, review seller updates, and discover services matched to your active projects."
                stats={dashboardHighlights}
                actions={
                    <>
                        <a
                            className="btn btn-primary"
                            href="/#services"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate("home", "#services");
                            }}
                        >
                            {" "}
                            {t("pages.buyerdashboardpage.exploreServices")}{" "}
                        </a>
                        <a
                            className="btn btn-secondary"
                            href="/dashboard/orders"
                            onClick={(event) => {
                                event.preventDefault();
                                onNavigate("orders");
                            }}
                        >
                            {" "}
                            {t("pages.buyerdashboardpage.viewOrders")}{" "}
                        </a>
                    </>
                }
            />

            <StatsGrid />

            <section className="dashboard-grid">
                <RecentOrders onNavigate={onNavigate} />
                <ChartCard onNavigate={onNavigate} />
                <MessagesPreview onNavigate={onNavigate} />
                <RecommendedServices onNavigate={onNavigate} />
            </section>
        </main>
    );
}
export default BuyerDashboardPage;
