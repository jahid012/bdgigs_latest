import { useState } from "react";
import SellerEarningsOverview from "../components/dashboard/earnings/SellerEarningsOverview.jsx";
import SellerFinancialDocuments from "../components/dashboard/earnings/SellerFinancialDocuments.jsx";
import { FinanceNotice, FinanceTabs } from "../components/dashboard/FinanceControls.jsx";
import { earningTabs } from "../data/dashboardPageData.js";

function EarningsPage() {
  const [activeTab, setActiveTab] = useState("overview");
  const [notice, setNotice] = useState("");

  return (
    <main className="dashboard-content finance-page">
      <div className="finance-page-header">
        <h1>Earnings</h1>
        <button type="button" onClick={() => setNotice("Help article opened for the earnings page.")}>
          Learn more about this page
        </button>
      </div>
      <FinanceTabs tabs={earningTabs} activeTab={activeTab} onChange={setActiveTab} />
      <FinanceNotice message={notice} />
      {activeTab === "overview" ? (
        <SellerEarningsOverview />
      ) : (
        <SellerFinancialDocuments onReport={() => setNotice("Financial document report is ready to download.")} />
      )}
    </main>
  );
}

export default EarningsPage;
