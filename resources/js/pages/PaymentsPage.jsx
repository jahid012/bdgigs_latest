import { useState } from "react";
import { billingTabs } from "../data/dashboardPageData.js";
import { FilterButton, FinanceEmptyState, FinanceNotice, FinanceTabs } from "../components/dashboard/FinanceControls.jsx";
import { Icon } from "../components/common/Icons.jsx";

function BillingHistory({ onNavigate, onReport }) {
  const [dateFilter, setDateFilter] = useState("Date range");
  const [documentFilter, setDocumentFilter] = useState("Document");
  const [currencyFilter, setCurrencyFilter] = useState("Currency");
  const [searchTerm, setSearchTerm] = useState("");

  return (
    <section className="billing-section">
      <h2>Billing history</h2>
      <div className="billing-toolbar">
        <div className="finance-filter-row">
          <FilterButton
            label="Date range"
            value={dateFilter}
            onClick={() => setDateFilter((current) => (current === "Date range" ? "This year" : "Date range"))}
          />
          <FilterButton
            label="Document"
            value={documentFilter}
            onClick={() => setDocumentFilter((current) => (current === "Document" ? "Invoices" : "Document"))}
          />
          <FilterButton
            label="Currency"
            value={currencyFilter}
            onClick={() => setCurrencyFilter((current) => (current === "Currency" ? "USD" : "Currency"))}
          />
        </div>
        <form className="billing-search" role="search" onSubmit={(event) => event.preventDefault()}>
          <Icon name="search" />
          <label className="sr-only" htmlFor="billingSearch">
            Search invoices
          </label>
          <input
            id="billingSearch"
            type="search"
            value={searchTerm}
            placeholder="Search by invoice or order number"
            onChange={(event) => setSearchTerm(event.target.value)}
          />
        </form>
      </div>
      <div className="billing-results-row">
        <p>Showing 0 results.</p>
        <button className="finance-report-link" type="button" onClick={onReport}>
          <Icon name="document" />
          Download report
        </button>
      </div>
      <div className="finance-table-wrap billing-empty-table">
        <table className="finance-table">
          <thead>
            <tr>
              <th>
                <span className="fake-checkbox" aria-hidden="true"></span>
              </th>
              <th>Date</th>
              <th>Document</th>
              <th>Service</th>
              <th>Order</th>
              <th>Currency</th>
              <th>Total</th>
              <th>PDF</th>
            </tr>
          </thead>
        </table>
        <FinanceEmptyState
          title="No invoices yet..."
          description="Ready to place an order? Make sure your billing info is up to date."
          actionLabel="Explore"
          onAction={() => onNavigate("home", "#services")}
        />
      </div>
    </section>
  );
}

function BillingInfo({ onSave }) {
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
    setForm((current) => ({ ...current, [field]: value }));
  };

  return (
    <form
      className="billing-form"
      onSubmit={(event) => {
        event.preventDefault();
        onSave();
      }}
    >
      <h2>Billing information</h2>
      <label>
        Full name
        <input value={form.fullName} onChange={(event) => updateField("fullName", event.target.value)} />
      </label>
      <label>
        Company name
        <input value={form.company} onChange={(event) => updateField("company", event.target.value)} />
      </label>
      <label>
        Country
        <select value={form.country} onChange={(event) => updateField("country", event.target.value)}>
          <option>Bangladesh</option>
          <option>United States</option>
          <option>Pakistan</option>
          <option>United Kingdom</option>
        </select>
      </label>
      <label>
        State/Region
        <input value={form.state} onChange={(event) => updateField("state", event.target.value)} />
      </label>
      <label>
        Address
        <input value={form.address} placeholder="Street or POB" onChange={(event) => updateField("address", event.target.value)} />
      </label>
      <label>
        City
        <input value={form.city} onChange={(event) => updateField("city", event.target.value)} />
      </label>
      <label>
        Postal code
        <input value={form.postalCode} onChange={(event) => updateField("postalCode", event.target.value)} />
      </label>
      <label>
        Tax ID
        <input value={form.taxId} onChange={(event) => updateField("taxId", event.target.value)} />
      </label>
      <button className="finance-primary-button" type="submit">
        Save billing info
      </button>
    </form>
  );
}

function Balances({ onCredit }) {
  return (
    <section className="billing-section">
      <h2>Available balances</h2>
      <div className="billing-balance-grid">
        <article>
          <h3>BDGigs Balance</h3>
          <div className="finance-card-body split">
            <div>
              <span className="finance-label">Earnings</span>
              <strong className="finance-value">$16.00</strong>
              <p>Available for withdrawal or purchases.</p>
            </div>
            <div>
              <span className="finance-label">From canceled orders</span>
              <strong className="finance-value">$0.00</strong>
            </div>
          </div>
        </article>
        <article>
          <h3>BDGigs Credits</h3>
          <div className="finance-card-body split">
            <div>
              <span className="finance-label">Credits</span>
              <strong className="finance-value">$0.00</strong>
              <p>Use for purchases.</p>
            </div>
            <div>
              <h4>Like to earn some credits?</h4>
              <p>Refer people you know and everyone benefits.</p>
              <button className="finance-primary-button" type="button" onClick={onCredit}>
                Earn BDGigs Credits
              </button>
            </div>
          </div>
        </article>
      </div>
    </section>
  );
}

function PaymentMethods({ onAdd }) {
  return (
    <section className="billing-section">
      <div className="finance-card-title-row">
        <h2>Payment methods</h2>
        <button className="finance-primary-button" type="button" onClick={onAdd}>
          Add payment method
        </button>
      </div>
      <div className="payment-method-grid">
        <article>
          <span className="stat-icon" aria-hidden="true">
            <Icon name="payment" />
          </span>
          <div>
            <strong>Visa ending in 4242</strong>
            <p>Primary method for protected orders.</p>
          </div>
          <span className="status-badge status-completed">Primary</span>
        </article>
        <article>
          <span className="stat-icon" aria-hidden="true">
            <Icon name="verifiedUser" />
          </span>
          <div>
            <strong>Protected checkout</strong>
            <p>Funds are released only after order approval.</p>
          </div>
          <span className="status-badge status-progress">Enabled</span>
        </article>
      </div>
    </section>
  );
}

function PaymentsPage({ onNavigate }) {
  const [activeTab, setActiveTab] = useState("history");
  const [notice, setNotice] = useState("");

  const renderTab = () => {
    if (activeTab === "info") return <BillingInfo onSave={() => setNotice("Billing information saved for future invoices.")} />;
    if (activeTab === "balances") return <Balances onCredit={() => setNotice("Credit referral options are ready to review.")} />;
    if (activeTab === "methods") return <PaymentMethods onAdd={() => setNotice("Payment method setup opened.")} />;
    return <BillingHistory onNavigate={onNavigate} onReport={() => setNotice("Billing report is ready to download.")} />;
  };

  return (
    <main className="dashboard-content finance-page">
      <div className="finance-page-header">
        <h1>Billing and payments</h1>
      </div>
      <FinanceTabs tabs={billingTabs} activeTab={activeTab} onChange={setActiveTab} />
      <FinanceNotice message={notice} />
      {renderTab()}
    </main>
  );
}

export default PaymentsPage;
