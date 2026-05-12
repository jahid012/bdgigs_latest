import { useState } from "react";
import { sellerPayoutHistory } from "../../../data/dashboardPageData.js";
import { Icon } from "../../common/Icons.jsx";
import { FilterButton, FinanceNotice } from "../FinanceControls.jsx";
import SellerEarningsLineChart from "./SellerEarningsLineChart.jsx";

const activityFilterLabels = {
  all: "Activity",
  earning: "Earning",
  withdrawal: "Withdrawal",
};

const nextActivityFilter = {
  all: "earning",
  earning: "withdrawal",
  withdrawal: "all",
};

function SellerEarningsOverview() {
  const [activityFilter, setActivityFilter] = useState("all");
  const [dateFilter, setDateFilter] = useState("All dates");
  const [viewMode, setViewMode] = useState("table");
  const [notice, setNotice] = useState("");
  const filteredHistory = getFilteredHistory(activityFilter);

  return (
    <>
      <FinanceNotice message={notice} />

      <section className="earnings-summary-grid" aria-label="Earnings summary">
        <article className="finance-summary-card">
          <h2>Available funds</h2>
          <div className="finance-card-body">
            <span className="finance-label">Balance available for use</span>
            <strong className="finance-value">$16.00</strong>
            <p>
              Withdrawn to date:
              <span>$1,024.80</span>
            </p>
            <button className="finance-primary-button" type="button" onClick={() => setNotice("Withdrawal flow opened. Your balance is ready to transfer.")}>
              Withdraw balance
            </button>
            <button className="finance-link-button" type="button" onClick={() => setNotice("Payout method settings are ready to review.")}>
              Manage payout methods
            </button>
          </div>
        </article>

        <article className="finance-summary-card">
          <h2>
            Future payments <span aria-label="More information">?</span>
          </h2>
          <div className="finance-card-body split">
            <div>
              <span className="finance-label">Payments being cleared</span>
              <strong className="finance-value">$0.00</strong>
            </div>
            <div>
              <span className="finance-label">Payments for active orders</span>
              <strong className="finance-value">$0.00</strong>
            </div>
          </div>
        </article>

        <article className="finance-summary-card">
          <div className="finance-card-title-row">
            <h2>
              Earnings & expenses <span aria-label="More information">?</span>
            </h2>
            <button type="button" onClick={() => setNotice("Showing earnings and expenses since joining.")}>
              Since joining <Icon name="chevronDown" />
            </button>
          </div>
          <div className="finance-card-body split">
            <div>
              <span className="finance-label">Earnings to date</span>
              <strong className="finance-value">$1,040.80</strong>
              <p>Your earnings since joining.</p>
            </div>
            <div>
              <span className="finance-label">Expenses to date</span>
              <strong className="finance-value">$0.00</strong>
              <p>Earnings spent on purchases since joining.</p>
            </div>
          </div>
        </article>
      </section>

      <section className="finance-table-section">
        <div className="finance-toolbar">
          <div className="finance-filter-row">
            <FilterButton
              label="Date range"
              value={dateFilter}
              onClick={() => setDateFilter((current) => (current === "All dates" ? "This year" : "All dates"))}
            />
            <FilterButton
              label="Activity"
              value={activityFilterLabels[activityFilter]}
              onClick={() => setActivityFilter((current) => nextActivityFilter[current])}
            />
          </div>
          <div className="finance-actions-row">
            <div className="finance-view-toggle" aria-label="View mode">
              <button className={viewMode === "table" ? "active" : ""} type="button" aria-pressed={viewMode === "table"} onClick={() => setViewMode("table")}>
                <Icon name="dashboard" />
              </button>
              <button className={viewMode === "chart" ? "active" : ""} type="button" aria-pressed={viewMode === "chart"} onClick={() => setViewMode("chart")}>
                <Icon name="chart" />
              </button>
            </div>
            <button className="finance-report-link" type="button" onClick={() => setNotice("Activity report queued for email delivery.")}>
              <Icon name="document" />
              Email activity report
            </button>
          </div>
        </div>

        <p className="finance-results-label">
          Showing results 1-{filteredHistory.length} of {filteredHistory.length}
        </p>

        {viewMode === "table" ? <EarningsHistoryTable history={filteredHistory} /> : <EarningsChartPanel />}
      </section>
    </>
  );
}

function EarningsHistoryTable({ history }) {
  return (
    <div className="finance-table-wrap">
      <table className="finance-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Activity</th>
            <th>Description</th>
            <th>From</th>
            <th>Order</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          {history.map((item) => (
            <tr key={`${item.id}-${item.date}`}>
              <td>{item.date}</td>
              <td>
                <span className="finance-activity">
                  <Icon name={item.activity === "earning" ? "payment" : "arrowRight"} />
                  {item.status}
                </span>
              </td>
              <td>{item.description}</td>
              <td>{item.from}</td>
              <td>
                <a href="#order">{item.id}</a>
              </td>
              <td className={item.amount.startsWith("-") ? "negative" : "positive"}>{item.amount}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function EarningsChartPanel() {
  return (
    <div className="finance-chart-panel">
      <SellerEarningsLineChart />
    </div>
  );
}

function getFilteredHistory(activityFilter) {
  if (activityFilter === "all") {
    return sellerPayoutHistory;
  }

  return sellerPayoutHistory.filter((item) => item.activity === activityFilter);
}

export default SellerEarningsOverview;
