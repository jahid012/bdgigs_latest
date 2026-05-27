import SellerEarningsOverview from "../components/dashboard/earnings/SellerEarningsOverview.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import { useTranslation } from "react-i18next";
function EarningsPage() {
    const { t } = useTranslation();
    const notify = useToast();
    return (
        <main className="dashboard-content finance-page">
            <div className="finance-page-header">
                <h1>{t("pages.earningspage.earnings")}</h1>
                <button
                    type="button"
                    onClick={() =>
                        notify.info("Help article opened for the earnings page.")
                    }
                >
                    {" "}
                    {t("pages.earningspage.learnMoreAboutThisPage")}{" "}
                </button>
            </div>
            <SellerEarningsOverview />
        </main>
    );
}
export default EarningsPage;
