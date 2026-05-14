import { chartData, dashboardHighlights, messages, orders, recommendedServices, stats } from "../data/dashboardData.js";
import DashboardPageHeader from "../components/dashboard/DashboardPageHeader.jsx";
import { Icon, Rating } from "../components/common/Icons.jsx";

function StatsGrid() {
  return (
    <section className="stats-grid" aria-label="Dashboard stats">
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
  return (
    <article className="card dashboard-card orders-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Order activity</span>
          <h2>Recent Orders</h2>
        </div>
        <a
          href="/dashboard/orders"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("orders");
          }}
        >
          View all
        </a>
      </div>
      <div className="orders-table-wrap">
        <table className="orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Service</th>
              <th>Seller</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Price</th>
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
                  <span className={`status-badge ${order.statusClass}`}>{order.status}</span>
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
  const topValue = Math.max(...chartData.map((bar) => bar.value));

  return (
    <article className="card dashboard-card chart-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Cash flow</span>
          <h2>Earnings / Spending</h2>
        </div>
        <a
          href="/dashboard/payments"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("payments");
          }}
        >
          Report
        </a>
      </div>
      <div className="chart-summary">
        <div>
          <strong>$2,860</strong>
          <span>Spent this period</span>
        </div>
        <span className="status-badge status-completed">+14%</span>
      </div>
    </article>
  );
}

function MessagesPreview({ onNavigate }) {
  return (
    <article className="card dashboard-card messages-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Seller replies</span>
          <h2>Messages Preview</h2>
        </div>
        <a
          href="/dashboard/messages"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("messages");
          }}
        >
          Inbox
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
  return (
    <article className="card dashboard-card recommend-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Matched for you</span>
          <h2>Recommended Services</h2>
        </div>
        <a
          href="/#services"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("home", "#services");
          }}
        >
          Browse more
        </a>
      </div>
      <div className="recommend-grid">
        {recommendedServices.map((service) => (
          <article className="mini-service" key={service.title}>
            <div className="mini-thumb">
              <img src={service.image} alt={`${service.title} service preview`} loading="lazy" decoding="async" />
              <span>{service.tag}</span>
            </div>
            <div>
              <h3>{service.title}</h3>
              <p>
                {service.seller} <Rating value={service.rating} />
              </p>
            </div>
            <div className="mini-service-footer">
              <span className="price">
                <span>From</span> <strong>{service.price}</strong>
              </span>
              <span className="mini-delivery">{service.delivery}</span>
              <a className="tag" href="#">
                Save
              </a>
            </div>
          </article>
        ))}
      </div>
    </article>
  );
}

function BuyerDashboardPage({ onNavigate }) {
  return (
    <main className="dashboard-content marketplace-dashboard-content">
      <DashboardPageHeader
        className="dashboard-overview-hero buyer-overview-hero"
        eyebrow="Buyer workspace"
        title="Welcome back, Jahid"
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
            Explore Services
          </a>
          <a
            className="btn btn-secondary"
            href="/dashboard/orders"
            onClick={(event) => {
              event.preventDefault();
              onNavigate("orders");
            }}
          >
            View Orders
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
