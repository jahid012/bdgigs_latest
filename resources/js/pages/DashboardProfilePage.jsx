import BuyerClientProfilePage from "../components/dashboard/profile/BuyerClientProfilePage.jsx";
import SellerProfileManagerPage from "../components/dashboard/profile/SellerProfileManagerPage.jsx";

function DashboardProfilePage({ initialMode = "profile", variant = "seller" }) {
    if (variant === "buyer") {
        return <BuyerClientProfilePage />;
    }

    return <SellerProfileManagerPage initialMode={initialMode} />;
}

export default DashboardProfilePage;
