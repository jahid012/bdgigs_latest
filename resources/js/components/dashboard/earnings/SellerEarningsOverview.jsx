import { useCallback, useEffect, useState } from "react";
import { Icon } from "../../common/Icons.jsx";
import {
    FilterButton,
    FinanceEmptyState,
} from "../FinanceControls.jsx";
import SellerEarningsLineChart from "./SellerEarningsLineChart.jsx";
import { useToast } from "../../common/ToastProvider.jsx";
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
        minimumWithdrawal: "$10",
        availableValue: 0,
    },
    history: [],
    chartData: [],
    payoutMethods: [],
    withdrawals: [],
};

function SellerEarningsOverview() {
    const { t } = useTranslation();
    const notify = useToast();
    const [activityFilter, setActivityFilter] = useState("all");
    const [dateFilter, setDateFilter] = useState("All dates");
    const [viewMode, setViewMode] = useState("table");
    const [finance, setFinance] = useState(emptyFinance);
    const [isWithdrawalOpen, setIsWithdrawalOpen] = useState(false);
    const [isPayoutMethodOpen, setIsPayoutMethodOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [withdrawalDraft, setWithdrawalDraft] = useState({
        payoutMethodId: "",
        amount: "",
        note: "",
    });
    const [payoutDraft, setPayoutDraft] = useState({
        type: "bank",
        label: "",
        accountHolder: "",
        accountNumber: "",
        routingDetails: "",
    });
    const filteredHistory = getFilteredHistory(finance.history, activityFilter);
    const loadFinance = useCallback(() => {
        return apiRequest("/api/seller/earnings")
            .then((nextFinance) => {
                const normalized = {
                    ...emptyFinance,
                    ...nextFinance,
                    summary: {
                        ...emptyFinance.summary,
                        ...(nextFinance.summary || {}),
                    },
                    payoutMethods: nextFinance.payoutMethods || [],
                    withdrawals: nextFinance.withdrawals || [],
                };

                setFinance(normalized);
                setWithdrawalDraft((current) => ({
                    ...current,
                    payoutMethodId:
                        current.payoutMethodId ||
                        String(normalized.payoutMethods[0]?.id || ""),
                }));
                return normalized;
            })
            .catch((error) =>
                notify.error(error.message || "Unable to load seller earnings."),
            );
    }, [notify]);

    useEffect(() => {
        loadFinance();
    }, [loadFinance]);
    const submitPayoutMethod = async (event) => {
        event.preventDefault();
        setIsSubmitting(true);

        try {
            await apiRequest("/api/seller/payout-methods", {
                body: payoutDraft,
            });
            setPayoutDraft({
                type: "bank",
                label: "",
                accountHolder: "",
                accountNumber: "",
                routingDetails: "",
            });
            notify.success("Payout method saved for manual withdrawals.");
            await loadFinance();
        } catch (error) {
            notify.error(error.message || "Unable to save payout method.");
        } finally {
            setIsSubmitting(false);
        }
    };
    const submitWithdrawal = async (event) => {
        event.preventDefault();
        setIsSubmitting(true);

        try {
            await apiRequest("/api/seller/withdrawals", {
                body: {
                    ...withdrawalDraft,
                    payoutMethodId: Number(withdrawalDraft.payoutMethodId),
                },
            });
            setWithdrawalDraft((current) => ({
                ...current,
                amount: "",
                note: "",
            }));
            notify.success("Withdrawal request sent for manual finance review.");
            await loadFinance();
        } catch (error) {
            notify.error(error.message || "Unable to request withdrawal.");
        } finally {
            setIsSubmitting(false);
        }
    };
    const cancelWithdrawal = async (withdrawalId) => {
        setIsSubmitting(true);

        try {
            await apiRequest(`/api/seller/withdrawals/${withdrawalId}/cancel`, {
                body: {},
            });
            notify.success("Withdrawal request cancelled.");
            await loadFinance();
        } catch (error) {
            notify.error(error.message || "Unable to cancel withdrawal.");
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <>
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
                                setIsWithdrawalOpen((current) => !current)
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
                                setIsPayoutMethodOpen((current) => !current)
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
                                notify.info(
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

            <WithdrawalWorkspace
                finance={finance}
                isPayoutMethodOpen={isPayoutMethodOpen}
                isSubmitting={isSubmitting}
                isWithdrawalOpen={isWithdrawalOpen}
                onCancel={cancelWithdrawal}
                onPayoutChange={setPayoutDraft}
                onPayoutSubmit={submitPayoutMethod}
                onWithdrawalChange={setWithdrawalDraft}
                onWithdrawalSubmit={submitWithdrawal}
                payoutDraft={payoutDraft}
                withdrawalDraft={withdrawalDraft}
            />

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
                                notify.info(
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

function WithdrawalWorkspace({
    finance,
    isPayoutMethodOpen,
    isSubmitting,
    isWithdrawalOpen,
    onCancel,
    onPayoutChange,
    onPayoutSubmit,
    onWithdrawalChange,
    onWithdrawalSubmit,
    payoutDraft,
    withdrawalDraft,
}) {
    if (
        !isPayoutMethodOpen &&
        !isWithdrawalOpen &&
        finance.payoutMethods.length === 0 &&
        finance.withdrawals.length === 0
    ) {
        return null;
    }

    return (
        <section
            className="seller-withdrawal-workspace"
            aria-label="Manual withdrawals"
        >
            <div className="seller-withdrawal-grid">
                {isWithdrawalOpen ? (
                    <form
                        className="seller-withdrawal-form"
                        onSubmit={onWithdrawalSubmit}
                    >
                        <div>
                            <h2>Request withdrawal</h2>
                            <p>
                                Finance reviews manual withdrawals before
                                recording a payout reference.
                            </p>
                        </div>
                        <label>
                            <span>Payout method</span>
                            <select
                                value={withdrawalDraft.payoutMethodId}
                                required
                                onChange={(event) =>
                                    onWithdrawalChange((current) => ({
                                        ...current,
                                        payoutMethodId: event.target.value,
                                    }))
                                }
                            >
                                <option value="">Choose payout method</option>
                                {finance.payoutMethods
                                    .filter((method) => method.active)
                                    .map((method) => (
                                        <option
                                            value={method.id}
                                            key={method.id}
                                        >
                                            {method.label} -{" "}
                                            {method.accountNumber}
                                        </option>
                                    ))}
                            </select>
                        </label>
                        <label>
                            <span>Amount</span>
                            <input
                                min="10"
                                max={
                                    finance.summary.availableValue || undefined
                                }
                                step="0.01"
                                type="number"
                                value={withdrawalDraft.amount}
                                placeholder={`Available ${finance.summary.availableFunds}`}
                                required
                                onChange={(event) =>
                                    onWithdrawalChange((current) => ({
                                        ...current,
                                        amount: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <label>
                            <span>Seller note</span>
                            <textarea
                                value={withdrawalDraft.note}
                                placeholder="Optional payout note for finance"
                                onChange={(event) =>
                                    onWithdrawalChange((current) => ({
                                        ...current,
                                        note: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <small>
                            Minimum {finance.summary.minimumWithdrawal}. Pending
                            approved requests reserve available funds.
                        </small>
                        <button
                            className="finance-primary-button"
                            disabled={
                                isSubmitting ||
                                finance.payoutMethods.length === 0 ||
                                !withdrawalDraft.payoutMethodId
                            }
                            type="submit"
                        >
                            Send withdrawal request
                        </button>
                    </form>
                ) : null}

                {isPayoutMethodOpen ? (
                    <form
                        className="seller-withdrawal-form"
                        onSubmit={onPayoutSubmit}
                    >
                        <div>
                            <h2>Add payout method</h2>
                            <p>
                                These details are snapshotted when you request a
                                manual withdrawal.
                            </p>
                        </div>
                        <label>
                            <span>Method type</span>
                            <select
                                value={payoutDraft.type}
                                onChange={(event) =>
                                    onPayoutChange((current) => ({
                                        ...current,
                                        type: event.target.value,
                                    }))
                                }
                            >
                                <option value="bank">Bank</option>
                                <option value="mobile_wallet">
                                    Mobile wallet
                                </option>
                                <option value="manual">Manual transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </label>
                        <label>
                            <span>Label</span>
                            <input
                                value={payoutDraft.label}
                                placeholder="Primary bank account"
                                required
                                onChange={(event) =>
                                    onPayoutChange((current) => ({
                                        ...current,
                                        label: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <label>
                            <span>Account holder</span>
                            <input
                                value={payoutDraft.accountHolder}
                                required
                                onChange={(event) =>
                                    onPayoutChange((current) => ({
                                        ...current,
                                        accountHolder: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <label>
                            <span>Account number or wallet</span>
                            <input
                                value={payoutDraft.accountNumber}
                                required
                                onChange={(event) =>
                                    onPayoutChange((current) => ({
                                        ...current,
                                        accountNumber: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <label>
                            <span>Routing details</span>
                            <input
                                value={payoutDraft.routingDetails}
                                placeholder="Bank, branch, routing code, or provider"
                                onChange={(event) =>
                                    onPayoutChange((current) => ({
                                        ...current,
                                        routingDetails: event.target.value,
                                    }))
                                }
                            />
                        </label>
                        <button
                            className="finance-primary-button"
                            disabled={isSubmitting}
                            type="submit"
                        >
                            Save payout method
                        </button>
                    </form>
                ) : null}

                <div className="seller-withdrawal-list">
                    <div>
                        <h2>Payout methods and requests</h2>
                        <p>
                            Withdrawals are paid manually after finance review.
                        </p>
                    </div>
                    <div className="seller-payout-method-list">
                        {finance.payoutMethods.map((method) => (
                            <article key={method.id}>
                                <strong>{method.label}</strong>
                                <span>
                                    {method.accountHolder} -{" "}
                                    {method.accountNumber}
                                </span>
                            </article>
                        ))}
                        {finance.payoutMethods.length === 0 ? (
                            <p>No payout method saved yet.</p>
                        ) : null}
                    </div>
                    <div className="seller-withdrawal-request-list">
                        {finance.withdrawals.map((withdrawal) => (
                            <article key={withdrawal.id}>
                                <div>
                                    <strong>{withdrawal.code}</strong>
                                    <span>
                                        {withdrawal.requestedDate} -{" "}
                                        {withdrawal.payout?.label ||
                                            "Manual payout"}
                                    </span>
                                </div>
                                <b>{withdrawal.amount}</b>
                                <em data-status={withdrawal.statusKey}>
                                    {withdrawal.status}
                                </em>
                                {withdrawal.canCancel ? (
                                    <button
                                        disabled={isSubmitting}
                                        type="button"
                                        onClick={() => onCancel(withdrawal.id)}
                                    >
                                        Cancel
                                    </button>
                                ) : null}
                            </article>
                        ))}
                        {finance.withdrawals.length === 0 ? (
                            <p>No withdrawal request is recorded yet.</p>
                        ) : null}
                    </div>
                </div>
            </div>
        </section>
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
    return history.filter((item) => item.activity === activityFilter);
}
export default SellerEarningsOverview;
