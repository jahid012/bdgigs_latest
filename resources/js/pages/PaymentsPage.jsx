import { useCallback, useEffect, useMemo, useState } from "react";
import { billingTabs } from "../data/dashboardPageData.js";
import {
    FilterButton,
    FinanceEmptyState,
    FinanceNotice,
    FinanceTabs,
} from "../components/dashboard/FinanceControls.jsx";
import { Icon } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../api/apiClient.js";

const emptyBillingSummary = {
    history: [],
    balances: {
        balance: "$0",
        credits: "$0",
        refunded: "$0",
    },
    paymentMethods: [],
    documents: [],
};

function BillingHistory({ history, onNavigate, onReport }) {
    const { t } = useTranslation();
    const [dateFilter, setDateFilter] = useState("Date range");
    const [documentFilter, setDocumentFilter] = useState("Document");
    const [currencyFilter, setCurrencyFilter] = useState("Currency");
    const [searchTerm, setSearchTerm] = useState("");
    const visibleHistory = useMemo(() => {
        const query = searchTerm.trim().toLowerCase();

        return history.filter((item) => {
            const matchesSearch =
                !query ||
                [item.document, item.service, item.order, item.id]
                    .filter(Boolean)
                    .join(" ")
                    .toLowerCase()
                    .includes(query);
            const matchesCurrency =
                currencyFilter === "Currency" ||
                item.currency === currencyFilter;
            const matchesDocument =
                documentFilter === "Document" ||
                item.document?.toLowerCase().includes("receipt");

            return matchesSearch && matchesCurrency && matchesDocument;
        });
    }, [currencyFilter, documentFilter, history, searchTerm]);

    return (
        <section className="billing-section">
            <h2>{t("pages.paymentspage.billingHistory")}</h2>
            <div className="billing-toolbar">
                <div className="finance-filter-row">
                    <FilterButton
                        label="Date range"
                        value={dateFilter}
                        onClick={() =>
                            setDateFilter((current) =>
                                current === "Date range"
                                    ? "This year"
                                    : "Date range",
                            )
                        }
                    />
                    <FilterButton
                        label="Document"
                        value={documentFilter}
                        onClick={() =>
                            setDocumentFilter((current) =>
                                current === "Document"
                                    ? "Invoices"
                                    : "Document",
                            )
                        }
                    />
                    <FilterButton
                        label="Currency"
                        value={currencyFilter}
                        onClick={() =>
                            setCurrencyFilter((current) =>
                                current === "Currency" ? "USD" : "Currency",
                            )
                        }
                    />
                </div>
                <form
                    className="billing-search"
                    role="search"
                    onSubmit={(event) => event.preventDefault()}
                >
                    <Icon name="search" />
                    <label className="sr-only" htmlFor="billingSearch">
                        {" "}
                        {t("pages.paymentspage.searchInvoices")}{" "}
                    </label>
                    <input
                        id="billingSearch"
                        type="search"
                        value={searchTerm}
                        placeholder={t(
                            "pages.paymentspage.searchByInvoiceOrOrderNumber",
                        )}
                        onChange={(event) => setSearchTerm(event.target.value)}
                    />
                </form>
            </div>
            <div className="billing-results-row">
                <p>
                    Showing {visibleHistory.length}{" "}
                    {visibleHistory.length === 1 ? "result" : "results"}
                </p>
                <button
                    className="finance-report-link"
                    type="button"
                    onClick={onReport}
                >
                    <Icon name="document" />{" "}
                    {t("pages.paymentspage.downloadReport")}{" "}
                </button>
            </div>
            <div
                className={`finance-table-wrap${visibleHistory.length ? "" : " billing-empty-table"}`}
            >
                <table className="finance-table">
                    <thead>
                        <tr>
                            <th>
                                <span
                                    className="fake-checkbox"
                                    aria-hidden="true"
                                ></span>
                            </th>
                            <th>{t("pages.paymentspage.date")}</th>
                            <th>{t("pages.paymentspage.document")}</th>
                            <th>{t("pages.paymentspage.service")}</th>
                            <th>{t("pages.paymentspage.order")}</th>
                            <th>{t("pages.paymentspage.currency")}</th>
                            <th>{t("pages.paymentspage.total")}</th>
                            <th>{t("pages.paymentspage.pdf")}</th>
                        </tr>
                    </thead>
                    {visibleHistory.length ? (
                        <tbody>
                            {visibleHistory.map((item) => (
                                <tr key={item.id}>
                                    <td>
                                        <span
                                            className="fake-checkbox"
                                            aria-hidden="true"
                                        ></span>
                                    </td>
                                    <td>{item.date}</td>
                                    <td>{item.document}</td>
                                    <td>{item.service}</td>
                                    <td>{item.order}</td>
                                    <td>{item.currency}</td>
                                    <td>{item.total}</td>
                                    <td>
                                        <span className="status-badge status-progress">
                                            Unavailable
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    ) : null}
                </table>
                {!visibleHistory.length ? (
                    <FinanceEmptyState
                        title={t("pages.paymentspage.noInvoicesYet")}
                        description="Order receipts and billing history appear after you place marketplace orders."
                        actionLabel="Explore"
                        onAction={() => onNavigate("home", "#services")}
                    />
                ) : null}
            </div>
        </section>
    );
}
function BillingInfo({ onSave, profile }) {
    const { t } = useTranslation();
    const [form, setForm] = useState(profile);

    useEffect(() => {
        setForm(profile);
    }, [profile]);

    const updateField = (field, value) => {
        setForm((current) => ({
            ...current,
            [field]: value,
        }));
    };
    return (
        <form
            className="billing-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSave(form);
            }}
        >
            <h2>{t("pages.paymentspage.billingInformation")}</h2>
            <label>
                {" "}
                {t("pages.paymentspage.fullName")}{" "}
                <input
                    value={form.fullName}
                    onChange={(event) =>
                        updateField("fullName", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.companyName")}{" "}
                <input
                    value={form.company}
                    onChange={(event) =>
                        updateField("company", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.country")}{" "}
                <select
                    value={form.country}
                    onChange={(event) =>
                        updateField("country", event.target.value)
                    }
                >
                    <option>{t("pages.paymentspage.bangladesh")}</option>
                    <option>{t("pages.paymentspage.unitedStates")}</option>
                    <option>{t("pages.paymentspage.pakistan")}</option>
                    <option>{t("pages.paymentspage.unitedKingdom")}</option>
                </select>
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.stateRegion")}{" "}
                <input
                    value={form.state}
                    onChange={(event) =>
                        updateField("state", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.address")}{" "}
                <input
                    value={form.address}
                    placeholder={t("pages.paymentspage.streetOrPob")}
                    onChange={(event) =>
                        updateField("address", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.city")}{" "}
                <input
                    value={form.city}
                    onChange={(event) =>
                        updateField("city", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.postalCode")}{" "}
                <input
                    value={form.postalCode}
                    onChange={(event) =>
                        updateField("postalCode", event.target.value)
                    }
                />
            </label>
            <label>
                {" "}
                {t("pages.paymentspage.taxId")}{" "}
                <input
                    value={form.taxId}
                    onChange={(event) =>
                        updateField("taxId", event.target.value)
                    }
                />
            </label>
            <button className="finance-primary-button" type="submit">
                {" "}
                {t("pages.paymentspage.saveBillingInfo")}{" "}
            </button>
        </form>
    );
}
function AddBalanceForm({ isSubmitting, onSubmit }) {
    const [draft, setDraft] = useState({
        amount: "",
        method: "card",
        note: "",
    });
    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };

    return (
        <form
            className="billing-add-balance-form"
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(draft).then((saved) => {
                    if (saved) {
                        setDraft({ amount: "", method: "card", note: "" });
                    }
                });
            }}
        >
            <div>
                <h3>Add balance</h3>
                <p>Add test wallet balance for marketplace purchases.</p>
            </div>
            <label>
                <span>Amount</span>
                <input
                    min="5"
                    max="5000"
                    step="0.01"
                    type="number"
                    value={draft.amount}
                    placeholder="50.00"
                    required
                    onChange={(event) =>
                        updateDraft("amount", event.target.value)
                    }
                />
            </label>
            <label>
                <span>Payment method</span>
                <select
                    value={draft.method}
                    onChange={(event) =>
                        updateDraft("method", event.target.value)
                    }
                >
                    <option value="card">Demo card</option>
                    <option value="mobile_wallet">Mobile wallet</option>
                    <option value="bank_transfer">Bank transfer</option>
                </select>
            </label>
            <label>
                <span>Note</span>
                <input
                    value={draft.note}
                    placeholder="Optional reference"
                    onChange={(event) => updateDraft("note", event.target.value)}
                />
            </label>
            <button
                className="finance-primary-button"
                type="submit"
                disabled={isSubmitting || !draft.amount}
            >
                {isSubmitting ? "Adding..." : "Add balance"}
            </button>
        </form>
    );
}

function Balances({ balances, isSubmitting, onAddBalance, onCredit }) {
    const { t } = useTranslation();
    return (
        <section className="billing-section">
            <h2>{t("pages.paymentspage.availableBalances")}</h2>
            <div className="billing-balance-grid">
                <article>
                    <h3>{t("pages.paymentspage.bdgigsBalance")}</h3>
                    <div className="finance-card-body split">
                        <div>
                            <span className="finance-label">
                                {t("pages.paymentspage.earnings")}
                            </span>
                            <strong className="finance-value">
                                {balances.balance}
                            </strong>
                            <p>
                                {t(
                                    "pages.paymentspage.availableForWithdrawalOrPurchases",
                                )}
                            </p>
                        </div>
                        <div>
                            <span className="finance-label">
                                {t("pages.paymentspage.fromCanceledOrders")}
                            </span>
                            <strong className="finance-value">
                                {balances.refunded}
                            </strong>
                        </div>
                    </div>
                </article>
                <article>
                    <h3>{t("pages.paymentspage.bdgigsCredits")}</h3>
                    <div className="finance-card-body split">
                        <div>
                            <span className="finance-label">
                                {t("pages.paymentspage.credits")}
                            </span>
                            <strong className="finance-value">
                                {balances.credits}
                            </strong>
                            <p>{t("pages.paymentspage.useForPurchases")}</p>
                        </div>
                        <div>
                            <h4>
                                {t("pages.paymentspage.likeToEarnSomeCredits")}
                            </h4>
                            <p>
                                {t(
                                    "pages.paymentspage.referPeopleYouKnowAndEveryoneBenefits",
                                )}
                            </p>
                            <button
                                className="finance-primary-button"
                                type="button"
                                onClick={onCredit}
                            >
                                {" "}
                                {t("pages.paymentspage.earnBdgigsCredits")}{" "}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
            <AddBalanceForm
                isSubmitting={isSubmitting}
                onSubmit={onAddBalance}
            />
        </section>
    );
}
function PaymentMethods({ methods, onAdd }) {
    const { t } = useTranslation();
    return (
        <section className="billing-section">
            <div className="finance-card-title-row">
                <h2>{t("pages.paymentspage.paymentMethods")}</h2>
                <button
                    className="finance-primary-button"
                    type="button"
                    onClick={onAdd}
                >
                    {" "}
                    {t("pages.paymentspage.addPaymentMethod")}{" "}
                </button>
            </div>
            {methods.length ? (
                <div className="payment-method-grid">
                    {methods.map((method) => (
                        <article key={method.id}>
                            <span className="stat-icon" aria-hidden="true">
                                <Icon name="payment" />
                            </span>
                            <div>
                                <strong>{method.label}</strong>
                                <p>{method.detail}</p>
                            </div>
                            <span className="status-badge status-completed">
                                {method.status}
                            </span>
                        </article>
                    ))}
                </div>
            ) : (
                <FinanceEmptyState
                    title="No payment methods on file"
                    description="Card vaulting and external checkout setup are not enabled in this billing pass."
                />
            )}
        </section>
    );
}
function PaymentsPage({ onNavigate }) {
    const { t } = useTranslation();
    const notify = useToast();
    const [activeTab, setActiveTab] = useState("history");
    const [notice, setNotice] = useState("");
    const [isAddingBalance, setIsAddingBalance] = useState(false);
    const [summary, setSummary] = useState(emptyBillingSummary);
    const [profile, setProfile] = useState({
        fullName: "",
        company: "",
        country: "",
        state: "",
        address: "",
        city: "",
        postalCode: "",
        taxId: "",
    });

    const loadSummary = useCallback(() => {
        return apiRequest("/api/billing/summary")
            .then((nextSummary) =>
                setSummary({ ...emptyBillingSummary, ...nextSummary }),
            )
            .catch((error) =>
                setNotice(error.message || "Unable to load billing history."),
            );
    }, []);

    useEffect(() => {
        loadSummary();
        apiRequest("/api/billing/profile")
            .then(setProfile)
            .catch((error) =>
                setNotice(error.message || "Unable to load billing profile."),
            );
    }, [loadSummary]);

    const saveBillingProfile = async (nextProfile) => {
        try {
            const savedProfile = await apiRequest("/api/billing/profile", {
                method: "PATCH",
                body: nextProfile,
            });
            setProfile(savedProfile);
            notify.success("Billing information saved for future order receipts.");
        } catch (error) {
            notify.error(
                error.message || "Billing information could not be saved.",
            );
        }
    };
    const addBalance = async (draft) => {
        setIsAddingBalance(true);
        setNotice("");

        try {
            const result = await apiRequest("/api/billing/add-balance", {
                body: draft,
            });
            setSummary({ ...emptyBillingSummary, ...(result.summary || {}) });
            notify.success(
                `${result.transaction?.amount || "Balance"} added to your wallet.`,
                { title: "Balance updated" },
            );
            setActiveTab("history");
            return true;
        } catch (error) {
            notify.error(error.message || "Balance could not be added.");
            return false;
        } finally {
            setIsAddingBalance(false);
        }
    };

    const renderTab = () => {
        if (activeTab === "info")
            return (
                <BillingInfo
                    profile={profile}
                    onSave={saveBillingProfile}
                />
            );
        if (activeTab === "balances")
            return (
                <Balances
                    balances={summary.balances}
                    isSubmitting={isAddingBalance}
                    onAddBalance={addBalance}
                    onCredit={() =>
                        notify.info(
                            "Marketplace credits are not available yet.",
                        )
                    }
                />
            );
        if (activeTab === "methods")
            return (
                <PaymentMethods
                    methods={summary.paymentMethods}
                    onAdd={() =>
                        notify.info(
                            "Payment method vaulting is not enabled yet.",
                        )
                    }
                />
            );
        return (
            <BillingHistory
                history={summary.history}
                onNavigate={onNavigate}
                onReport={() =>
                    notify.info(
                        summary.documents.length
                            ? "Billing documents are available in your history."
                            : "No downloadable billing documents are available yet.",
                    )
                }
            />
        );
    };
    return (
        <main className="dashboard-content finance-page">
            <div className="finance-page-header">
                <h1>{t("pages.paymentspage.billingAndPayments")}</h1>
            </div>
            <FinanceTabs
                tabs={billingTabs}
                activeTab={activeTab}
                onChange={setActiveTab}
            />
            <FinanceNotice message={notice} />
            {renderTab()}
        </main>
    );
}
export default PaymentsPage;
