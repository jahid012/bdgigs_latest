import { useEffect, useState } from "react";
import { Link, useParams, useSearchParams } from "react-router-dom";
import { apiRequest } from "../api/apiClient.js";
import { Icon } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";
import { useSessionStore } from "../stores/useSessionStore.js";

function VerifyEmailPage({ onNavigate }) {
    const { status } = useParams();
    const [searchParams] = useSearchParams();
    const notify = useToast();
    const currentUser = useSessionStore((state) => state.currentUser);
    const hydrateSession = useSessionStore((state) => state.hydrateSession);
    const [isSending, setIsSending] = useState(false);

    useEffect(() => {
        hydrateSession();
    }, [hydrateSession]);

    const resend = async () => {
        setIsSending(true);

        try {
            const response = await apiRequest(
                "/api/email/verification-notification",
                { body: {} },
            );
            notify.success(response.message || "Verification email sent.");
        } catch (error) {
            notify.error(error.message || "Verification email could not be sent.");
        } finally {
            setIsSending(false);
        }
    };

    const email = searchParams.get("email") || currentUser?.email || "";
    const isSuccess = status === "success";
    const isInvalid = status === "invalid";

    return (
        <div className="home-page auth-status-page">
            <Header onNavigate={onNavigate} />
            <main className="auth-status-shell">
                <section className="auth-status-panel">
                    <span className={`auth-status-icon${isInvalid ? " is-error" : ""}`}>
                        <Icon name={isInvalid ? "close" : "verifiedUser"} />
                    </span>
                    <h1>
                        {isSuccess
                            ? "Email verified"
                            : isInvalid
                                ? "Verification link expired"
                                : "Verify your email"}
                    </h1>
                    <p>
                        {isSuccess
                            ? `${email || "Your email"} is verified. You can continue using protected marketplace actions.`
                            : isInvalid
                                ? "This verification link is invalid or expired. Request a fresh verification email from your account."
                                : "Check your inbox for the verification link we sent after registration."}
                    </p>
                    <div className="auth-status-actions">
                        {!isSuccess && currentUser?.authenticated ? (
                            <button
                                className="btn btn-primary"
                                type="button"
                                disabled={isSending}
                                onClick={resend}
                            >
                                {isSending ? "Sending..." : "Resend verification"}
                            </button>
                        ) : null}
                        <Link className="btn btn-secondary" to="/dashboard">
                            Open dashboard
                        </Link>
                    </div>
                </section>
            </main>
            <Footer />
        </div>
    );
}

export default VerifyEmailPage;
