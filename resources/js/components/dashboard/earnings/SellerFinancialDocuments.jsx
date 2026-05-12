import { Icon } from "../../common/Icons.jsx";
import { FilterButton, FinanceEmptyState } from "../FinanceControls.jsx";

function SellerFinancialDocuments({ onReport }) {
  return (
    <section className="finance-documents-panel">
      <div className="finance-toolbar">
        <div className="finance-filter-row">
          <FilterButton label="Year" value="2026" onClick={onReport} />
          <FilterButton label="Document" value="Tax statements" onClick={onReport} />
        </div>
        <button className="finance-report-link" type="button" onClick={onReport}>
          <Icon name="document" />
          Download documents report
        </button>
      </div>
      <div className="finance-table-wrap">
        <table className="finance-table">
          <thead>
            <tr>
              <th>Document</th>
              <th>Period</th>
              <th>Status</th>
              <th>Download</th>
            </tr>
          </thead>
        </table>
        <FinanceEmptyState title="No financial documents yet..." description="Your tax and yearly financial documents will appear here when available." />
      </div>
    </section>
  );
}

export default SellerFinancialDocuments;
