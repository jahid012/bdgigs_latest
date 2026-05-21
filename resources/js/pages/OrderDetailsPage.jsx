import { useState } from "react";
import { useParams } from "react-router-dom";
import {
    orderDetailRecords,
    orderRequirements,
    orderSupportLinks,
} from "../data/orderDetailsData.js";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useConversationLauncher } from "../hooks/useConversationLauncher.js";
import { useTranslation } from "react-i18next";
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
    const order = orderDetailRecords[variant] || orderDetailRecords.buyer;
    const launchConversation = useConversationLauncher();
    const [activeTab, setActiveTab] = useState("details");
    const [conversationStatus, setConversationStatus] = useState("");
    const openOrderConversation = async () => {
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
                        <OrderRequirementsPanel />
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

            <ol className="order-bullet-list">
                {order.orderBullets.map((item) => (
                    <li key={item}>{item}</li>
                ))}
            </ol>

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
function OrderRequirementsPanel() {
    const { t } = useTranslation();
    return (
        <article className="order-detail-card order-requirements-card">
            {orderRequirements.map((requirement, index) => (
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
    return (
        <article className="order-detail-card order-activity-card">
            <span className="order-date-pill">{order.timelineDate}</span>
            <div className="order-timeline">
                <TimelineItem
                    color="violet"
                    icon="document"
                    title={`${order.counterpartyName} placed the order`}
                    time={order.dateOrdered}
                />
                <TimelineItem
                    color="blue"
                    icon="edit"
                    title={`${order.counterpartyName} sent the requirements`}
                    time={order.dateOrdered}
                >
                    <OrderRequirementsPanel />
                </TimelineItem>
                <TimelineItem
                    color="green"
                    icon="orders"
                    title={t("pages.orderdetailspage.theOrderStarted")}
                    time={order.dateOrdered}
                />
                <TimelineItem
                    color="green"
                    icon="payment"
                    title={`Your delivery date was updated to ${order.deliveryDate}`}
                    time={order.dateOrdered}
                />
                <TimelineItem
                    color="pink"
                    icon="packageCheck"
                    title={t("pages.orderdetailspage.youDeliveredTheOrder")}
                    time="Nov 21, 2025, 8:55 PM"
                >
                    <ActivityMessage
                        title={t("pages.orderdetailspage.delivery1")}
                        avatarLabel="Me"
                        message={order.deliveryMessage}
                    />
                </TimelineItem>
                <TimelineItem
                    color="violet"
                    icon="document"
                    title={t("pages.orderdetailspage.theOrderWasCompleted")}
                    time="Nov 21, 2025, 9:07 PM"
                />
                <TimelineItem
                    color="slate"
                    icon="star"
                    title={`${order.counterpartyName} gave you a 5-star review`}
                    time="Nov 21, 2025, 9:08 PM"
                >
                    <ReviewCard
                        heading={`${order.counterpartyName}'s review`}
                        name={`${order.counterpartyName}'s message`}
                        avatar={order.counterpartyAvatar}
                        message={order.buyerReview}
                    />
                    {isSeller ? (
                        <ActivityMessage
                            avatarLabel="Me"
                            message={order.sellerReply}
                        />
                    ) : null}
                </TimelineItem>
                <TimelineItem
                    color="slate"
                    icon="star"
                    title={`You left ${order.counterpartyName} a 5-star review`}
                    time="Nov 21, 2025, 9:40 PM"
                >
                    <ReviewCard
                        heading="Your review"
                        name="Me"
                        avatar="/assets/img/gig_images/4.png"
                        message="It was a nice experience. Very responsive. Looking forward to the next order."
                    />
                </TimelineItem>
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
                    <img src={order.serviceImage} alt="" />
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
