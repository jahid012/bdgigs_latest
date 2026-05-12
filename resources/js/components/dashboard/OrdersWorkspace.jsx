import { buyerOrderInsights, orders, sellerOrderInsights, sellerOrders } from "../../data/dashboardData.js";
import { Link } from "react-router-dom";
import DashboardPageHeader from "./DashboardPageHeader.jsx";
import { Icon } from "../common/Icons.jsx";

const getProgress = (status) => {
  const progressMap = {
    "In Progress": 62,
    Delivered: 88,
    Completed: 100,
    Cancelled: 18,
  };

  return progressMap[status] || 40;
};

const getOrderValue = (value) => Number(value.replace(/[^0-9.]/g, "")) || 0;
const getOrderDetailPath = (order, isSeller) => {
  const orderSlug = order.id.replace(/^#/, "");
  return `${isSeller ? "/dashboard/seller/orders" : "/dashboard/orders"}/${orderSlug}`;
};

function OrdersWorkspace({ variant = "buyer" }) {
  const isSeller = variant === "seller";
  const rawOrders = isSeller ? sellerOrders : orders;
  const insights = isSeller ? sellerOrderInsights : buyerOrderInsights;
  const activeOrders = rawOrders.filter((order) => order.status === "In Progress").length;
  const deliveredOrders = rawOrders.filter((order) => order.status === "Delivered").length;
  const completedOrders = rawOrders.filter((order) => order.status === "Completed").length;
  const amountKey = isSeller ? "earnings" : "price";
  const partyKey = isSeller ? "buyer" : "seller";
  const totalValue = rawOrders.reduce((total, order) => total + getOrderValue(order[amountKey]), 0);
  const focusOrder = rawOrders[0];

  return (
    <main className="dashboard-content orders-page">
      <DashboardPageHeader
        eyebrow={isSeller ? "Seller orders" : "Buyer orders"}
        title="Orders"
        titleId="ordersTitle"
        description={
          isSeller
            ? "Manage active work, delivery dates, revisions, and buyer approvals from a focused seller order center."
            : "Track purchases, delivery status, seller progress, and approval steps across every active project."
        }
        stats={insights}
      />

      <section className="orders-kpi-grid" aria-label="Order metrics">
        <article className="card order-kpi-card">
          <span className="stat-icon" aria-hidden="true">
            <Icon name="orders" />
          </span>
          <div>
            <span>Active Orders</span>
            <strong>{activeOrders}</strong>
          </div>
        </article>
        <article className="card order-kpi-card">
          <span className="stat-icon" aria-hidden="true">
            <Icon name="packageCheck" />
          </span>
          <div>
            <span>Delivered</span>
            <strong>{deliveredOrders}</strong>
          </div>
        </article>
        <article className="card order-kpi-card">
          <span className="stat-icon" aria-hidden="true">
            <Icon name="verifiedUser" />
          </span>
          <div>
            <span>Completed</span>
            <strong>{completedOrders}</strong>
          </div>
        </article>
        <article className="card order-kpi-card">
          <span className="stat-icon" aria-hidden="true">
            <Icon name="payment" />
          </span>
          <div>
            <span>{isSeller ? "Projected Earnings" : "Tracked Value"}</span>
            <strong>${totalValue.toLocaleString()}</strong>
          </div>
        </article>
      </section>

      <section className="orders-workspace">
        <article className="card dashboard-card orders-page-card">
          <div className="messages-panel-heading">
            <div>
              <span className="card-kicker">{isSeller ? "Delivery queue" : "Purchase activity"}</span>
              <h2>All Orders</h2>
            </div>
            <a href="#">Export</a>
          </div>

          <div className="orders-toolbar">
            <form className="messages-search" role="search" aria-label="Search orders" onSubmit={(event) => event.preventDefault()}>
              <Icon name="search" />
              <label className="sr-only" htmlFor="ordersSearch">
                Search orders
              </label>
              <input id="ordersSearch" type="search" placeholder="Search by service, person, or order ID..." autoComplete="off" />
            </form>
            <div className="messages-filters" aria-label="Order filters">
              <button className="active" type="button">
                All
              </button>
              <button type="button">Active</button>
              <button type="button">Delivered</button>
              <button type="button">Completed</button>
            </div>
          </div>

          <div className="orders-table-wrap">
            <table className="orders-table order-page-table">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Service</th>
                  <th>{isSeller ? "Buyer" : "Seller"}</th>
                  <th>Status</th>
                  <th>Progress</th>
                  <th>Due Date</th>
                  <th>{isSeller ? "Earnings" : "Price"}</th>
                </tr>
              </thead>
              <tbody>
                {rawOrders.map((order) => (
                  <tr key={order.id}>
                    <td data-label="Order ID">
                      <Link className="order-table-link" to={getOrderDetailPath(order, isSeller)}>
                        {order.id}
                      </Link>
                    </td>
                    <td data-label="Service">
                      <Link className="order-service-link" to={getOrderDetailPath(order, isSeller)}>
                        {order.service}
                      </Link>
                    </td>
                    <td data-label={isSeller ? "Buyer" : "Seller"}>{order[partyKey]}</td>
                    <td data-label="Status">
                      <span className={`status-badge ${order.statusClass}`}>{order.status}</span>
                    </td>
                    <td data-label="Progress">
                      <span className="order-progress" aria-label={`${order.status} progress`}>
                        <span style={{ "--progress": `${getProgress(order.status)}%` }}></span>
                      </span>
                    </td>
                    <td data-label="Due Date">{order.dueDate}</td>
                    <td data-label={isSeller ? "Earnings" : "Price"}>{order[amountKey]}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </article>

        <aside className="card dashboard-card order-focus-card" aria-label="Focused order details">
          <div className="card-heading">
            <div>
              <span className="card-kicker">Priority order</span>
              <h2>{focusOrder.id}</h2>
            </div>
            <span className={`status-badge ${focusOrder.statusClass}`}>{focusOrder.status}</span>
          </div>
          <div className="order-focus-body">
            <h3>{focusOrder.service}</h3>
            <p>
              {isSeller ? "Buyer" : "Seller"}: <strong>{focusOrder[partyKey]}</strong>
            </p>
            <div className="order-focus-meta">
              <span>
                <small>Due date</small>
                <strong>{focusOrder.dueDate}</strong>
              </span>
              <span>
                <small>{isSeller ? "Earnings" : "Price"}</small>
                <strong>{focusOrder[amountKey]}</strong>
              </span>
            </div>
            <div>
              <div className="order-focus-progress">
                <span>Progress</span>
                <strong>{getProgress(focusOrder.status)}%</strong>
              </div>
              <span className="order-progress large">
                <span style={{ "--progress": `${getProgress(focusOrder.status)}%` }}></span>
              </span>
            </div>
          </div>
          <Link className="order-open-link" to={getOrderDetailPath(focusOrder, isSeller)}>
            Open order details
          </Link>
          <div className="order-next-steps">
            <h3>Next Steps</h3>
            <ul>
              <li>{isSeller ? "Confirm buyer notes and upload the next milestone." : "Review the latest seller update and leave concise feedback."}</li>
              <li>{isSeller ? "Keep delivery files organized before final submission." : "Approve delivery only after checking source files."}</li>
              <li>{isSeller ? "Send a clear closing message when the work is delivered." : "Use the order thread for all scope changes."}</li>
            </ul>
          </div>
        </aside>
      </section>
    </main>
  );
}

export default OrdersWorkspace;
