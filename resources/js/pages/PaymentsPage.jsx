import { useState } from "react";
import { billingTabs } from "../data/dashboardPageData.js";
import {
    FilterButton,
    FinanceEmptyState,
    FinanceNotice,
    FinanceTabs,
} from "../components/dashboard/FinanceControls.jsx";
import { Icon } from "../components/common/Icons.jsx";
import { useTranslation } from "react-i18next";
function BillingHistory({ onNavigate, onReport }) {
    const { t } = useTranslation();
    const [dateFilter, setDateFilter] = useState("Date range");
    const [documentFilter, setDocumentFilter] = useState("Document");
    const [currencyFilter, setCurrencyFilter] = useState("Currency");
    const [searchTerm, setSearchTerm] = useState("");
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
                <p>{t("pages.paymentspage.showing0Results")}</p>
                <button
                    className="finance-report-link"
                    type="button"
                    onClick={onReport}
                >
                    <Icon name="document" />{" "}
                    {t("pages.paymentspage.downloadReport")}{" "}
                </button>
            </div>
            <div className="finance-table-wrap billing-empty-table">
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
                </table>
                <FinanceEmptyState
                    title={t("pages.paymentspage.noInvoicesYet")}
                    description="Ready to place an order? Make sure your billing info is up to date."
                    actionLabel="Explore"
                    onAction={() => onNavigate("home", "#services")}
                />
            </div>
        </section>
    );
}
function BillingInfo({ onSave }) {
    const { t } = useTranslation();
    const [form, setForm] = useState({
        fullName: "jahid_01",
        company: "",
        country: "Bangladesh",
        state: "",
        address: "",
        city: "",
        postalCode: "",
        taxId: "",
    });
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
                onSave();
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
function Balances({ onCredit }) {
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
                                {t("pages.paymentspage.1600")}
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
                                {t("pages.paymentspage.000")}
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
                                {t("pages.paymentspage.000")}
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
        </section>
    );
}
function PaymentMethods({ onAdd }) {
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
            <div className="payment-method-grid">
                <article>
                    <span className="stat-icon" aria-hidden="true">
                        <Icon name="payment" />
                    </span>
                    <div>
                        <strong>
                            {t("pages.paymentspage.visaEndingIn4242")}
                        </strong>
                        <p>
                            {t(
                                "pages.paymentspage.primaryMethodForProtectedOrders",
                            )}
                        </p>
                    </div>
                    <span className="status-badge status-completed">
                        {t("pages.paymentspage.primary")}
                    </span>
                </article>
                <article>
                    <span className="stat-icon" aria-hidden="true">
                        <Icon name="verifiedUser" />
                    </span>
                    <div>
                        <strong>
                            {t("pages.paymentspage.protectedCheckout")}
                        </strong>
                        <p>
                            {t(
                                "pages.paymentspage.fundsAreReleasedOnlyAfterOrderApproval",
                            )}
                        </p>
                    </div>
                    <span className="status-badge status-progress">
                        {t("pages.paymentspage.enabled")}
                    </span>
                </article>
            </div>
        </section>
    );
}
function PaymentsPage({ onNavigate }) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState("history");
    const [notice, setNotice] = useState("");
    const renderTab = () => {
        if (activeTab === "info")
            return (
                <BillingInfo
                    onSave={() =>
                        setNotice(
                            "Billing information saved for future invoices.",
                        )
                    }
                />
            );
        if (activeTab === "balances")
            return (
                <Balances
                    onCredit={() =>
                        setNotice(
                            "Credit referral options are ready to review.",
                        )
                    }
                />
            );
        if (activeTab === "methods")
            return (
                <PaymentMethods
                    onAdd={() => setNotice("Payment method setup opened.")}
                />
            );
        return (
            <BillingHistory
                onNavigate={onNavigate}
                onReport={() =>
                    setNotice("Billing report is ready to download.")
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
