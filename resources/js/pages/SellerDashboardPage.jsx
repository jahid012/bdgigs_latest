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

function SellerStatsGrid() {
  return (
    <section className="stats-grid" aria-label="Seller dashboard stats">
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
  return (
    <article className="card dashboard-card orders-card seller-orders-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Order queue</span>
          <h2>Recent Seller Orders</h2>
        </div>
        <a
          href="/dashboard/seller/orders"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("seller-orders");
          }}
        >
          Manage orders
        </a>
      </div>
      <div className="orders-table-wrap">
        <table className="orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Service</th>
              <th>Buyer</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Earnings</th>
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
                  <span className={`status-badge ${order.statusClass}`}>{order.status}</span>
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
  const topValue = Math.max(...sellerChartData.map((bar) => bar.value));

  return (
    <article className="card dashboard-card chart-card seller-chart-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Revenue</span>
          <h2>Earnings Snapshot</h2>
        </div>
        <a
          href="/dashboard/seller/earnings"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("seller-earnings");
          }}
        >
          Payouts
        </a>
      </div>
      <div className="chart-summary">
        <div>
          <strong>$5,420</strong>
          <span>Earned this month</span>
        </div>
        <span className="status-badge status-completed">+18%</span>
      </div>
      <div className="chart-note">
        <span>Monthly earnings trend</span>
        <strong>Peak: {sellerChartData.find((bar) => bar.value === topValue)?.label}</strong>
      </div>
      <SellerEarningsLineChart
        ariaLabel="Monthly seller earnings trend"
        chartConfig={{ width: 640, height: 170, paddingX: 22, paddingY: 20 }}
        className="dashboard-line-chart"
        gradientId="snapshotEarningsLineGradient"
        showHeader={false}
      />
    </article>
  );
}

function SellerMessagesPreview({ onNavigate }) {
  return (
    <article className="card dashboard-card messages-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Buyer messages</span>
          <h2>Inbox Preview</h2>
        </div>
        <a
          href="/dashboard/seller/messages"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("seller-messages");
          }}
        >
          Open inbox
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
  return (
    <article className="card dashboard-card seller-pipeline-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Delivery focus</span>
          <h2>Next Milestones</h2>
        </div>
        <a
          href="/dashboard/seller/orders"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("seller-orders");
          }}
        >
          Calendar
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
            <div className="seller-progress-track" aria-label={`${item.title} progress`}>
              <span className="seller-progress-fill" style={{ "--progress": `${item.progress}%` }}></span>
            </div>
          </article>
        ))}
      </div>
    </article>
  );
}

function SellerServices({ onNavigate }) {
  return (
    <article className="card dashboard-card recommend-card seller-services-card">
      <div className="card-heading">
        <div>
          <span className="card-kicker">Gig performance</span>
          <h2>Active Services</h2>
        </div>
        <a
          href="/dashboard/seller/services"
          onClick={(event) => {
            event.preventDefault();
            onNavigate("seller-services");
          }}
        >
          Manage gigs
        </a>
      </div>
      <div className="recommend-grid">
        {sellerServices.map((service) => (
          <article className="mini-service seller-service" key={service.title}>
            <div className="mini-thumb">
              <img src={service.image} alt={`${service.title} preview`} loading="lazy" decoding="async" />
              <span>{service.tag}</span>
            </div>
            <div>
              <h3>{service.title}</h3>
              <p>
                {service.category} <Rating value={service.rating} />
              </p>
              <div className="seller-service-meta">
                <span>{service.orders}</span>
                <span>{service.conversion}</span>
              </div>
            </div>
            <div className="mini-service-footer">
              <span className="price">
                <span>Starts at</span> <strong>{service.price}</strong>
              </span>
              <span className="mini-delivery">{service.delivery}</span>
              <span className={`status-badge ${service.statusClass}`}>{service.status}</span>
            </div>
          </article>
        ))}
      </div>
    </article>
  );
}

function SellerDashboardPage({ onNavigate }) {
  return (
    <main className="dashboard-content marketplace-dashboard-content">
      <DashboardPageHeader
        className="dashboard-overview-hero seller-overview-hero"
        eyebrow="Seller workspace"
        title="Welcome back, Jahid"
        titleId="sellerDashboardTitle"
        description="Monitor active orders, protect delivery momentum, and optimize your best-selling services from one focused seller hub."
        stats={sellerDashboardHighlights}
        actions={
          <>
          <a
            className="btn btn-primary"
            href="/dashboard/seller/services"
            onClick={(event) => {
              event.preventDefault();
              onNavigate("seller-services");
            }}
          >
            Create New Gig
          </a>
          <a
            className="btn btn-light"
            href="/"
            onClick={(event) => {
              event.preventDefault();
              onNavigate("home");
            }}
          >
            View Marketplace
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
