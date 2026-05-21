import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { orderSupportLinks } from "../data/orderDetailsData.js";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useConversationLauncher } from "../hooks/useConversationLauncher.js";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../api/apiClient.js";
const tabs = [
    {
        id: "activity",
        label: "Activity",
    },
    {
        id: "details",
        label: "Details",
    },
    {
        id: "requirements",
        label: "Requirements",
    },
];
function OrderDetailsPage({ variant = "buyer" }) {
    const { orderId } = useParams();
    const isSeller = variant === "seller";
    const launchConversation = useConversationLauncher();
    const [activeTab, setActiveTab] = useState("details");
    const [conversationStatus, setConversationStatus] = useState("");
    const [order, setOrder] = useState(null);
    const [loadError, setLoadError] = useState("");

    useEffect(() => {
        apiRequest(
            `/api/orders/${encodeURIComponent(orderId)}?role=${isSeller ? "seller" : "buyer"}`,
        )
            .then(setOrder)
            .catch((error) =>
                setLoadError(error.message || "This order is unavailable."),
            );
    }, [isSeller, orderId]);

    const openOrderConversation = async () => {
        if (!order) return;
        setConversationStatus("Opening conversation...");

        try {
            await launchConversation({
                targetName: order.counterpartyName,
                targetSlug: order.counterpartyHandle?.replace("@", ""),
                contextType: "order",
                contextId: orderId || order.orderNumber,
            });
        } catch (error) {
            setConversationStatus(
                error.message || "This order conversation is unavailable.",
            );
        }
    };

    if (loadError) {
        return (
            <main className="dashboard-content order-details-page">
                <div className="order-detail-card">
                    <h1>Order unavailable</h1>
                    <p>{loadError}</p>
                </div>
            </main>
        );
    }

    if (!order) {
        return (
            <main className="dashboard-content order-details-page">
                <p className="messages-empty">Loading order details...</p>
            </main>
        );
    }

    return (
        <main className="dashboard-content order-details-page">
            <div className="order-details-shell">
                <section
                    className="order-details-main"
                    aria-label={`Order ${orderId || order.orderNumber}`}
                >
                    <OrderTabs activeTab={activeTab} onChange={setActiveTab} />
                    {activeTab === "activity" ? (
                        <OrderActivity order={order} isSeller={isSeller} />
                    ) : null}
                    {activeTab === "details" ? (
                        <OrderDetailsPanel order={order} />
                    ) : null}
                    {activeTab === "requirements" ? (
                        <OrderRequirementsPanel
                            requirements={order.requirements || []}
                        />
                    ) : null}
                </section>

                <OrderDetailsSidebar
                    conversationStatus={conversationStatus}
                    onOpenConversation={openOrderConversation}
                    order={order}
                />
            </div>
        </main>
    );
}
function OrderTabs({ activeTab, onChange }) {
    const { t } = useTranslation();
    return (
        <nav
            className="order-detail-tabs"
            aria-label={t("pages.orderdetailspage.orderDetailSections")}
        >
            {tabs.map((tab) => (
                <button
                    className={activeTab === tab.id ? "active" : ""}
                    type="button"
                    aria-pressed={activeTab === tab.id}
                    key={tab.id}
                    onClick={() => onChange(tab.id)}
                >
                    {tab.label}
                </button>
            ))}
        </nav>
    );
}
function OrderDetailsPanel({ order }) {
    const { t } = useTranslation();
    return (
        <article className="order-detail-card order-invoice-card">
            <div className="order-detail-card-head">
                <div>
                    <h1>{order.serviceTitle}</h1>
                    <p>
                        {" "}
                        {t("pages.orderdetailspage.orderedBy")}{" "}
                        <strong>{order.orderedBy}</strong>{" "}
                        <a href="#history">
                            {t("pages.orderdetailspage.viewHistory")}
                        </a>
                        <span>
                            {t("pages.orderdetailspage.dateOrdered")}{" "}
                            <strong>{order.dateOrdered}</strong>
                        </span>
                    </p>
                </div>
                <div className="order-total-block">
                    <span>{t("pages.orderdetailspage.totalPrice")}</span>
                    <strong>{order.totalPrice}</strong>
                </div>
            </div>

            <div className="order-number-row">
                <span>{t("pages.orderdetailspage.orderNumber")}</span>
                <strong>{order.orderNumber}</strong>
            </div>

            {order.orderBullets?.length ? (
                <ol className="order-bullet-list">
                    {order.orderBullets.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ol>
            ) : null}

            <div
                className="order-item-table"
                role="table"
                aria-label={t("pages.orderdetailspage.orderItemDetails")}
            >
                <div className="order-item-table-head" role="row">
                    <strong>{t("pages.orderdetailspage.item")}</strong>
                    <strong>{t("pages.orderdetailspage.qty")}</strong>
                    <strong>{t("pages.orderdetailspage.duration")}</strong>
                    <strong>{t("pages.orderdetailspage.price")}</strong>
                </div>
                <div className="order-item-table-row" role="row">
                    <div>
                        <strong>{order.serviceTitle}</strong>
                        <p>{order.itemSummary}</p>
                        <span>{order.revisions}</span>
                    </div>
                    <span>{order.quantity}</span>
                    <span>{order.duration}</span>
                    <span>{order.totalPrice}</span>
                </div>
                <div className="order-item-table-total" role="row">
                    <strong>{t("pages.orderdetailspage.total")}</strong>
                    <strong>{order.totalPrice}</strong>
                </div>
            </div>
        </article>
    );
}
function OrderRequirementsPanel({ requirements = [] }) {
    const { t } = useTranslation();

    if (requirements.length === 0) {
        return (
            <article className="order-detail-card order-requirements-card">
                <p className="messages-empty">
                    No saved order requirements are available yet.
                </p>
            </article>
        );
    }

    return (
        <article className="order-detail-card order-requirements-card">
            {requirements.map((requirement, index) => (
                <section
                    className="order-requirement-item"
                    key={requirement.question}
                >
                    <h2>
                        {index + 1}. {requirement.question}
                        {requirement.optional ? (
                            <span>{t("pages.orderdetailspage.optional")}</span>
                        ) : null}
                    </h2>
                    <p
                        className={
                            requirement.answer === "Not answered" ? "empty" : ""
                        }
                    >
                        {requirement.answer}
                    </p>
                </section>
            ))}
        </article>
    );
}
function OrderActivity({ order, isSeller }) {
    const { t } = useTranslation();

    if (!order.activity?.length) {
        return (
            <article className="order-detail-card order-activity-card">
                <p className="messages-empty">
                    Order activity will appear here as delivery events are
                    recorded.
                </p>
            </article>
        );
    }

    return (
        <article className="order-detail-card order-activity-card">
            <span className="order-date-pill">{order.dateOrdered}</span>
            <div className="order-timeline">
                {order.activity.map((item) => (
                    <TimelineItem
                        color={item.color || "green"}
                        icon={item.icon || "orders"}
                        title={item.title}
                        time={item.time}
                        key={`${item.title}-${item.time}`}
                    />
                ))}
            </div>
        </article>
    );
}
function TimelineItem({ children, color, icon, time, title }) {
    return (
        <section className="order-timeline-item">
            <span className={`order-timeline-icon ${color}`} aria-hidden="true">
                <Icon name={icon} />
            </span>
            <div className="order-timeline-content">
                <header>
                    <strong>{title}</strong>
                    <time>{time}</time>
                    {children ? <Icon name="chevronDown" /> : null}
                </header>
                {children ? (
                    <div className="order-timeline-panel">{children}</div>
                ) : null}
            </div>
        </section>
    );
}
function ActivityMessage({ avatarLabel, message, title }) {
    return (
        <article className="order-activity-message">
            {title ? <h3>{title}</h3> : null}
            <div>
                <span className="avatar">
                    {avatarLabel === "Me" ? "JA" : avatarLabel.slice(0, 2)}
                </span>
                <p>
                    <strong>{avatarLabel}</strong>
                    {message}
                </p>
            </div>
        </article>
    );
}
function ReviewCard({ avatar, heading, message, name }) {
    const { t } = useTranslation();
    return (
        <article className="order-review-card">
            <h3>{heading}</h3>
            <div className="order-review-body">
                <img src={avatar} alt="" />
                <div>
                    <strong>
                        {name} <Rating value="5" />
                    </strong>
                    <p>{message}</p>
                </div>
            </div>
            <dl className="order-review-ratings">
                {[
                    "Seller communication level",
                    "Quality of delivery",
                    "Value of delivery",
                ].map((label) => (
                    <div key={label}>
                        <dt>{label}</dt>
                        <dd>
                            <Rating value="5" />
                        </dd>
                    </div>
                ))}
            </dl>
            <div className="order-policy-note">
                <strong>{t("pages.orderdetailspage.ourPolicy")}</strong>
                <p>
                    {t(
                        "pages.orderdetailspage.ratingsAndReviewsReflectTheBuyersIndividualExperience",
                    )}
                </p>
            </div>
        </article>
    );
}
function OrderDetailsSidebar({
    conversationStatus = "",
    onOpenConversation,
    order,
}) {
    const { t } = useTranslation();
    return (
        <aside
            className="order-detail-sidebar"
            aria-label={t("pages.orderdetailspage.orderDetailsSidebar")}
        >
            <section className="order-side-card">
                <h2>{t("pages.orderdetailspage.orderDetails")}</h2>
                <div className="order-side-service">
                    {order.serviceImage ? (
                        <img src={order.serviceImage} alt="" />
                    ) : null}
                    <div>
                        <strong>{order.serviceSummary}</strong>
                        <span className={`status-badge ${order.statusClass}`}>
                            {order.status}
                        </span>
                    </div>
                </div>
                <div className="order-side-person">
                    <span className="avatar">{order.counterpartyInitials}</span>
                    <div>
                        <strong>{order.counterpartyName}</strong>
                        <span>{order.counterpartyHandle}</span>
                        <small>
                            {t("pages.orderdetailspage.lastSeen2MonthsAgo")}
                        </small>
                    </div>
                </div>
                <button
                    className="order-conversation-button"
                    type="button"
                    onClick={onOpenConversation}
                >
                    <Icon name="message" />{" "}
                    {t("pages.orderdetailspage.viewConversation")}{" "}
                </button>
                {conversationStatus ? (
                    <p className="profile-message-status">
                        {conversationStatus}
                    </p>
                ) : null}
                <dl className="order-side-meta">
                    <div>
                        <dt>{t("pages.orderdetailspage.orderedBy")}</dt>
                        <dd>{order.orderedBy}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.deliveryDate")}</dt>
                        <dd>{order.deliveryDate}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.totalPrice")}</dt>
                        <dd>{order.totalPrice}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.orderNumber")}</dt>
                        <dd>{order.orderNumber}</dd>
                    </div>
                </dl>
                <div className="order-track-list">
                    <h3>
                        {" "}
                        {t("pages.orderdetailspage.trackOrder")}{" "}
                        <Icon name="chevronDown" />
                    </h3>
                    <span>
                        <Icon name="packageCheck" />{" "}
                        {t("pages.orderdetailspage.deliveryReviewed")}
                    </span>
                    <span>
                        <Icon name="packageCheck" />{" "}
                        {t("pages.orderdetailspage.orderCompleted")}
                    </span>
                </div>
            </section>

            <section className="order-side-card order-note-card">
                <div>
                    <h2>{t("pages.orderdetailspage.privateNote")}</h2>
                    <span>{t("pages.orderdetailspage.onlyVisibleToYou")}</span>
                </div>
                <button type="button">
                    <Icon name="plus" />{" "}
                    {t("pages.orderdetailspage.addNote")}{" "}
                </button>
            </section>

            <section className="order-side-card">
                <h2>{t("pages.orderdetailspage.support")}</h2>
                <div className="order-support-list">
                    {orderSupportLinks.map((link) => (
                        <a href="#support" key={link.title}>
                            <Icon name={link.icon} />
                            <span>
                                <strong>{link.title}</strong>
                                <small>{link.description}</small>
                            </span>
                            <Icon name="arrowRight" />
                        </a>
                    ))}
                </div>
            </section>
        </aside>
    );
}
export default OrderDetailsPage;
