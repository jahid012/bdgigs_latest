import { useTranslation } from "react-i18next";
function SellerCTA() {
    const { t } = useTranslation();
    return (
        <section className="seller-cta" id="seller">
            <div className="container">
                <div className="seller-cta-panel">
                    <div>
                        <span className="eyebrow">
                            {t("components.home.sellercta.forFreelancers")}
                        </span>
                        <h2>
                            {t(
                                "components.home.sellercta.turnYourSkillsIntoAPremiumServiceBusiness",
                            )}
                        </h2>
                        <p>
                            {" "}
                            {t(
                                "components.home.sellercta.createPackagesAttractSeriousBuyersAndManageOrders",
                            )}{" "}
                        </p>
                        <div
                            className="seller-cta-list"
                            aria-label={t(
                                "components.home.sellercta.sellerBenefits",
                            )}
                        >
                            <span>
                                {t("components.home.sellercta.fastPayouts")}
                            </span>
                            <span>
                                {t("components.home.sellercta.protectedOrders")}
                            </span>
                            <span>
                                {t(
                                    "components.home.sellercta.portfolioFirstProfiles",
                                )}
                            </span>
                        </div>
                    </div>
                    <a className="btn btn-primary" href="#seller">
                        {" "}
                        {t("components.home.sellercta.becomeASeller")}{" "}
                    </a>
                </div>
            </div>
        </section>
    );
}
export default SellerCTA;
