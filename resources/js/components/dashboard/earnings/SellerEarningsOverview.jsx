import { useState } from "react";
import { sellerPayoutHistory } from "../../../data/dashboardPageData.js";
import { Icon } from "../../common/Icons.jsx";
import { FilterButton, FinanceNotice } from "../FinanceControls.jsx";
import SellerEarningsLineChart from "./SellerEarningsLineChart.jsx";
import { useTranslation } from "react-i18next";
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
    const { t } = useTranslation();
    const [activityFilter, setActivityFilter] = useState("all");
    const [dateFilter, setDateFilter] = useState("All dates");
    const [viewMode, setViewMode] = useState("table");
    const [notice, setNotice] = useState("");
    const filteredHistory = getFilteredHistory(activityFilter);
    return (
        <>
            <FinanceNotice message={notice} />

            <section
                className="earnings-summary-grid"
                aria-label={t(
                    "components.dashboard.earnings.sellerearningsoverview.earningsSummary",
                )}
            >
                <article className="finance-summary-card">
                    <h2>
                        {t(
                            "components.dashboard.earnings.sellerearningsoverview.availableFunds",
                        )}
                    </h2>
                    <div className="finance-card-body">
                        <span className="finance-label">
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.balanceAvailableForUse",
                            )}
                        </span>
                        <strong className="finance-value">
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.1600",
                            )}
                        </strong>
                        <p>
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.withdrawnToDate",
                            )}{" "}
                            <span>
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.102480",
                                )}
                            </span>
                        </p>
                        <button
                            className="finance-primary-button"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Withdrawal flow opened. Your balance is ready to transfer.",
                                )
                            }
                        >
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.withdrawBalance",
                            )}{" "}
                        </button>
                        <button
                            className="finance-link-button"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Payout method settings are ready to review.",
                                )
                            }
                        >
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.managePayoutMethods",
                            )}{" "}
                        </button>
                    </div>
                </article>

                <article className="finance-summary-card">
                    <h2>
                        {" "}
                        {t(
                            "components.dashboard.earnings.sellerearningsoverview.futurePayments",
                        )}{" "}
                        <span
                            aria-label={t(
                                "components.dashboard.earnings.sellerearningsoverview.moreInformation",
                            )}
                        >
                            ?
                        </span>
                    </h2>
                    <div className="finance-card-body split">
                        <div>
                            <span className="finance-label">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.paymentsBeingCleared",
                                )}
                            </span>
                            <strong className="finance-value">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.000",
                                )}
                            </strong>
                        </div>
                        <div>
                            <span className="finance-label">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.paymentsForActiveOrders",
                                )}
                            </span>
                            <strong className="finance-value">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.000",
                                )}
                            </strong>
                        </div>
                    </div>
                </article>

                <article className="finance-summary-card">
                    <div className="finance-card-title-row">
                        <h2>
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.earningsAndExpenses",
                            )}{" "}
                            <span
                                aria-label={t(
                                    "components.dashboard.earnings.sellerearningsoverview.moreInformation",
                                )}
                            >
                                ?
                            </span>
                        </h2>
                        <button
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Showing earnings and expenses since joining.",
                                )
                            }
                        >
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.sinceJoining",
                            )}{" "}
                            <Icon name="chevronDown" />
                        </button>
                    </div>
                    <div className="finance-card-body split">
                        <div>
                            <span className="finance-label">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.earningsToDate",
                                )}
                            </span>
                            <strong className="finance-value">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.104080",
                                )}
                            </strong>
                            <p>
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.yourEarningsSinceJoining",
                                )}
                            </p>
                        </div>
                        <div>
                            <span className="finance-label">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.expensesToDate",
                                )}
                            </span>
                            <strong className="finance-value">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.000",
                                )}
                            </strong>
                            <p>
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.earningsSpentOnPurchasesSinceJoining",
                                )}
                            </p>
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
                            onClick={() =>
                                setDateFilter((current) =>
                                    current === "All dates"
                                        ? "This year"
                                        : "All dates",
                                )
                            }
                        />
                        <FilterButton
                            label="Activity"
                            value={activityFilterLabels[activityFilter]}
                            onClick={() =>
                                setActivityFilter(
                                    (current) => nextActivityFilter[current],
                                )
                            }
                        />
                    </div>
                    <div className="finance-actions-row">
                        <div
                            className="finance-view-toggle"
                            aria-label={t(
                                "components.dashboard.earnings.sellerearningsoverview.viewMode",
                            )}
                        >
                            <button
                                className={viewMode === "table" ? "active" : ""}
                                type="button"
                                aria-pressed={viewMode === "table"}
                                onClick={() => setViewMode("table")}
                            >
                                <Icon name="dashboard" />
                            </button>
                            <button
                                className={viewMode === "chart" ? "active" : ""}
                                type="button"
                                aria-pressed={viewMode === "chart"}
                                onClick={() => setViewMode("chart")}
                            >
                                <Icon name="chart" />
                            </button>
                        </div>
                        <button
                            className="finance-report-link"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Activity report queued for email delivery.",
                                )
                            }
                        >
                            <Icon name="document" />{" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.emailActivityReport",
                            )}{" "}
                        </button>
                    </div>
                </div>

                <p className="finance-results-label">
                    {" "}
                    {t(
                        "components.dashboard.earnings.sellerearningsoverview.showingResults1",
                    )}
                    {filteredHistory.length}{" "}
                    {t(
                        "components.dashboard.earnings.sellerearningsoverview.of",
                    )}{" "}
                    {filteredHistory.length}
                </p>

                {viewMode === "table" ? (
                    <EarningsHistoryTable history={filteredHistory} />
                ) : (
                    <EarningsChartPanel />
                )}
            </section>
        </>
    );
}
function EarningsHistoryTable({ history }) {
    const { t } = useTranslation();
    return (
        <div className="finance-table-wrap">
            <table className="finance-table">
                <thead>
                    <tr>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.date",
                            )}
                        </th>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.activity",
                            )}
                        </th>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.description",
                            )}
                        </th>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.from",
                            )}
                        </th>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.order",
                            )}
                        </th>
                        <th>
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.amount",
                            )}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {history.map((item) => (
                        <tr key={`${item.id}-${item.date}`}>
                            <td>{item.date}</td>
                            <td>
                                <span className="finance-activity">
                                    <Icon
                                        name={
                                            item.activity === "earning"
                                                ? "payment"
                                                : "arrowRight"
                                        }
                                    />
                                    {item.status}
                                </span>
                            </td>
                            <td>{item.description}</td>
                            <td>{item.from}</td>
                            <td>
                                <a href="#order">{item.id}</a>
                            </td>
                            <td
                                className={
                                    item.amount.startsWith("-")
                                        ? "negative"
                                        : "positive"
                                }
                            >
                                {item.amount}
                            </td>
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
    return sellerPayoutHistory.filter(
        (item) => item.activity === activityFilter,
    );
}
export default SellerEarningsOverview;
