import { useCallback, useEffect, useMemo, useState } from "react";
import { useParams } from "react-router-dom";
import { orderSupportLinks } from "../data/orderDetailsData.js";
import { Icon, Rating } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import { useConversationLauncher } from "../hooks/useConversationLauncher.js";
import { useTranslation } from "react-i18next";
import { apiRequest } from "../api/apiClient.js";
const tabs = [
    {
        id: "activity",
        label: "Activity",
    },
    {
        id: "details",
        label: "Details",
    },
    {
        id: "requirements",
        label: "Requirements",
    },
];
function OrderDetailsPage({ variant = "buyer" }) {
    const { orderId } = useParams();
    const isSeller = variant === "seller";
    const notify = useToast();
    const launchConversation = useConversationLauncher();
    const [activeTab, setActiveTab] = useState("activity");
    const [conversationStatus, setConversationStatus] = useState("");
    const [order, setOrder] = useState(null);
    const [loadError, setLoadError] = useState("");
    const [extensionLoading, setExtensionLoading] = useState("");
    const [resolutionLoading, setResolutionLoading] = useState("");
    const [reviewLoading, setReviewLoading] = useState(false);
    const [requirementsLoading, setRequirementsLoading] = useState(false);
    const [lifecycleLoading, setLifecycleLoading] = useState("");
    const [activeActionModal, setActiveActionModal] = useState(null);
    const [isNoteModalOpen, setIsNoteModalOpen] = useState(false);
    const [noteLoading, setNoteLoading] = useState("");

    const loadOrder = useCallback(() => {
        setLoadError("");

        return apiRequest(
            `/api/orders/${encodeURIComponent(orderId)}?role=${isSeller ? "seller" : "buyer"}`,
        )
            .then(setOrder)
            .catch((error) =>
                setLoadError(error.message || "This order is unavailable."),
            );
    }, [isSeller, orderId]);

    useEffect(() => {
        loadOrder();
    }, [loadOrder]);

    const openOrderConversation = async () => {
        if (!order) return;
        setConversationStatus("Opening conversation...");

        try {
            await launchConversation({
                targetName: order.counterpartyName,
                targetSlug: order.counterpartyHandle?.replace("@", ""),
                contextType: "order",
                contextId: orderId || order.orderNumber,
            });
        } catch (error) {
            setConversationStatus(
                error.message || "This order conversation is unavailable.",
            );
        }
    };
    const requestTimeExtension = async (payload) => {
        if (!order) return false;

        setExtensionLoading("request");

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/time-extensions?role=seller`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Time extension request sent to the buyer.");
            setActiveTab("activity");
            setActiveActionModal(null);
            return true;
        } catch (error) {
            notify.error(
                error.message || "Time extension request could not be sent.",
            );
            return false;
        } finally {
            setExtensionLoading("");
        }
    };
    const decideTimeExtension = async (extensionId, decision) => {
        if (!order || !extensionId) return;

        setExtensionLoading(decision);

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/time-extensions/${extensionId}/decision?role=buyer`,
                { body: { decision } },
            );
            setOrder(updatedOrder);
            notify.success(
                decision === "accept"
                    ? "Time extension accepted."
                    : "Time extension rejected.",
            );
            setActiveTab("activity");
            setActiveActionModal(null);
        } catch (error) {
            notify.error(error.message || "Time extension could not be reviewed.");
        } finally {
            setExtensionLoading("");
        }
    };
    const savePrivateNote = async (body, noteId = null) => {
        if (!order) return false;

        setNoteLoading(noteId ? `update-${noteId}` : "create");

        try {
            const updatedOrder = await apiRequest(
                noteId
                    ? `/api/orders/${encodeURIComponent(order.orderNumber)}/private-notes/${noteId}?role=${isSeller ? "seller" : "buyer"}`
                    : `/api/orders/${encodeURIComponent(order.orderNumber)}/private-notes?role=${isSeller ? "seller" : "buyer"}`,
                {
                    method: noteId ? "PATCH" : "POST",
                    body: { body },
                },
            );
            setOrder(updatedOrder);
            notify.success(
                noteId ? "Private note updated." : "Private note added.",
            );
            return true;
        } catch (error) {
            notify.error(error.message || "Private note could not be saved.");
            return false;
        } finally {
            setNoteLoading("");
        }
    };
    const deletePrivateNote = async (noteId) => {
        if (!order || !noteId) return;

        setNoteLoading(`delete-${noteId}`);

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/private-notes/${noteId}?role=${isSeller ? "seller" : "buyer"}`,
                { method: "DELETE" },
            );
            setOrder(updatedOrder);
            notify.success("Private note deleted.");
        } catch (error) {
            notify.error(error.message || "Private note could not be deleted.");
        } finally {
            setNoteLoading("");
        }
    };
    const openDispute = async (payload) => {
        if (!order) return false;

        setResolutionLoading("open");

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/disputes?role=${isSeller ? "seller" : "buyer"}`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Resolution Center case opened.");
            setActiveTab("activity");
            setActiveActionModal("resolution");
            return true;
        } catch (error) {
            notify.error(error.message || "Resolution case could not be opened.");
            return false;
        } finally {
            setResolutionLoading("");
        }
    };
    const sendDisputeMessage = async (caseId, message) => {
        if (!order || !caseId) return false;

        setResolutionLoading(`message-${caseId}`);

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/disputes/${caseId}/messages?role=${isSeller ? "seller" : "buyer"}`,
                { body: { message } },
            );
            setOrder(updatedOrder);
            notify.success("Resolution message added.");
            setActiveTab("activity");
            setActiveActionModal("resolution");
            return true;
        } catch (error) {
            notify.error(error.message || "Resolution message could not be sent.");
            return false;
        } finally {
            setResolutionLoading("");
        }
    };
    const submitReview = async (payload) => {
        if (!order) return false;

        setReviewLoading(true);

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/reviews?role=${isSeller ? "seller" : "buyer"}`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Review submitted.");
            setActiveTab("activity");
            setActiveActionModal(null);
            return true;
        } catch (error) {
            notify.error(error.message || "Review could not be submitted.");
            return false;
        } finally {
            setReviewLoading(false);
        }
    };
    const submitRequirements = async (payload) => {
        if (!order) return false;

        setRequirementsLoading(true);

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/requirements?role=${isSeller ? "seller" : "buyer"}`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Order requirements submitted.");
            setActiveTab("activity");
            return true;
        } catch (error) {
            notify.error(error.message || "Requirements could not be submitted.");
            return false;
        } finally {
            setRequirementsLoading(false);
        }
    };
    const submitDelivery = async (payload) => {
        if (!order) return false;

        setLifecycleLoading("delivery");

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/deliveries?role=seller`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Delivery submitted to the buyer.");
            setActiveTab("activity");
            setActiveActionModal(null);
            return true;
        } catch (error) {
            notify.error(error.message || "Delivery could not be submitted.");
            return false;
        } finally {
            setLifecycleLoading("");
        }
    };
    const requestRevision = async (payload) => {
        if (!order) return false;

        setLifecycleLoading("revision");

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/revision-requests?role=buyer`,
                { body: payload },
            );
            setOrder(updatedOrder);
            notify.success("Revision request sent.");
            setActiveTab("activity");
            setActiveActionModal(null);
            return true;
        } catch (error) {
            notify.error(error.message || "Revision could not be requested.");
            return false;
        } finally {
            setLifecycleLoading("");
        }
    };
    const completeOrder = async () => {
        if (!order) return;

        setLifecycleLoading("complete");

        try {
            const updatedOrder = await apiRequest(
                `/api/orders/${encodeURIComponent(order.orderNumber)}/complete?role=buyer`,
                { body: {} },
            );
            setOrder(updatedOrder);
            notify.success("Order completed.");
            setActiveTab("activity");
            setActiveActionModal(null);
        } catch (error) {
            notify.error(error.message || "Order could not be completed.");
        } finally {
            setLifecycleLoading("");
        }
    };

    if (loadError) {
        return (
            <main className="dashboard-content order-details-page">
                <div className="order-detail-card">
                    <h1>Order unavailable</h1>
                    <p>{loadError}</p>
                </div>
            </main>
        );
    }

    if (!order) {
        return (
            <main className="dashboard-content order-details-page">
                <p className="messages-empty">Loading order details...</p>
            </main>
        );
    }

    return (
        <main className="dashboard-content order-details-page">
            <div className="order-details-shell">
                <section
                    className="order-details-main"
                    aria-label={`Order ${orderId || order.orderNumber}`}
                >
                    <OrderTabs activeTab={activeTab} onChange={setActiveTab} />
                    {activeTab === "activity" ? (
                        <OrderActivity
                            isSeller={isSeller}
                            onOpenReview={() => setActiveActionModal("review")}
                            order={order}
                        />
                    ) : null}
                    {activeTab === "details" ? (
                        <OrderDetailsPanel order={order} />
                    ) : null}
                    {activeTab === "requirements" ? (
                        <OrderRequirementsPanel
                            isLoading={requirementsLoading}
                            isSeller={isSeller}
                            onSubmit={submitRequirements}
                            requirementsState={order.requirementsState}
                        />
                    ) : null}
                </section>

                <OrderDetailsSidebar
                    conversationStatus={conversationStatus}
                    extensionLoading={extensionLoading}
                    isSeller={isSeller}
                    onDecideExtension={decideTimeExtension}
                    onOpenConversation={openOrderConversation}
                    onOpenHelp={() => setActiveActionModal("help")}
                    onOpenPrivateNotes={() => setIsNoteModalOpen(true)}
                    onOpenResolution={() => setActiveActionModal("resolution")}
                    onOpenTimeExtension={() => setActiveActionModal("time-extension")}
                    onOpenDelivery={() => setActiveActionModal("delivery")}
                    onRequestExtension={requestTimeExtension}
                    order={order}
                />
            </div>
            <PrivateNotesModal
                isLoading={noteLoading}
                isOpen={isNoteModalOpen}
                notes={order.privateNotes || []}
                onClose={() => setIsNoteModalOpen(false)}
                onDelete={deletePrivateNote}
                onSave={savePrivateNote}
            />
            <OrderHelpModal
                faqs={order.faq || []}
                isOpen={activeActionModal === "help"}
                isSeller={isSeller}
                order={order}
                onClose={() => setActiveActionModal(null)}
            />
            <ResolutionCenterModal
                isLoading={resolutionLoading}
                isOpen={activeActionModal === "resolution"}
                onClose={() => setActiveActionModal(null)}
                onOpenDispute={openDispute}
                onSendMessage={sendDisputeMessage}
                resolution={order.resolutionCenter}
            />
            <TimeExtensionModal
                extension={order.timeExtension}
                isLoading={extensionLoading}
                isOpen={activeActionModal === "time-extension"}
                isSeller={isSeller}
                onClose={() => setActiveActionModal(null)}
                onDecide={decideTimeExtension}
                onRequest={requestTimeExtension}
            />
            <ReviewModal
                isLoading={reviewLoading}
                isOpen={activeActionModal === "review"}
                isSeller={isSeller}
                onClose={() => setActiveActionModal(null)}
                onSubmit={submitReview}
                reviewState={order.reviewsState}
            />
            <DeliveryModal
                deliveryFlow={order.deliveryFlow}
                isLoading={lifecycleLoading}
                isOpen={activeActionModal === "delivery"}
                isSeller={isSeller}
                onClose={() => setActiveActionModal(null)}
                onComplete={completeOrder}
                onRequestRevision={requestRevision}
                onSubmitDelivery={submitDelivery}
            />
        </main>
    );
}
function OrderTabs({ activeTab, onChange }) {
    const { t } = useTranslation();
    return (
        <nav
            className="order-detail-tabs"
            aria-label={t("pages.orderdetailspage.orderDetailSections")}
        >
            {tabs.map((tab) => (
                <button
                    className={activeTab === tab.id ? "active" : ""}
                    type="button"
                    aria-pressed={activeTab === tab.id}
                    key={tab.id}
                    onClick={() => onChange(tab.id)}
                >
                    {tab.label}
                </button>
            ))}
        </nav>
    );
}
function OrderDetailsPanel({ order }) {
    const { t } = useTranslation();
    return (
        <article className="order-detail-card order-invoice-card">
            <div className="order-detail-card-head">
                <div>
                    <h1>{order.serviceTitle}</h1>
                    <p>
                        {" "}
                        {t("pages.orderdetailspage.orderedBy")}{" "}
                        <strong>{order.orderedBy}</strong>{" "}
                        <a href="#history">
                            {t("pages.orderdetailspage.viewHistory")}
                        </a>
                        <span>
                            {t("pages.orderdetailspage.dateOrdered")}{" "}
                            <strong>{order.dateOrdered}</strong>
                        </span>
                    </p>
                </div>
                <div className="order-total-block">
                    <span>{t("pages.orderdetailspage.totalPrice")}</span>
                    <strong>{order.totalPrice}</strong>
                </div>
            </div>

            <div className="order-number-row">
                <span>{t("pages.orderdetailspage.orderNumber")}</span>
                <strong>{order.orderNumber}</strong>
            </div>

            {order.orderBullets?.length ? (
                <ol className="order-bullet-list">
                    {order.orderBullets.map((item) => (
                        <li key={item}>{item}</li>
                    ))}
                </ol>
            ) : null}

            <div
                className="order-item-table"
                role="table"
                aria-label={t("pages.orderdetailspage.orderItemDetails")}
            >
                <div className="order-item-table-head" role="row">
                    <strong>{t("pages.orderdetailspage.item")}</strong>
                    <strong>{t("pages.orderdetailspage.qty")}</strong>
                    <strong>{t("pages.orderdetailspage.duration")}</strong>
                    <strong>{t("pages.orderdetailspage.price")}</strong>
                </div>
                <div className="order-item-table-row" role="row">
                    <div>
                        <strong>{order.serviceTitle}</strong>
                        <p>{order.itemSummary}</p>
                        <span>{order.revisions}</span>
                    </div>
                    <span>{order.quantity}</span>
                    <span>{order.duration}</span>
                    <span>{order.totalPrice}</span>
                </div>
                <div className="order-item-table-total" role="row">
                    <strong>{t("pages.orderdetailspage.total")}</strong>
                    <strong>{order.totalPrice}</strong>
                </div>
            </div>
        </article>
    );
}
function OrderRequirementsPanel({
    isLoading = false,
    isSeller,
    onSubmit,
    requirementsState = {},
}) {
    const { t } = useTranslation();
    const requirements = requirementsState.items || [];
    const [answers, setAnswers] = useState({});
    const [files, setFiles] = useState({});
    const [error, setError] = useState("");

    useEffect(() => {
        setAnswers(
            Object.fromEntries(
                requirements.map((requirement) => [
                    requirement.id,
                    requirement.answer || "",
                ]),
            ),
        );
        setFiles({});
        setError("");
    }, [requirementsState.submittedAt, requirements.length]);

    const updateAnswer = (id, value) => {
        setAnswers((current) => ({ ...current, [id]: value }));
    };
    const submitRequirements = async (event) => {
        event.preventDefault();

        const missing = requirements.find((requirement) => {
            if (!requirement.required) return false;
            if (String(requirement.type).toLowerCase() === "file upload") {
                return !files[requirement.id] && !requirement.files?.length;
            }
            return !String(answers[requirement.id] || "").trim();
        });

        if (missing) {
            setError(`Please complete: ${missing.question}`);
            return;
        }

        const formData = new FormData();
        Object.entries(answers).forEach(([id, value]) => {
            formData.append(`answers[${id}]`, value || "");
        });
        Object.entries(files).forEach(([id, file]) => {
            if (file) {
                formData.append(`files[${id}]`, file);
            }
        });

        setError("");
        await onSubmit(formData);
    };

    if (requirements.length === 0) {
        return (
            <article className="order-detail-card order-requirements-card">
                <p className="messages-empty">
                    No saved order requirements are available yet.
                </p>
            </article>
        );
    }

    return (
        <article className="order-detail-card order-requirements-card">
            <div className="order-detail-card-head compact">
                <div>
                    <h2>Order requirements</h2>
                    <p>{requirementsState.statusLabel}</p>
                </div>
                {requirementsState.submitted ? (
                    <span className="status-badge status-completed">
                        Submitted
                    </span>
                ) : (
                    <span className="status-badge status-delivered">
                        Pending
                    </span>
                )}
            </div>

            {!isSeller && requirementsState.canSubmit ? (
                <form
                    className="order-requirements-form"
                    onSubmit={submitRequirements}
                >
                    {requirements.map((requirement, index) => (
                        <RequirementField
                            answer={answers[requirement.id] || ""}
                            file={files[requirement.id]}
                            index={index}
                            key={requirement.id}
                            onAnswerChange={updateAnswer}
                            onFileChange={(file) =>
                                setFiles((current) => ({
                                    ...current,
                                    [requirement.id]: file,
                                }))
                            }
                            requirement={requirement}
                        />
                    ))}
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <button type="submit" disabled={isLoading}>
                        {isLoading
                            ? "Submitting..."
                            : requirementsState.submitted
                                ? "Update requirements"
                                : "Submit requirements"}
                    </button>
                </form>
            ) : (
                <div className="order-requirement-readonly-list">
                    {requirements.map((requirement, index) => (
                        <RequirementAnswer
                            index={index}
                            key={requirement.id}
                            requirement={requirement}
                            t={t}
                        />
                    ))}
                    {!requirementsState.submitted ? (
                        <p className="messages-empty">
                            {isSeller
                                ? "Waiting for the buyer to submit these requirements."
                                : "Requirements are waiting for submission."}
                        </p>
                    ) : null}
                </div>
            )}
        </article>
    );
}

function RequirementField({
    answer,
    file,
    index,
    onAnswerChange,
    onFileChange,
    requirement,
}) {
    const type = String(requirement.type || "Free text").toLowerCase();

    return (
        <section className="order-requirement-item">
            <h2>
                {index + 1}. {requirement.question}
                {!requirement.required ? <span>Optional</span> : null}
            </h2>
            {type === "multiple choice" ? (
                <select
                    value={answer}
                    onChange={(event) =>
                        onAnswerChange(requirement.id, event.target.value)
                    }
                    required={requirement.required}
                >
                    <option value="">Choose an option</option>
                    {(requirement.options || []).map((option) => (
                        <option value={option} key={option}>
                            {option}
                        </option>
                    ))}
                </select>
            ) : type === "file upload" ? (
                <label className="order-requirement-file">
                    <span>{file?.name || "Choose a file"}</span>
                    <input
                        type="file"
                        required={
                            requirement.required && !requirement.files?.length
                        }
                        onChange={(event) =>
                            onFileChange(event.target.files?.[0] || null)
                        }
                    />
                </label>
            ) : (
                <textarea
                    rows="4"
                    value={answer}
                    placeholder="Share the details the seller needs to start."
                    required={requirement.required}
                    onChange={(event) =>
                        onAnswerChange(requirement.id, event.target.value)
                    }
                />
            )}
            {requirement.files?.length ? (
                <RequirementFiles files={requirement.files} />
            ) : null}
        </section>
    );
}

function RequirementAnswer({ index, requirement, t }) {
    const answer = Array.isArray(requirement.answer)
        ? requirement.answer.join(", ")
        : requirement.answer;

    return (
        <section className="order-requirement-item">
            <h2>
                {index + 1}. {requirement.question}
                {requirement.optional ? (
                    <span>{t("pages.orderdetailspage.optional")}</span>
                ) : null}
            </h2>
            {answer ? (
                <p>{answer}</p>
            ) : (
                <p className="empty">Not answered yet.</p>
            )}
            {requirement.files?.length ? (
                <RequirementFiles files={requirement.files} />
            ) : null}
        </section>
    );
}

function RequirementFiles({ files = [] }) {
    return (
        <div className="order-requirement-files">
            {files.map((file) => (
                <a href={file.url} key={`${file.url}-${file.name}`} download>
                    <Icon name="paperclip" />
                    {file.name || "Requirement file"}
                </a>
            ))}
        </div>
    );
}
function OrderActivity({
    isSeller,
    onOpenReview,
    order,
}) {
    return (
        <article className="order-detail-card order-activity-card">
            <div className="order-detail-card-head compact">
                <div>
                    <h2>Order activity</h2>
                    <p>Important order events appear here chronologically.</p>
                </div>
                <span className="order-date-pill">{order.dateOrdered}</span>
            </div>
            <OrderReviewTimelineStatus
                isSeller={isSeller}
                onOpenReview={onOpenReview}
                reviewState={order.reviewsState}
            />
            {order.activity?.length ? (
                <div className="order-timeline">
                    {order.activity.map((item) => (
                        <TimelineItem
                            actorAvatar={item.actorAvatar}
                            actorInitials={item.actorInitials}
                            actorName={item.actorName}
                            color={item.color || "green"}
                            icon={item.icon || "orders"}
                            detail={item.detail}
                            title={item.title}
                            time={item.time}
                            key={item.id || `${item.title}-${item.time}`}
                        />
                    ))}
                </div>
            ) : (
                <p className="messages-empty">
                    Order activity will appear here as delivery events are
                    recorded.
                </p>
            )}
        </article>
    );
}

function OrderReviewTimelineStatus({ isSeller, onOpenReview, reviewState = {} }) {
    const visibleReviews = reviewState.visibleReviews || {};
    const reviews = [visibleReviews.buyer, visibleReviews.seller].filter(Boolean);

    if (!reviewState.completed && !reviews.length) {
        return null;
    }

    return (
        <section className="order-review-timeline-status">
            <div>
                <strong>Reviews</strong>
                <span>{reviewState.nextStep || "Review status will appear here."}</span>
                {reviewState.deadlineLabel ? (
                    <small>Deadline: {reviewState.deadlineLabel}</small>
                ) : null}
            </div>
            {reviewState.canReview ? (
                <button type="button" onClick={onOpenReview}>
                    {isSeller ? "Review buyer" : "Review seller"}
                </button>
            ) : null}
            {reviews.length ? (
                <div className="order-review-mini-list">
                    {reviews.map((review) => (
                        <article key={review.id}>
                            <CounterpartyAvatar
                                avatar={review.reviewerAvatar}
                                initials={review.reviewerInitials}
                                name={review.reviewerName}
                            />
                            <span>
                                <strong>
                                    {review.reviewerName}{" "}
                                    <Rating value={review.rating} />
                                </strong>
                                <small>{review.submittedAt}</small>
                            </span>
                        </article>
                    ))}
                </div>
            ) : null}
        </section>
    );
}

function ReviewActivitySection({
    isLoading,
    isSeller,
    onSubmit,
    reviewState = {},
}) {
    const [draft, setDraft] = useState({ rating: 5, comment: "" });
    const [error, setError] = useState("");
    const visibleReviews = reviewState.visibleReviews || {};
    const reviews = [visibleReviews.buyer, visibleReviews.seller].filter(Boolean);

    const submitReview = async (event) => {
        event.preventDefault();

        if (draft.comment.trim().length < 10) {
            setError("Write at least 10 characters for your review.");
            return;
        }

        setError("");
        const saved = await onSubmit({
            rating: Number(draft.rating),
            comment: draft.comment.trim(),
        });

        if (saved) {
            setDraft({ rating: 5, comment: "" });
        }
    };

    return (
        <article className="order-detail-card order-review-activity-card">
            <div className="order-detail-card-head compact">
                <div>
                    <h2>Reviews</h2>
                    <p>{reviewState.nextStep || "Review status will appear here."}</p>
                    {reviewState.deadlineLabel ? (
                        <span className="order-review-deadline">
                            Deadline: {reviewState.deadlineLabel}
                        </span>
                    ) : null}
                </div>
            </div>

            {reviews.length ? (
                <div className="order-review-list">
                    {reviews.map((review) => (
                        <article key={review.id}>
                            <span className="avatar">
                                {review.reviewerInitials}
                            </span>
                            <div>
                                <strong>
                                    {review.reviewerName}{" "}
                                    <Rating value={review.rating} />
                                </strong>
                                <small>
                                    {review.role === "buyer"
                                        ? "Buyer review"
                                        : "Seller review"}{" "}
                                    - {review.submittedAt}
                                </small>
                                <p>{review.comment}</p>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="messages-empty">
                    Mutual reviews stay private until the review flow allows
                    them to be shown.
                </p>
            )}

            {reviewState.canReview ? (
                <form className="order-review-form" onSubmit={submitReview}>
                    <label>
                        <span>{isSeller ? "Rate the buyer" : "Rate the seller"}</span>
                        <select
                            value={draft.rating}
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    rating: event.target.value,
                                }))
                            }
                        >
                            {[5, 4, 3, 2, 1].map((rating) => (
                                <option value={rating} key={rating}>
                                    {rating} stars
                                </option>
                            ))}
                        </select>
                    </label>
                    <label>
                        <span>Your review</span>
                        <textarea
                            rows="4"
                            value={draft.comment}
                            placeholder="Share clear, professional feedback about this order."
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    comment: event.target.value,
                                }))
                            }
                        />
                    </label>
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <button type="submit" disabled={isLoading}>
                        {isLoading ? "Submitting..." : "Submit review"}
                    </button>
                </form>
            ) : null}
        </article>
    );
}

function ResolutionCenterSection({
    isLoading,
    onOpenDispute,
    onSendMessage,
    resolution = {},
}) {
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [draft, setDraft] = useState({
        reason: "",
        description: "",
        priority: "normal",
    });
    const [attachments, setAttachments] = useState([]);
    const [messageDrafts, setMessageDrafts] = useState({});
    const [error, setError] = useState("");
    const cases = resolution.cases || [];

    const submitDispute = async (event) => {
        event.preventDefault();

        if (draft.description.trim().length < 10) {
            setError("Describe the issue in at least 10 characters.");
            return;
        }

        const formData = new FormData();
        formData.append("reason", draft.reason.trim());
        formData.append("description", draft.description.trim());
        formData.append("priority", draft.priority);
        attachments.forEach((file) => {
            formData.append("attachments[]", file);
        });

        setError("");
        const saved = await onOpenDispute(formData);

        if (saved) {
            setDraft({ reason: "", description: "", priority: "normal" });
            setAttachments([]);
            setIsFormOpen(false);
        }
    };
    const submitMessage = async (event, caseId) => {
        event.preventDefault();
        const message = String(messageDrafts[caseId] || "").trim();

        if (message.length < 2) return;

        const saved = await onSendMessage(caseId, message);

        if (saved) {
            setMessageDrafts((current) => ({ ...current, [caseId]: "" }));
        }
    };

    return (
        <article className="order-detail-card order-resolution-card">
            <div className="order-detail-card-head compact">
                <div>
                    <h2>Resolution Center</h2>
                    <p>
                        Create a case when the order needs buyer, seller, and
                        support visibility.
                    </p>
                </div>
                {resolution.canOpen && !isFormOpen ? (
                    <button type="button" onClick={() => setIsFormOpen(true)}>
                        Create dispute
                    </button>
                ) : null}
            </div>

            {isFormOpen ? (
                <form className="order-resolution-form" onSubmit={submitDispute}>
                    <label>
                        <span>Reason</span>
                        <input
                            value={draft.reason}
                            placeholder="Delivery scope disagreement"
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    reason: event.target.value,
                                }))
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>Priority</span>
                        <select
                            value={draft.priority}
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    priority: event.target.value,
                                }))
                            }
                        >
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </label>
                    <label>
                        <span>Description</span>
                        <textarea
                            rows="4"
                            value={draft.description}
                            placeholder="Explain what happened and what outcome you are requesting."
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    description: event.target.value,
                                }))
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>Attachments</span>
                        <input
                            type="file"
                            multiple
                            onChange={(event) =>
                                setAttachments(
                                    Array.from(event.target.files || []),
                                )
                            }
                        />
                    </label>
                    {attachments.length ? (
                        <p className="order-resolution-attachment-note">
                            {attachments.length} file
                            {attachments.length === 1 ? "" : "s"} selected
                        </p>
                    ) : null}
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <div>
                        <button type="button" onClick={() => setIsFormOpen(false)}>
                            Cancel
                        </button>
                        <button type="submit" disabled={isLoading === "open"}>
                            {isLoading === "open" ? "Opening..." : "Open case"}
                        </button>
                    </div>
                </form>
            ) : null}

            <div className="order-resolution-list">
                {cases.length ? (
                    cases.map((item) => (
                        <section key={item.id}>
                            <header>
                                <div>
                                    <strong>
                                        {item.caseCode} - {item.reason}
                                    </strong>
                                    <span>
                                        {item.statusLabel} - {item.priority}
                                    </span>
                                </div>
                                <small>Opened {item.openedAt}</small>
                            </header>
                            <p>{item.description}</p>
                            {item.attachments?.length ? (
                                <div className="order-resolution-attachments">
                                    {item.attachments.map((file) => (
                                        <a
                                            href={file.url}
                                            key={`${item.id}-${file.url}`}
                                            download
                                        >
                                            <Icon name="paperclip" />
                                            {file.name || "Attachment"}
                                        </a>
                                    ))}
                                </div>
                            ) : null}
                            <div className="order-resolution-messages">
                                {item.messages.map((message) => (
                                    <article key={message.id}>
                                        <span className="avatar">
                                            {message.actorInitials}
                                        </span>
                                        <div>
                                            <strong>{message.title}</strong>
                                            <small>
                                                {message.actorName} - {message.time}
                                            </small>
                                            {message.body ? <p>{message.body}</p> : null}
                                        </div>
                                    </article>
                                ))}
                            </div>
                            {!item.isTerminal ? (
                                <form
                                    className="order-resolution-message-form"
                                    onSubmit={(event) =>
                                        submitMessage(event, item.id)
                                    }
                                >
                                    <input
                                        value={messageDrafts[item.id] || ""}
                                        placeholder="Add a resolution message"
                                        onChange={(event) =>
                                            setMessageDrafts((current) => ({
                                                ...current,
                                                [item.id]: event.target.value,
                                            }))
                                        }
                                    />
                                    <button
                                        type="submit"
                                        disabled={isLoading === `message-${item.id}`}
                                    >
                                        {isLoading === `message-${item.id}`
                                            ? "Sending..."
                                            : "Send"}
                                    </button>
                                </form>
                            ) : null}
                        </section>
                    ))
                ) : (
                    <p className="messages-empty">
                        No Resolution Center cases are linked to this order.
                    </p>
                )}
            </div>
        </article>
    );
}

function OrderFaqSection({ faqs = [] }) {
    return (
        <article className="order-detail-card order-faq-card">
            <div className="order-detail-card-head compact">
                <div>
                    <h2>Order help</h2>
                    <p>Quick answers for order delivery and dispute questions.</p>
                </div>
            </div>
            <OrderFaqList faqs={faqs} />
        </article>
    );
}

function OrderFaqList({ faqs = [] }) {
    const [openIndex, setOpenIndex] = useState(0);

    return (
        <div className="order-faq-list">
            {faqs.map((faq, index) => (
                <section key={faq.question}>
                    <button
                        type="button"
                        aria-expanded={openIndex === index}
                        onClick={() =>
                            setOpenIndex((current) =>
                                current === index ? -1 : index,
                            )
                        }
                    >
                        <strong>{faq.question}</strong>
                        <Icon name="chevronDown" />
                    </button>
                    {openIndex === index ? <p>{faq.answer}</p> : null}
                </section>
            ))}
            {!faqs.length ? (
                <p className="messages-empty">No order help topics yet.</p>
            ) : null}
        </div>
    );
}

function OrderHelpModal({ faqs = [], isOpen, isSeller, onClose, order }) {
    if (!isOpen) return null;

    return (
        <ActionModal
            kicker={isSeller ? "Seller help" : "Buyer help"}
            onClose={onClose}
            title="Order Help"
        >
            <div className="order-help-next-step">
                <strong>
                    {isSeller ? "Recommended next step" : "What happens next"}
                </strong>
                <p>
                    {isSeller
                        ? order.requirementsState?.submitted
                            ? "Review requirements, keep delivery updates in the conversation, and submit work before the deadline."
                            : "Wait for the buyer to submit requirements before starting work."
                        : order.requirementsState?.submitted
                            ? "The seller has your requirements and can continue the order."
                            : "Submit the Requirements tab so the seller can start work."}
                </p>
            </div>
            <section className="order-help-faq-block">
                <header>
                    <h3>Order help</h3>
                    <p>Quick answers for order delivery and dispute questions.</p>
                </header>
                <OrderFaqList faqs={faqs} />
            </section>
        </ActionModal>
    );
}

function ResolutionCenterModal({
    isLoading,
    isOpen,
    onClose,
    onOpenDispute,
    onSendMessage,
    resolution,
}) {
    if (!isOpen) return null;

    return (
        <ActionModal
            kicker="Order support"
            onClose={onClose}
            title="Resolution Center"
        >
            <ResolutionCenterSection
                isLoading={isLoading}
                onOpenDispute={onOpenDispute}
                onSendMessage={onSendMessage}
                resolution={resolution}
            />
        </ActionModal>
    );
}

function TimeExtensionModal({
    extension,
    isLoading,
    isOpen,
    isSeller,
    onClose,
    onDecide,
    onRequest,
}) {
    if (!isOpen) return null;

    return (
        <ActionModal
            kicker="Delivery deadline"
            onClose={onClose}
            title="Time Extension"
        >
            <TimeExtensionWorkflow
                extension={extension}
                isLoading={isLoading}
                isSeller={isSeller}
                onDecide={onDecide}
                onRequest={onRequest}
            />
        </ActionModal>
    );
}

function ReviewModal({
    isLoading,
    isOpen,
    isSeller,
    onClose,
    onSubmit,
    reviewState,
}) {
    if (!isOpen) return null;

    return (
        <ActionModal
            kicker="Mutual review"
            onClose={onClose}
            title={isSeller ? "Review Buyer" : "Review Seller"}
        >
            <ReviewActivitySection
                isLoading={isLoading}
                isSeller={isSeller}
                onSubmit={onSubmit}
                reviewState={reviewState}
            />
        </ActionModal>
    );
}

function DeliveryModal({
    deliveryFlow = {},
    isLoading,
    isOpen,
    isSeller,
    onClose,
    onComplete,
    onRequestRevision,
    onSubmitDelivery,
}) {
    if (!isOpen) return null;

    return (
        <ActionModal
            kicker="Order delivery"
            onClose={onClose}
            title={isSeller ? "Submit Delivery" : "Review Delivery"}
        >
            <DeliveryWorkflow
                deliveryFlow={deliveryFlow}
                isLoading={isLoading}
                isSeller={isSeller}
                onComplete={onComplete}
                onRequestRevision={onRequestRevision}
                onSubmitDelivery={onSubmitDelivery}
            />
        </ActionModal>
    );
}

function DeliveryWorkflow({
    deliveryFlow = {},
    isLoading,
    isSeller,
    onComplete,
    onRequestRevision,
    onSubmitDelivery,
}) {
    const [deliveryDraft, setDeliveryDraft] = useState("");
    const [revisionDraft, setRevisionDraft] = useState("");
    const [files, setFiles] = useState([]);
    const [error, setError] = useState("");
    const latest = deliveryFlow.latest;

    const submitDelivery = async (event) => {
        event.preventDefault();

        if (deliveryDraft.trim().length < 10) {
            setError("Write at least 10 characters for the delivery note.");
            return;
        }

        const formData = new FormData();
        formData.append("message", deliveryDraft.trim());
        files.forEach((file) => formData.append("files[]", file));
        setError("");

        const saved = await onSubmitDelivery(formData);

        if (saved) {
            setDeliveryDraft("");
            setFiles([]);
        }
    };
    const submitRevision = async (event) => {
        event.preventDefault();

        if (revisionDraft.trim().length < 10) {
            setError("Write at least 10 characters for the revision request.");
            return;
        }

        setError("");
        const saved = await onRequestRevision({
            message: revisionDraft.trim(),
        });

        if (saved) {
            setRevisionDraft("");
        }
    };

    return (
        <div className="order-delivery-workflow">
            {latest ? (
                <article className="order-delivery-latest">
                    <header>
                        <strong>Latest delivery</strong>
                        <span>{latest.statusLabel}</span>
                    </header>
                    <p>{latest.message}</p>
                    {latest.revisionMessage ? (
                        <p>
                            <strong>Revision request:</strong>{" "}
                            {latest.revisionMessage}
                        </p>
                    ) : null}
                    {latest.files?.length ? (
                        <RequirementFiles files={latest.files} />
                    ) : null}
                </article>
            ) : (
                <p className="messages-empty">No delivery has been submitted.</p>
            )}

            {isSeller && deliveryFlow.canSubmitDelivery ? (
                <form
                    className="order-delivery-form"
                    onSubmit={submitDelivery}
                >
                    <label>
                        <span>Delivery note</span>
                        <textarea
                            rows="5"
                            value={deliveryDraft}
                            placeholder="Explain what you delivered and how the buyer can review it."
                            onChange={(event) =>
                                setDeliveryDraft(event.target.value)
                            }
                        />
                    </label>
                    <label>
                        <span>Delivery files</span>
                        <input
                            type="file"
                            multiple
                            onChange={(event) =>
                                setFiles(Array.from(event.target.files || []))
                            }
                        />
                    </label>
                    {files.length ? (
                        <p className="order-resolution-attachment-note">
                            {files.length} file{files.length === 1 ? "" : "s"} selected
                        </p>
                    ) : null}
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <button type="submit" disabled={isLoading === "delivery"}>
                        {isLoading === "delivery"
                            ? "Submitting..."
                            : "Submit delivery"}
                    </button>
                </form>
            ) : null}

            {!isSeller && deliveryFlow.canRequestRevision ? (
                <form
                    className="order-delivery-form"
                    onSubmit={submitRevision}
                >
                    <label>
                        <span>Revision details</span>
                        <textarea
                            rows="4"
                            value={revisionDraft}
                            placeholder="Describe the changes needed before you accept the delivery."
                            onChange={(event) =>
                                setRevisionDraft(event.target.value)
                            }
                        />
                    </label>
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <div className="order-extension-actions">
                        <button
                            type="submit"
                            disabled={isLoading === "revision"}
                        >
                            {isLoading === "revision"
                                ? "Sending..."
                                : "Request revision"}
                        </button>
                        <button
                            type="button"
                            disabled={isLoading === "complete"}
                            onClick={onComplete}
                        >
                            {isLoading === "complete"
                                ? "Completing..."
                                : "Accept and complete"}
                        </button>
                    </div>
                </form>
            ) : null}
        </div>
    );
}

function ActionModal({ children, kicker, onClose, title }) {
    return (
        <div
            className="order-note-modal-backdrop"
            role="presentation"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <section
                className="order-note-modal order-action-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby={`orderAction${title.replace(/\s+/g, "")}`}
            >
                <header>
                    <div>
                        <span>{kicker}</span>
                        <h2 id={`orderAction${title.replace(/\s+/g, "")}`}>
                            {title}
                        </h2>
                    </div>
                    <button
                        type="button"
                        aria-label={`Close ${title}`}
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </header>
                <div className="order-action-modal-body">{children}</div>
            </section>
        </div>
    );
}

function TimelineItem({
    actorAvatar,
    actorInitials,
    actorName,
    children,
    color,
    detail,
    icon,
    time,
    title,
}) {
    return (
        <section className="order-timeline-item">
            <span className={`order-timeline-icon ${color}`} aria-hidden="true">
                <Icon name={icon} />
            </span>
            <div className="order-timeline-content">
                <header>
                    <strong>{title}</strong>
                    <time>{time}</time>
                    {children ? <Icon name="chevronDown" /> : null}
                </header>
                {actorName ? (
                    <div className="order-timeline-actor">
                        <CounterpartyAvatar
                            avatar={actorAvatar}
                            initials={actorInitials}
                            name={actorName}
                        />
                        <span>{actorName}</span>
                    </div>
                ) : null}
                {detail ? <p className="order-timeline-detail">{detail}</p> : null}
                {children ? (
                    <div className="order-timeline-panel">{children}</div>
                ) : null}
            </div>
        </section>
    );
}
function ActivityMessage({ avatarLabel, message, title }) {
    return (
        <article className="order-activity-message">
            {title ? <h3>{title}</h3> : null}
            <div>
                <span className="avatar">
                    {avatarLabel === "Me" ? "JA" : avatarLabel.slice(0, 2)}
                </span>
                <p>
                    <strong>{avatarLabel}</strong>
                    {message}
                </p>
            </div>
        </article>
    );
}
function ReviewCard({ avatar, heading, message, name }) {
    const { t } = useTranslation();
    return (
        <article className="order-review-card">
            <h3>{heading}</h3>
            <div className="order-review-body">
                <img src={avatar} alt="" />
                <div>
                    <strong>
                        {name} <Rating value="5" />
                    </strong>
                    <p>{message}</p>
                </div>
            </div>
            <dl className="order-review-ratings">
                {[
                    "Seller communication level",
                    "Quality of delivery",
                    "Value of delivery",
                ].map((label) => (
                    <div key={label}>
                        <dt>{label}</dt>
                        <dd>
                            <Rating value="5" />
                        </dd>
                    </div>
                ))}
            </dl>
            <div className="order-policy-note">
                <strong>{t("pages.orderdetailspage.ourPolicy")}</strong>
                <p>
                    {t(
                        "pages.orderdetailspage.ratingsAndReviewsReflectTheBuyersIndividualExperience",
                    )}
                </p>
            </div>
        </article>
    );
}
function OrderDetailsSidebar({
    conversationStatus = "",
    extensionLoading = "",
    isSeller,
    onDecideExtension,
    onOpenConversation,
    onOpenDelivery,
    onOpenHelp,
    onOpenPrivateNotes,
    onOpenResolution,
    onOpenTimeExtension,
    onRequestExtension,
    order,
}) {
    const { t } = useTranslation();
    return (
        <aside
            className="order-detail-sidebar"
            aria-label={t("pages.orderdetailspage.orderDetailsSidebar")}
        >
            <section className="order-side-card">
                <h2>{t("pages.orderdetailspage.orderDetails")}</h2>
                <div className="order-side-service">
                    {order.serviceImage ? (
                        <img src={order.serviceImage} alt="" />
                    ) : null}
                    <div>
                        <strong>{order.serviceSummary}</strong>
                        <span className={`status-badge ${order.statusClass}`}>
                            {order.status}
                        </span>
                    </div>
                </div>
                <div className="order-side-person">
                    <CounterpartyAvatar
                        avatar={order.counterpartyAvatar}
                        initials={order.counterpartyInitials}
                        name={order.counterpartyName}
                    />
                    <div>
                        <strong>{order.counterpartyName}</strong>
                        <span>{order.counterpartyHandle}</span>
                        <small>
                            {t("pages.orderdetailspage.lastSeen2MonthsAgo")}
                        </small>
                    </div>
                </div>
                <OrderCountdown
                    deliveryDate={order.deliveryDate}
                    dueAt={order.deliveryDueAt}
                    status={order.status}
                />
                <OrderTimeExtensionControls
                    extension={order.timeExtension}
                    isLoading={extensionLoading}
                    isSeller={isSeller}
                    onDecide={onDecideExtension}
                    onOpen={onOpenTimeExtension}
                    onRequest={onRequestExtension}
                />
                <OrderDeliveryControls
                    deliveryFlow={order.deliveryFlow}
                    isSeller={isSeller}
                    onOpen={onOpenDelivery}
                />
                <button
                    className="order-conversation-button"
                    type="button"
                    onClick={onOpenConversation}
                >
                    <Icon name="message" />{" "}
                    {t("pages.orderdetailspage.viewConversation")}{" "}
                </button>
                {conversationStatus ? (
                    <p className="profile-message-status">
                        {conversationStatus}
                    </p>
                ) : null}
                <dl className="order-side-meta">
                    <div>
                        <dt>{t("pages.orderdetailspage.orderedBy")}</dt>
                        <dd>{order.orderedBy}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.deliveryDate")}</dt>
                        <dd>{order.deliveryDate}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.totalPrice")}</dt>
                        <dd>{order.totalPrice}</dd>
                    </div>
                    <div>
                        <dt>{t("pages.orderdetailspage.orderNumber")}</dt>
                        <dd>{order.orderNumber}</dd>
                    </div>
                </dl>
                <div className="order-track-list">
                    <h3>
                        {" "}
                        {t("pages.orderdetailspage.trackOrder")}{" "}
                        <Icon name="chevronDown" />
                    </h3>
                    <span>
                        <Icon name="packageCheck" />{" "}
                        {t("pages.orderdetailspage.deliveryReviewed")}
                    </span>
                    <span>
                        <Icon name="packageCheck" />{" "}
                        {t("pages.orderdetailspage.orderCompleted")}
                    </span>
                </div>
            </section>

            <section className="order-side-card order-note-card">
                <div>
                    <h2>{t("pages.orderdetailspage.privateNote")}</h2>
                    <span>
                        {order.privateNotes?.length
                            ? `${order.privateNotes.length} saved note${order.privateNotes.length === 1 ? "" : "s"}`
                            : t("pages.orderdetailspage.onlyVisibleToYou")}
                    </span>
                </div>
                <button type="button" onClick={onOpenPrivateNotes}>
                    <Icon name="plus" />{" "}
                    {t("pages.orderdetailspage.addNote")}{" "}
                </button>
            </section>

            <section className="order-side-card">
                <h2>{t("pages.orderdetailspage.support")}</h2>
                <div className="order-support-list">
                    {orderSupportLinks.map((link) => {
                        const isResolution = link.title
                            .toLowerCase()
                            .includes("resolution");

                        return (
                            <button
                                type="button"
                                key={link.title}
                                onClick={
                                    isResolution ? onOpenResolution : onOpenHelp
                                }
                            >
                                <Icon name={link.icon} />
                                <span>
                                    <strong>{link.title}</strong>
                                    <small>{link.description}</small>
                                </span>
                                <Icon name="arrowRight" />
                            </button>
                        );
                    })}
                </div>
            </section>
        </aside>
    );
}

function CounterpartyAvatar({ avatar, initials, name }) {
    return avatar ? (
        <img
            className="order-person-avatar"
            src={avatar}
            alt={`${name || "Member"} profile`}
            loading="lazy"
            decoding="async"
        />
    ) : (
        <span className="avatar">{initials}</span>
    );
}

function OrderCountdown({ deliveryDate, dueAt, status }) {
    const remaining = useCountdown(dueAt);
    const isComplete = ["Completed", "Cancelled", "Canceled"].includes(status);
    const label = isComplete
        ? "Order finished"
        : remaining.isPast
            ? "Delivery time passed"
            : "Time left";

    return (
        <section className="order-countdown-card" aria-live="polite">
            <div>
                <Icon name="clock" />
                <span>Delivery Deadline</span>
            </div>
            {isComplete ? (
                <strong>Completed</strong>
            ) : (
                <div className="order-countdown-grid">
                    {[
                        ["D", remaining.days],
                        ["H", remaining.hours],
                        ["M", remaining.minutes],
                        ["S", remaining.seconds],
                    ].map(([unit, value]) => (
                        <span key={unit}>
                            <strong>{String(value).padStart(2, "0")}</strong>
                            <small>{unit}</small>
                        </span>
                    ))}
                </div>
            )}
            <em className={remaining.isPast ? "is-overdue" : ""}>{label}</em>
            <small>Delivery date: {deliveryDate || "Not scheduled"}</small>
        </section>
    );
}

function OrderTimeExtensionControls({
    extension,
    isSeller,
    onOpen,
}) {
    const pending = extension?.pending;
    const latest = extension?.latest;
    const canRequest = Boolean(extension?.canRequest);
    const canDecide = Boolean(extension?.canDecide);

    if (!extension) {
        return null;
    }

    const canOpen = typeof onOpen === "function";
    const actionLabel = pending
        ? isSeller
            ? "View time extension request"
            : "Review time extension request"
        : isSeller && canRequest
            ? "Request time extension"
            : "View time extension";

    const openCard = () => {
        if (canOpen) {
            onOpen();
        }
    };

    const handleKeyDown = (event) => {
        if (!canOpen) return;

        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            onOpen();
        }
    };

    return (
        <section
            aria-label={actionLabel}
            className={`order-extension-card${canOpen ? " order-extension-card-action" : ""}`}
            onClick={openCard}
            onKeyDown={handleKeyDown}
            role={canOpen ? "button" : undefined}
            tabIndex={canOpen ? 0 : undefined}
        >
            <div>
                <strong>Time extension</strong>
                <span>
                    {pending
                        ? `${pending.days} day request pending`
                        : latest
                            ? `Latest request ${latest.statusLabel}`
                            : "No request yet"}
                </span>
            </div>
            {pending ? (
                <article className="order-extension-summary">
                    <span>Requested delivery: {pending.requestedDueDate}</span>
                    <p>{pending.reason}</p>
                </article>
            ) : null}
            {isSeller && pending ? (
                <p className="order-extension-note">
                    Waiting for the buyer to review this request.
                </p>
            ) : null}
            <span className="order-extension-card-cta">{actionLabel}</span>
        </section>
    );
}

function OrderDeliveryControls({ deliveryFlow = {}, isSeller, onOpen }) {
    const latest = deliveryFlow.latest;
    const canAct = isSeller
        ? deliveryFlow.canSubmitDelivery
        : deliveryFlow.canRequestRevision || deliveryFlow.canComplete;

    if (!deliveryFlow) {
        return null;
    }

    return (
        <section className="order-extension-card order-delivery-card">
            <div>
                <strong>Delivery</strong>
                <span>
                    {latest
                        ? `Latest delivery ${latest.statusLabel}`
                        : "No delivery submitted yet"}
                </span>
            </div>
            {latest ? (
                <article className="order-extension-summary">
                    <span>{latest.submittedAt}</span>
                    <p>{latest.message}</p>
                </article>
            ) : null}
            {canAct ? (
                <button
                    className="order-extension-button"
                    type="button"
                    onClick={onOpen}
                >
                    {isSeller ? "Submit Delivery" : "Review delivery"}
                </button>
            ) : null}
        </section>
    );
}

function TimeExtensionWorkflow({
    extension,
    isLoading,
    isSeller,
    onDecide,
    onRequest,
}) {
    const [draft, setDraft] = useState({
        days: "2",
        reason: "",
    });
    const pending = extension?.pending;
    const latest = extension?.latest;
    const canRequest = Boolean(extension?.canRequest);
    const canDecide = Boolean(extension?.canDecide);

    const submitRequest = async (event) => {
        event.preventDefault();
        const saved = await onRequest({
            days: Number(draft.days),
            reason: draft.reason,
        });

        if (saved) {
            setDraft({ days: "2", reason: "" });
        }
    };

    if (!extension) {
        return <p className="messages-empty">Time extension is unavailable.</p>;
    }

    return (
        <div className="order-extension-workflow">
            {pending ? (
                <article className="order-extension-summary">
                    <strong>{pending.days} extra day request pending</strong>
                    <span>Requested delivery: {pending.requestedDueDate}</span>
                    <p>{pending.reason}</p>
                </article>
            ) : latest ? (
                <article className="order-extension-summary">
                    <strong>Latest request {latest.statusLabel}</strong>
                    <span>Requested delivery: {latest.requestedDueDate}</span>
                    <p>{latest.reason}</p>
                </article>
            ) : (
                <p className="messages-empty">No time extension requests yet.</p>
            )}
            {isSeller && canRequest ? (
                <form className="order-extension-form" onSubmit={submitRequest}>
                    <label>
                        <span>Extra days</span>
                        <input
                            min="1"
                            max="30"
                            type="number"
                            value={draft.days}
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    days: event.target.value,
                                }))
                            }
                            required
                        />
                    </label>
                    <label>
                        <span>Reason</span>
                        <textarea
                            rows="4"
                            value={draft.reason}
                            placeholder="Explain why the delivery needs more time."
                            onChange={(event) =>
                                setDraft((current) => ({
                                    ...current,
                                    reason: event.target.value,
                                }))
                            }
                            required
                        />
                    </label>
                    <button type="submit" disabled={isLoading === "request"}>
                        {isLoading === "request"
                            ? "Sending..."
                            : "Send request"}
                    </button>
                </form>
            ) : null}
            {!isSeller && canDecide && pending ? (
                <div className="order-extension-actions">
                    <button
                        type="button"
                        disabled={Boolean(isLoading)}
                        onClick={() => onDecide(pending.id, "reject")}
                    >
                        {isLoading === "reject" ? "Rejecting..." : "Reject"}
                    </button>
                    <button
                        type="button"
                        disabled={Boolean(isLoading)}
                        onClick={() => onDecide(pending.id, "accept")}
                    >
                        {isLoading === "accept" ? "Accepting..." : "Accept"}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

function useCountdown(dueAt) {
    const targetTime = useMemo(() => {
        if (!dueAt) {
            return null;
        }

        const timestamp = new Date(dueAt).getTime();

        return Number.isNaN(timestamp) ? null : timestamp;
    }, [dueAt]);
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const timerId = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timerId);
    }, []);

    if (!targetTime) {
        return {
            isPast: false,
            label: "No due date",
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0,
        };
    }

    const diff = targetTime - now;
    const absoluteDiff = Math.abs(diff);
    const days = Math.floor(absoluteDiff / 86_400_000);
    const hours = Math.floor((absoluteDiff % 86_400_000) / 3_600_000);
    const minutes = Math.floor((absoluteDiff % 3_600_000) / 60_000);
    const seconds = Math.floor((absoluteDiff % 60_000) / 1000);

    return {
        isPast: diff <= 0,
        label:
            days > 0
                ? `${days}d ${hours}h ${minutes}m`
                : `${hours}h ${minutes}m ${seconds}s`,
        days,
        hours,
        minutes,
        seconds,
    };
}

function PrivateNotesModal({
    isLoading,
    isOpen,
    notes,
    onClose,
    onDelete,
    onSave,
}) {
    const [draft, setDraft] = useState("");
    const [editingId, setEditingId] = useState(null);
    const [error, setError] = useState("");
    const editingNote = notes.find((note) => note.id === editingId);

    useEffect(() => {
        if (!isOpen) {
            setDraft("");
            setEditingId(null);
            setError("");
        }
    }, [isOpen]);

    const startEditing = (note) => {
        setEditingId(note.id);
        setDraft(note.body || "");
        setError("");
    };
    const cancelEditing = () => {
        setEditingId(null);
        setDraft("");
        setError("");
    };
    const submitNote = async (event) => {
        event.preventDefault();

        if (draft.trim().length < 2) {
            setError("Write at least 2 characters for your private note.");
            return;
        }

        setError("");

        const saved = await onSave(draft.trim(), editingId);

        if (saved) {
            setDraft("");
            setEditingId(null);
        }
    };

    if (!isOpen) {
        return null;
    }

    return (
        <div
            className="order-note-modal-backdrop"
            role="presentation"
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <section
                className="order-note-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="orderPrivateNotesTitle"
            >
                <header>
                    <div>
                        <span>Only visible to you</span>
                        <h2 id="orderPrivateNotesTitle">Private notes</h2>
                    </div>
                    <button
                        type="button"
                        aria-label="Close private notes"
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </header>
                <form className="order-note-form" onSubmit={submitNote}>
                    <label>
                        <span>
                            {editingNote ? "Edit note" : "Add a private note"}
                        </span>
                        <textarea
                            rows="4"
                            value={draft}
                            placeholder="Save context, reminders, or follow-up points for yourself."
                            onChange={(event) => setDraft(event.target.value)}
                        />
                    </label>
                    {error ? (
                        <p className="order-note-error" role="alert">
                            {error}
                        </p>
                    ) : null}
                    <div>
                        {editingNote ? (
                            <button type="button" onClick={cancelEditing}>
                                Cancel edit
                            </button>
                        ) : null}
                        <button
                            type="submit"
                            disabled={
                                isLoading === "create" ||
                                isLoading === `update-${editingId}`
                            }
                        >
                            {isLoading === "create" ||
                            isLoading === `update-${editingId}`
                                ? "Saving..."
                                : editingNote
                                    ? "Save changes"
                                    : "Add note"}
                        </button>
                    </div>
                </form>
                <div className="order-note-list">
                    {notes.length ? (
                        notes.map((note) => (
                            <article key={note.id}>
                                <p>{note.body}</p>
                                <span>
                                    Updated {note.updatedAt || note.createdAt}
                                </span>
                                <div>
                                    <button
                                        type="button"
                                        onClick={() => startEditing(note)}
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        disabled={
                                            isLoading === `delete-${note.id}`
                                        }
                                        onClick={() => onDelete(note.id)}
                                    >
                                        {isLoading === `delete-${note.id}`
                                            ? "Deleting..."
                                            : "Delete"}
                                    </button>
                                </div>
                            </article>
                        ))
                    ) : (
                        <p className="order-note-empty">
                            No private notes yet.
                        </p>
                    )}
                </div>
            </section>
        </div>
    );
}
export default OrderDetailsPage;
