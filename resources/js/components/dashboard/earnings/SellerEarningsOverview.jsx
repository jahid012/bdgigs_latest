import { useEffect, useState } from "react";
import { Icon } from "../../common/Icons.jsx";
import {
    FilterButton,
    FinanceEmptyState,
    FinanceNotice,
} from "../FinanceControls.jsx";
import SellerEarningsLineChart from "./SellerEarningsLineChart.jsx";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../../../api/apiClient.js";
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

const emptyFinance = {
    summary: {
        availableFunds: "$0",
        withdrawnToDate: "$0",
        clearing: "$0",
        activeOrderEarnings: "$0",
        earningsToDate: "$0",
        expensesToDate: "$0",
    },
    history: [],
    chartData: [],
};

function SellerEarningsOverview() {
    const { t } = useTranslation();
    const [activityFilter, setActivityFilter] = useState("all");
    const [dateFilter, setDateFilter] = useState("All dates");
    const [viewMode, setViewMode] = useState("table");
    const [notice, setNotice] = useState("");
    const [finance, setFinance] = useState(emptyFinance);
    const filteredHistory = getFilteredHistory(
        finance.history,
        activityFilter,
    );

    useEffect(() => {
        apiRequest("/api/seller/earnings")
            .then((nextFinance) =>
                setFinance({
                    ...emptyFinance,
                    ...nextFinance,
                    summary: {
                        ...emptyFinance.summary,
                        ...(nextFinance.summary || {}),
                    },
                }),
            )
            .catch((error) =>
                setNotice(error.message || "Unable to load seller earnings."),
            );
    }, []);

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
                            {finance.summary.availableFunds}
                        </strong>
                        <p>
                            {" "}
                            {t(
                                "components.dashboard.earnings.sellerearningsoverview.withdrawnToDate",
                            )}{" "}
                            <span>{finance.summary.withdrawnToDate}</span>
                        </p>
                        <button
                            className="finance-primary-button"
                            type="button"
                            onClick={() =>
                                setNotice(
                                    "Payout transfers are not enabled yet.",
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
                                    "Payout method setup is not enabled yet.",
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
                                {finance.summary.clearing}
                            </strong>
                        </div>
                        <div>
                            <span className="finance-label">
                                {t(
                                    "components.dashboard.earnings.sellerearningsoverview.paymentsForActiveOrders",
                                )}
                            </span>
                            <strong className="finance-value">
                                {finance.summary.activeOrderEarnings}
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
                                {finance.summary.earningsToDate}
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
                                {finance.summary.expensesToDate}
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
                                    "No emailed activity export is available yet.",
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
                    <EarningsChartPanel
                        data={finance.chartData}
                        summaryValue={finance.summary.earningsToDate}
                    />
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
                {history.length ? (
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
                ) : null}
            </table>
            {!history.length ? (
                <FinanceEmptyState
                    title="No earnings activity yet"
                    description="Seller order earnings appear here after buyers place orders."
                />
            ) : null}
        </div>
    );
}
function EarningsChartPanel({ data, summaryValue }) {
    return (
        <div className="finance-chart-panel">
            {data.length ? (
                <SellerEarningsLineChart
                    data={data}
                    summaryValue={summaryValue}
                    trendLabel="Order earnings by month"
                />
            ) : (
                <FinanceEmptyState
                    title="No chart data yet"
                    description="Monthly earnings trends appear after seller orders are recorded."
                />
            )}
        </div>
    );
}
function getFilteredHistory(history, activityFilter) {
    if (activityFilter === "all") {
        return history;
    }
    return history.filter(
        (item) => item.activity === activityFilter,
    );
}
export default SellerEarningsOverview;
