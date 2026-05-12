import { useState } from "react";
import { useParams } from "react-router-dom";
import { orderDetailRecords, orderRequirements, orderSupportLinks } from "../data/orderDetailsData.js";
import { Icon, Rating } from "../components/common/Icons.jsx";

const tabs = [
  { id: "activity", label: "Activity" },
  { id: "details", label: "Details" },
  { id: "requirements", label: "Requirements" },
];

function OrderDetailsPage({ variant = "buyer" }) {
  const { orderId } = useParams();
  const isSeller = variant === "seller";
  const order = orderDetailRecords[variant] || orderDetailRecords.buyer;
  const [activeTab, setActiveTab] = useState("details");

  return (
    <main className="dashboard-content order-details-page">
      <div className="order-details-shell">
        <section className="order-details-main" aria-label={`Order ${orderId || order.orderNumber}`}>
          <OrderTabs activeTab={activeTab} onChange={setActiveTab} />
          {activeTab === "activity" ? <OrderActivity order={order} isSeller={isSeller} /> : null}
          {activeTab === "details" ? <OrderDetailsPanel order={order} /> : null}
          {activeTab === "requirements" ? <OrderRequirementsPanel /> : null}
        </section>

        <OrderDetailsSidebar order={order} />
      </div>
    </main>
  );
}

function OrderTabs({ activeTab, onChange }) {
  return (
    <nav className="order-detail-tabs" aria-label="Order detail sections">
      {tabs.map((tab) => (
        <button className={activeTab === tab.id ? "active" : ""} type="button" aria-pressed={activeTab === tab.id} key={tab.id} onClick={() => onChange(tab.id)}>
          {tab.label}
        </button>
      ))}
    </nav>
  );
}

function OrderDetailsPanel({ order }) {
  return (
    <article className="order-detail-card order-invoice-card">
      <div className="order-detail-card-head">
        <div>
          <h1>{order.serviceTitle}</h1>
          <p>
            Ordered by <strong>{order.orderedBy}</strong> <a href="#history">(view history)</a>
            <span>Date ordered <strong>{order.dateOrdered}</strong></span>
          </p>
        </div>
        <div className="order-total-block">
          <span>Total price</span>
          <strong>{order.totalPrice}</strong>
        </div>
      </div>

      <div className="order-number-row">
        <span>Order number</span>
        <strong>{order.orderNumber}</strong>
      </div>

      <ol className="order-bullet-list">
        {order.orderBullets.map((item) => (
          <li key={item}>{item}</li>
        ))}
      </ol>

      <div className="order-item-table" role="table" aria-label="Order item details">
        <div className="order-item-table-head" role="row">
          <strong>Item</strong>
          <strong>Qty.</strong>
          <strong>Duration</strong>
          <strong>Price</strong>
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
          <strong>Total</strong>
          <strong>{order.totalPrice}</strong>
        </div>
      </div>
    </article>
  );
}

function OrderRequirementsPanel() {
  return (
    <article className="order-detail-card order-requirements-card">
      {orderRequirements.map((requirement, index) => (
        <section className="order-requirement-item" key={requirement.question}>
          <h2>
            {index + 1}. {requirement.question}
            {requirement.optional ? <span>(Optional)</span> : null}
          </h2>
          <p className={requirement.answer === "Not answered" ? "empty" : ""}>{requirement.answer}</p>
        </section>
      ))}
    </article>
  );
}

function OrderActivity({ order, isSeller }) {
  return (
    <article className="order-detail-card order-activity-card">
      <span className="order-date-pill">{order.timelineDate}</span>
      <div className="order-timeline">
        <TimelineItem color="violet" icon="document" title={`${order.counterpartyName} placed the order`} time={order.dateOrdered} />
        <TimelineItem color="blue" icon="edit" title={`${order.counterpartyName} sent the requirements`} time={order.dateOrdered}>
          <OrderRequirementsPanel />
        </TimelineItem>
        <TimelineItem color="green" icon="orders" title="The order started" time={order.dateOrdered} />
        <TimelineItem color="green" icon="payment" title={`Your delivery date was updated to ${order.deliveryDate}`} time={order.dateOrdered} />
        <TimelineItem color="pink" icon="packageCheck" title="You delivered the order" time="Nov 21, 2025, 8:55 PM">
          <ActivityMessage title="Delivery #1" avatarLabel="Me" message={order.deliveryMessage} />
        </TimelineItem>
        <TimelineItem color="violet" icon="document" title="The order was completed" time="Nov 21, 2025, 9:07 PM" />
        <TimelineItem color="slate" icon="star" title={`${order.counterpartyName} gave you a 5-star review`} time="Nov 21, 2025, 9:08 PM">
          <ReviewCard
            heading={`${order.counterpartyName}'s review`}
            name={`${order.counterpartyName}'s message`}
            avatar={order.counterpartyAvatar}
            message={order.buyerReview}
          />
          {isSeller ? <ActivityMessage avatarLabel="Me" message={order.sellerReply} /> : null}
        </TimelineItem>
        <TimelineItem color="slate" icon="star" title={`You left ${order.counterpartyName} a 5-star review`} time="Nov 21, 2025, 9:40 PM">
          <ReviewCard heading="Your review" name="Me" avatar="/assets/img/gig_images/4.png" message="It was a nice experience. Very responsive. Looking forward to the next order." />
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
        {children ? <div className="order-timeline-panel">{children}</div> : null}
      </div>
    </section>
  );
}

function ActivityMessage({ avatarLabel, message, title }) {
  return (
    <article className="order-activity-message">
      {title ? <h3>{title}</h3> : null}
      <div>
        <span className="avatar">{avatarLabel === "Me" ? "JA" : avatarLabel.slice(0, 2)}</span>
        <p>
          <strong>{avatarLabel}</strong>
          {message}
        </p>
      </div>
    </article>
  );
}

function ReviewCard({ avatar, heading, message, name }) {
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
        {["Seller communication level", "Quality of delivery", "Value of delivery"].map((label) => (
          <div key={label}>
            <dt>{label}</dt>
            <dd>
              <Rating value="5" />
            </dd>
          </div>
        ))}
      </dl>
      <div className="order-policy-note">
        <strong>Our policy</strong>
        <p>Ratings and reviews reflect the buyer's individual experience.</p>
      </div>
    </article>
  );
}

function OrderDetailsSidebar({ order }) {
  return (
    <aside className="order-detail-sidebar" aria-label="Order details sidebar">
      <section className="order-side-card">
        <h2>Order details</h2>
        <div className="order-side-service">
          <img src={order.serviceImage} alt="" />
          <div>
            <strong>{order.serviceSummary}</strong>
            <span className={`status-badge ${order.statusClass}`}>{order.status}</span>
          </div>
        </div>
        <div className="order-side-person">
          <span className="avatar">{order.counterpartyInitials}</span>
          <div>
            <strong>{order.counterpartyName}</strong>
            <span>{order.counterpartyHandle}</span>
            <small>Last seen 2 months ago</small>
          </div>
        </div>
        <button className="order-conversation-button" type="button">
          <Icon name="message" />
          View conversation
        </button>
        <dl className="order-side-meta">
          <div>
            <dt>Ordered by</dt>
            <dd>{order.orderedBy}</dd>
          </div>
          <div>
            <dt>Delivery date</dt>
            <dd>{order.deliveryDate}</dd>
          </div>
          <div>
            <dt>Total price</dt>
            <dd>{order.totalPrice}</dd>
          </div>
          <div>
            <dt>Order number</dt>
            <dd>{order.orderNumber}</dd>
          </div>
        </dl>
        <div className="order-track-list">
          <h3>
            Track Order
            <Icon name="chevronDown" />
          </h3>
          <span><Icon name="packageCheck" /> Delivery reviewed</span>
          <span><Icon name="packageCheck" /> Order completed</span>
        </div>
      </section>

      <section className="order-side-card order-note-card">
        <div>
          <h2>Private note</h2>
          <span>Only visible to you</span>
        </div>
        <button type="button">
          <Icon name="plus" />
          Add Note
        </button>
      </section>

      <section className="order-side-card">
        <h2>Support</h2>
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
