import { dashboardDetailCopy } from "../data/dashboardPageData.js";
import DetailPageShell from "../components/dashboard/DetailPageShell.jsx";
import MinimalServiceList from "../components/dashboard/MinimalServiceList.jsx";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useEffect } from "react";

function SellerServicesPage({ onNavigate }) {
    const content = dashboardDetailCopy.seller.services;
    const sellerServices = useDashboardStore((state) => state.sellerServices);
    const fetchSellerServices = useDashboardStore(
        (state) => state.fetchSellerServices,
    );
    const isSellerServicesLoading = useDashboardStore(
        (state) => state.isSellerServicesLoading,
    );
    const changeSellerServiceStatus = useDashboardStore(
        (state) => state.changeSellerServiceStatus,
    );
    const deleteSellerService = useDashboardStore(
        (state) => state.deleteSellerService,
    );

    useEffect(() => {
        fetchSellerServices();
    }, [fetchSellerServices]);

    return (
        <DetailPageShell
            content={content}
            onNavigate={onNavigate}
            variant="seller"
        >
            <MinimalServiceList
                content={content}
                loading={isSellerServicesLoading}
                onNavigate={onNavigate}
                onServiceDelete={(service) => deleteSellerService(service.id)}
                onServiceStatusChange={(service, action) =>
                    changeSellerServiceStatus(service.id, action)
                }
                seller
                services={sellerServices}
            />
        </DetailPageShell>
    );
}

export default SellerServicesPage;
