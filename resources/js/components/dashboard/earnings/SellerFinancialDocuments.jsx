import { Icon } from "../../common/Icons.jsx";
import { FilterButton, FinanceEmptyState } from "../FinanceControls.jsx";
import { useTranslation } from "react-i18next";
function SellerFinancialDocuments({ onReport }) {
    const { t } = useTranslation();
    return (
        <section className="finance-documents-panel">
            <div className="finance-toolbar">
                <div className="finance-filter-row">
                    <FilterButton
                        label="Year"
                        value="2026"
                        onClick={onReport}
                    />
                    <FilterButton
                        label="Document"
                        value="Tax statements"
                        onClick={onReport}
                    />
                </div>
                <button
                    className="finance-report-link"
                    type="button"
                    onClick={onReport}
                >
                    <Icon name="document" />{" "}
                    {t(
                        "components.dashboard.earnings.sellerfinancialdocuments.downloadDocumentsReport",
                    )}{" "}
                </button>
            </div>
            <div className="finance-table-wrap">
                <table className="finance-table">
                    <thead>
                        <tr>
                            <th>
                                {t(
                                    "components.dashboard.earnings.sellerfinancialdocuments.document",
                                )}
                            </th>
                            <th>
                                {t(
                                    "components.dashboard.earnings.sellerfinancialdocuments.period",
                                )}
                            </th>
                            <th>
                                {t(
                                    "components.dashboard.earnings.sellerfinancialdocuments.status",
                                )}
                            </th>
                            <th>
                                {t(
                                    "components.dashboard.earnings.sellerfinancialdocuments.download",
                                )}
                            </th>
                        </tr>
                    </thead>
                </table>
                <FinanceEmptyState
                    title={t(
                        "components.dashboard.earnings.sellerfinancialdocuments.noFinancialDocumentsYet",
                    )}
                    description="Your tax and yearly financial documents will appear here when available."
                />
            </div>
        </section>
    );
}
export default SellerFinancialDocuments;
