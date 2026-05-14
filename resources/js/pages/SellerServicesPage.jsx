import { sellerServices } from "../data/dashboardData.js";
import { dashboardDetailCopy } from "../data/dashboardPageData.js";
import DetailPageShell from "../components/dashboard/DetailPageShell.jsx";
import MinimalServiceList from "../components/dashboard/MinimalServiceList.jsx";

function SellerServicesPage({ onNavigate }) {
    const content = dashboardDetailCopy.seller.services;

    return (
        <DetailPageShell
            content={content}
            onNavigate={onNavigate}
            variant="seller"
        >
            <MinimalServiceList
                content={content}
                onNavigate={onNavigate}
                seller
                services={sellerServices}
            />
        </DetailPageShell>
    );
}

export default SellerServicesPage;
