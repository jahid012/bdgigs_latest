import { useMemo, useState } from "react";
import { Link, useNavigate, useParams, useSearchParams } from "react-router-dom";
import { apiRequest } from "../api/apiClient.js";
import { Icon } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import Footer from "../components/layout/Footer.jsx";
import Header from "../components/layout/Header.jsx";

function ResetPasswordPage({ onNavigate }) {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const notify = useToast();
    const initialEmail = useMemo(() => searchParams.get("email") || "", [searchParams]);
    const [form, setForm] = useState({
        email: initialEmail,
        password: "",
        password_confirmation: "",
    });
    const [error, setError] = useState("");
    const [isSubmitting, setIsSubmitting] = useState(false);

    const updateForm = (field, value) => {
        setForm((current) => ({ ...current, [field]: value }));
    };

    const submit = async (event) => {
        event.preventDefault();
        setError("");

        if (form.password !== form.password_confirmation) {
            setError("Passwords do not match.");
            return;
        }

        setIsSubmitting(true);

        try {
            await apiRequest("/reset-password", {
                body: {
                    token,
                    email: form.email,
                    password: form.password,
                    password_confirmation: form.password_confirmation,
                },
            });
            notify.success("Password reset. Sign in with your new password.");
            navigate("/?auth=login", { replace: true });
        } catch (nextError) {
            const message =
                nextError.payload?.errors?.email?.[0] ||
                nextError.payload?.errors?.password?.[0] ||
                nextError.message ||
                "Password reset failed.";
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
                    <h1>Choose a new password</h1>
                    <p>Use the email and token from your secure reset link.</p>
                    <form className="auth-status-form" onSubmit={submit}>
                        <label>
                            <span>Email address</span>
                            <input
                                type="email"
                                autoComplete="email"
                                value={form.email}
                                onChange={(event) => updateForm("email", event.target.value)}
                                required
                            />
                        </label>
                        <label>
                            <span>New password</span>
                            <input
                                type="password"
                                autoComplete="new-password"
                                minLength="8"
                                value={form.password}
                                onChange={(event) => updateForm("password", event.target.value)}
                                required
                            />
                        </label>
                        <label>
                            <span>Confirm password</span>
                            <input
                                type="password"
                                autoComplete="new-password"
                                minLength="8"
                                value={form.password_confirmation}
                                onChange={(event) =>
                                    updateForm("password_confirmation", event.target.value)
                                }
                                required
                            />
                        </label>
                        {error ? <p role="alert">{error}</p> : null}
                        <button
                            className="btn btn-primary"
                            type="submit"
                            disabled={
                                isSubmitting ||
                                !form.email ||
                                !form.password ||
                                !form.password_confirmation
                            }
                        >
                            {isSubmitting ? "Resetting..." : "Reset password"}
                        </button>
                    </form>
                    <Link className="auth-status-link" to="/forgot-password">
                        Request a new link
                    </Link>
                </section>
            </main>
            <Footer />
        </div>
    );
}

export default ResetPasswordPage;
