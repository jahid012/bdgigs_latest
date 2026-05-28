import { useState } from "react";
import { Link } from "react-router-dom";
import { apiRequest } from "../api/apiClient.js";
import { Icon } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";

function ForgotPasswordPage({ onNavigate }) {
    const notify = useToast();
    const [email, setEmail] = useState("");
    const [error, setError] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [sent, setSent] = useState(false);

    const submit = async (event) => {
        event.preventDefault();
        setError("");
        setIsSubmitting(true);

        try {
            await apiRequest("/forgot-password", {
                body: { email },
            });
            setSent(true);
            notify.success("Password reset email sent.");
        } catch (nextError) {
            const message =
                nextError.payload?.errors?.email?.[0] ||
                nextError.message ||
                "Password reset email could not be sent.";
            setError(message);
            notify.error(message);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="home-page auth-status-page">
            <Header onNavigate={onNavigate} />
            <main className="auth-status-shell">
                <section className="auth-status-panel">
                    <span className="auth-status-icon">
                        <Icon name="settings" />
                    </span>
                    <h1>Reset your password</h1>
                    <p>
                        Enter your account email and we will send a secure reset
                        link using the marketplace email system.
                    </p>
                    <form className="auth-status-form" onSubmit={submit}>
                        <label>
                            <span>Email address</span>
                            <input
                                type="email"
                                autoComplete="email"
                                value={email}
                                onChange={(event) => setEmail(event.target.value)}
                                required
                            />
                        </label>
                        {error ? <p role="alert">{error}</p> : null}
                        {sent ? (
                            <p className="auth-status-success">
                                Check your inbox for the reset link.
                            </p>
                        ) : null}
                        <button
                            className="btn btn-primary"
                            type="submit"
                            disabled={isSubmitting || !email}
                        >
                            {isSubmitting ? "Sending..." : "Send reset link"}
                        </button>
                    </form>
                    <Link className="auth-status-link" to="/?auth=login">
                        Back to sign in
                    </Link>
                </section>
            </main>
            <Footer />
        </div>
    );
}

export default ForgotPasswordPage;
