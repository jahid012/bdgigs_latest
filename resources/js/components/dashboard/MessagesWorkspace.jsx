import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";
import LoadingSkeleton from "../common/LoadingSkeleton.jsx";
import { useToast } from "../common/ToastProvider.jsx";
import { useTranslation } from "react-i18next";
import { useDashboardStore } from "../../stores/useDashboardStore.js";
const inboxFilters = [
    { id: "all", label: "All messages" },
    { id: "buying", label: "Buying" },
    { id: "selling", label: "Selling" },
    { id: "order", label: "Order threads" },
    { id: "archived", label: "Archived" },
];

function MessagesWorkspace({ variant = "buyer" }) {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const notify = useToast();
    const [searchParams] = useSearchParams();
    const isSeller = variant === "seller";
    const threads = useDashboardStore((state) =>
        isSeller ? state.sellerMessageThreads : state.buyerMessageThreads,
    );
    const fetchConversations = useDashboardStore(
        (state) => state.fetchConversations,
    );
    const isConversationsLoading = useDashboardStore(
        (state) => state.isConversationsLoading,
    );
    const fetchConversation = useDashboardStore(
        (state) => state.fetchConversation,
    );
    const markConversationRead = useDashboardStore(
        (state) => state.markConversationRead,
    );
    const sendMessage = useDashboardStore((state) => state.sendMessage);
    const sendTyping = useDashboardStore((state) => state.sendTyping);
    const fetchSavedMessages = useDashboardStore(
        (state) => state.fetchSavedMessages,
    );
    const saveMessage = useDashboardStore((state) => state.saveMessage);
    const unsaveMessage = useDashboardStore((state) => state.unsaveMessage);
    const fetchCustomOfferOptions = useDashboardStore(
        (state) => state.fetchCustomOfferOptions,
    );
    const createCustomOffer = useDashboardStore(
        (state) => state.createCustomOffer,
    );
    const acceptCustomOffer = useDashboardStore(
        (state) => state.acceptCustomOffer,
    );
    const payCustomOffer = useDashboardStore((state) => state.payCustomOffer);
    const declineCustomOffer = useDashboardStore(
        (state) => state.declineCustomOffer,
    );
    const cancelCustomOffer = useDashboardStore(
        (state) => state.cancelCustomOffer,
    );
    const [activeThreadIds, setActiveThreadIds] = useState({});
    const [conversationFilter, setConversationFilter] = useState("all");
    const [isInboxFilterOpen, setIsInboxFilterOpen] = useState(false);
    const [isInboxSearchOpen, setIsInboxSearchOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    const [conversationMenuOpen, setConversationMenuOpen] = useState(false);
    const [openMessageMenu, setOpenMessageMenu] = useState(null);
    const [activeConversationView, setActiveConversationView] =
        useState("messages");
    const [savedMessages, setSavedMessages] = useState([]);
    const [isCustomOfferModalOpen, setIsCustomOfferModalOpen] =
        useState(false);
    const [customOfferOptions, setCustomOfferOptions] = useState([]);
    const [customOfferLoading, setCustomOfferLoading] = useState("");
    const [draft, setDraft] = useState("");
    const [attachments, setAttachments] = useState([]);
    const searchInputRef = useRef(null);
    const attachmentInputRef = useRef(null);
    const textareaRef = useRef(null);
    const lastTypingSentAtRef = useRef(0);
    const workspaceRef = useRef(null);
    const requestedConversationId = searchParams.get("conversation");
    const activeThreadId = activeThreadIds[variant] || requestedConversationId;
    const activeFilter =
        inboxFilters.find((filter) => filter.id === conversationFilter) ||
        inboxFilters[0];
    const closeMenus = useCallback(() => {
        setIsInboxFilterOpen(false);
        setConversationMenuOpen(false);
        setOpenMessageMenu(null);
    }, []);
    useDismissOnInteractOutside(
        workspaceRef,
        isInboxFilterOpen || conversationMenuOpen || openMessageMenu !== null,
        closeMenus,
    );
    useEffect(() => {
        if (isInboxSearchOpen) {
            searchInputRef.current?.focus();
        }
    }, [isInboxSearchOpen]);

    useEffect(() => {
        fetchConversations(conversationFilter);
    }, [conversationFilter, fetchConversations]);

    useEffect(() => {
        if (
            !requestedConversationId ||
            threads.some((thread) => thread.id === requestedConversationId)
        ) {
            return;
        }

        fetchConversation(requestedConversationId).catch(() => {});
    }, [fetchConversation, requestedConversationId, threads]);

    useEffect(() => {
        if (!textareaRef.current) return;
        textareaRef.current.style.height = "auto";
        textareaRef.current.style.height = `${Math.min(textareaRef.current.scrollHeight, 180)}px`;
    }, [draft, activeThreadId]);
    const activeThread = useMemo(
        () => threads.find((thread) => thread.id === activeThreadId) || null,
        [activeThreadId, threads],
    );
    const displayThread = activeThread;

    useEffect(() => {
        if (!activeThread?.id) {
            return;
        }

        markConversationRead(activeThread.id).catch(() => {});
    }, [activeThread?.id, markConversationRead]);
    useEffect(() => {
        if (activeConversationView !== "saved" || !activeThread?.id) {
            setSavedMessages([]);
            return;
        }

        fetchSavedMessages(activeThread.id)
            .then(setSavedMessages)
            .catch(() => setSavedMessages([]));
    }, [activeConversationView, activeThread?.id, fetchSavedMessages]);
    const filteredThreads = useMemo(() => {
        const query = searchTerm.trim().toLowerCase();
        if (!query) return threads;
        return threads.filter((thread) => {
            const searchable = [
                thread.name,
                thread.service,
                thread.status,
                thread.priority,
                thread.preview,
            ]
                .join(" ")
                .toLowerCase();
            return searchable.includes(query);
        });
    }, [searchTerm, threads]);
    const activeMessages = useMemo(() => {
        if (activeConversationView === "saved") {
            return savedMessages;
        }

        return activeThread?.messages ?? [];
    }, [activeConversationView, activeThread, savedMessages]);
    const handleSendMessage = async () => {
        const text = draft.trim();
        if ((!text && attachments.length === 0) || !activeThread?.id) return;
        setDraft("");
        const files = attachments;
        setAttachments([]);

        try {
            await sendMessage(activeThread.id, text, files);
        } catch {
            setDraft(text);
            setAttachments(files);
        }
    };
    const openCustomOfferModal = async () => {
        if (!activeThread?.id) return;

        setIsCustomOfferModalOpen(true);
        setCustomOfferLoading("options");

        try {
            setCustomOfferOptions(await fetchCustomOfferOptions(activeThread.id));
        } catch (error) {
            notify.error(error.message || "Custom offer options are unavailable.");
            setCustomOfferOptions([]);
        } finally {
            setCustomOfferLoading("");
        }
    };
    const handleCreateCustomOffer = async (payload) => {
        if (!activeThread?.id) return false;

        setCustomOfferLoading("create");

        try {
            await createCustomOffer(activeThread.id, payload);
            notify.success("Custom offer sent.");
            setIsCustomOfferModalOpen(false);
            return true;
        } catch (error) {
            notify.error(error.message || "Custom offer could not be sent.");
            return false;
        } finally {
            setCustomOfferLoading("");
        }
    };
    const handleCustomOfferAction = async (offer, action) => {
        if (!offer?.id) return;

        setCustomOfferLoading(`${action}-${offer.id}`);

        try {
            let response = null;

            if (action === "accept") {
                response = await acceptCustomOffer(offer.id);
                notify.success("Custom offer accepted.");
            } else if (action === "pay") {
                response = await payCustomOffer(offer.id);
                notify.success("Custom offer paid. Order created.");
                const path = response?.order?.path || response?.offer?.orderPath;

                if (path) {
                    navigate(path);
                }
            } else if (action === "decline") {
                response = await declineCustomOffer(offer.id);
                notify.success("Custom offer declined.");
            } else if (action === "cancel") {
                response = await cancelCustomOffer(offer.id);
                notify.success("Custom offer cancelled.");
            }

            return response;
        } catch (error) {
            notify.error(error.message || "Custom offer action failed.");
            if (
                action === "pay" &&
                String(error.message || "")
                    .toLowerCase()
                    .includes("balance")
            ) {
                navigate("/dashboard/payments");
            }
            return null;
        } finally {
            setCustomOfferLoading("");
        }
    };
    const handleComposerKeyDown = (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            handleSendMessage();
        }
    };
    const selectAttachments = (event) => {
        const files = Array.from(event.target.files || []);
        setAttachments(files.slice(0, 5));
        event.target.value = "";
    };
    return (
        <main
            className="dashboard-content messages-page"
            ref={workspaceRef}
            onClick={closeMenus}
        >
            <section
                className="messages-shell"
                aria-label={t(
                    "components.dashboard.messagesworkspace.dashboardMessages",
                )}
            >
                <aside
                    className="messages-thread-list"
                    aria-label={t(
                        "components.dashboard.messagesworkspace.conversationList",
                    )}
                >
                    <div className="messages-inbox-toolbar">
                        <div className="conversation-menu-wrap">
                            <button
                                className="inbox-title-button"
                                type="button"
                                aria-expanded={isInboxFilterOpen}
                                aria-label={t(
                                    "components.dashboard.messagesworkspace.filterAllMessages",
                                )}
                                onClick={(event) => {
                                    event.stopPropagation();
                                    setIsInboxFilterOpen((isOpen) => !isOpen);
                                }}
                            >
                                {activeFilter.label} <Icon name="chevronDown" />
                            </button>
                            <div
                                className={`message-action-menu conversation-more-menu${isInboxFilterOpen ? " is-open" : ""}`}
                                role="menu"
                            >
                                {inboxFilters.map((filter) => (
                                    <button
                                        type="button"
                                        role="menuitem"
                                        key={filter.id}
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            setConversationFilter(filter.id);
                                            setIsInboxFilterOpen(false);
                                        }}
                                    >
                                        {filter.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <button
                            className={`inbox-search-toggle${isInboxSearchOpen ? " active" : ""}`}
                            type="button"
                            aria-expanded={isInboxSearchOpen}
                            aria-label={t(
                                "components.dashboard.messagesworkspace.searchConversations",
                            )}
                            onClick={(event) => {
                                event.stopPropagation();
                                setIsInboxSearchOpen((isOpen) => !isOpen);
                            }}
                        >
                            <Icon name="search" />
                        </button>
                    </div>

                    <form
                        className={`messages-search messages-search-drawer${isInboxSearchOpen ? " is-open" : ""}`}
                        role="search"
                        aria-label={t(
                            "components.dashboard.messagesworkspace.searchMessages",
                        )}
                        onClick={(event) => event.stopPropagation()}
                        onSubmit={(event) => event.preventDefault()}
                    >
                        <Icon name="search" />
                        <label className="sr-only" htmlFor="messagesSearch">
                            {" "}
                            {t(
                                "components.dashboard.messagesworkspace.searchMessages",
                            )}{" "}
                        </label>
                        <input
                            ref={searchInputRef}
                            id="messagesSearch"
                            type="search"
                            value={searchTerm}
                            placeholder={t(
                                "components.dashboard.messagesworkspace.searchConversations2",
                            )}
                            autoComplete="off"
                            onChange={(event) =>
                                setSearchTerm(event.target.value)
                            }
                        />
                        <button
                            className="messages-search-close"
                            type="button"
                            onClick={() => {
                                setIsInboxSearchOpen(false);
                                setSearchTerm("");
                            }}
                        >
                            {" "}
                            {t(
                                "components.dashboard.messagesworkspace.close",
                            )}{" "}
                        </button>
                    </form>

                    <div className="message-thread-items">
                        {isConversationsLoading && threads.length === 0 ? (
                            <ConversationListSkeleton />
                        ) : filteredThreads.length > 0 ? (
                            filteredThreads.map((thread) => (
                                <button
                                    className={`message-thread${thread.id === displayThread?.id ? " active" : ""}`}
                                    type="button"
                                    key={thread.id}
                                    onClick={() =>
                                        setActiveThreadIds((current) => ({
                                            ...current,
                                            [variant]: thread.id,
                                        }))
                                    }
                                >
                                    <ConversationAvatar thread={thread} />
                                    <span className="message-thread-body">
                                        <span className="message-thread-top">
                                            <strong>{thread.name}</strong>
                                            <small>{thread.time}</small>
                                        </span>
                                        <span className="message-thread-preview">
                                            {thread.preview}
                                        </span>
                                    </span>
                                    <span
                                        className="message-thread-favorite"
                                        aria-hidden="true"
                                    >
                                        <Icon name="star" />
                                    </span>
                                </button>
                            ))
                        ) : (
                            <p className="messages-empty">
                                {t(
                                    "components.dashboard.messagesworkspace.noConversationsFound",
                                )}
                            </p>
                        )}
                    </div>
                </aside>

                <article
                    className="conversation-panel"
                    aria-labelledby="activeConversationTitle"
                >
                    {displayThread ? (
                        <>
                            <header className="conversation-header">
                                <div className="conversation-person">
                                    <ConversationAvatar thread={displayThread} />
                                    <div>
                                        <h1 id="activeConversationTitle">
                                            {displayThread.name}{" "}
                                            <span>
                                                @
                                                {displayThread.name
                                                    .toLowerCase()
                                                    .replace(/[^a-z0-9]/g, "")}
                                            </span>
                                        </h1>
                                        <p>
                                            {t(
                                                "components.dashboard.messagesworkspace.lastSeen",
                                            )}{" "}
                                            {displayThread.time}{" "}
                                            {t(
                                                "components.dashboard.messagesworkspace.localTime429Am",
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div className="conversation-header-tools">
                                    {isSeller ? (
                                        <button
                                            className="custom-offer-trigger"
                                            type="button"
                                            onClick={openCustomOfferModal}
                                        >
                                            <Icon name="packageCheck" />
                                            Custom offer
                                        </button>
                                    ) : null}
                                    <button
                                        className="icon-button ghost"
                                        type="button"
                                        aria-label={t(
                                            "components.dashboard.messagesworkspace.tagConversation",
                                        )}
                                    >
                                        <Icon name="tag" />
                                    </button>
                                    <button
                                        className="icon-button ghost"
                                        type="button"
                                        aria-label={t(
                                            "components.dashboard.messagesworkspace.saveConversation",
                                        )}
                                    >
                                        <Icon name="star" />
                                    </button>
                                    <div className="conversation-menu-wrap">
                                        <button
                                            className="icon-button ghost"
                                            type="button"
                                            aria-label={t(
                                                "components.dashboard.messagesworkspace.moreConversationActions",
                                            )}
                                            aria-expanded={conversationMenuOpen}
                                            onClick={(event) => {
                                                event.stopPropagation();
                                                setConversationMenuOpen(
                                                    (isOpen) => !isOpen,
                                                );
                                                setOpenMessageMenu(null);
                                            }}
                                        >
                                            <Icon name="moreHorizontal" />
                                        </button>
                                        <div
                                            className={`message-action-menu conversation-more-menu${conversationMenuOpen ? " is-open" : ""}`}
                                            role="menu"
                                        >
                                            <button
                                                type="button"
                                                role="menuitem"
                                            >
                                                <Icon name="message" />{" "}
                                                {t(
                                                    "components.dashboard.messagesworkspace.markAsUnread",
                                                )}{" "}
                                            </button>
                                            <button
                                                type="button"
                                                role="menuitem"
                                            >
                                                <Icon name="archive" />{" "}
                                                {t(
                                                    "components.dashboard.messagesworkspace.moveToArchive",
                                                )}{" "}
                                            </button>
                                            <button
                                                className="danger"
                                                type="button"
                                                role="menuitem"
                                            >
                                                <Icon name="trash" />{" "}
                                                {t(
                                                    "components.dashboard.messagesworkspace.delete",
                                                )}{" "}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </header>

                            <div
                                className="conversation-tabs"
                                aria-label={t(
                                    "components.dashboard.messagesworkspace.conversationViews",
                                )}
                            >
                                <button
                                    className={
                                        activeConversationView === "messages"
                                            ? "active"
                                            : ""
                                    }
                                    type="button"
                                    aria-pressed={
                                        activeConversationView === "messages"
                                    }
                                    onClick={() =>
                                        setActiveConversationView("messages")
                                    }
                                >
                                    {" "}
                                    {t(
                                        "components.dashboard.messagesworkspace.messages",
                                    )}{" "}
                                </button>
                                <button
                                    className={
                                        activeConversationView === "saved"
                                            ? "active"
                                            : ""
                                    }
                                    type="button"
                                    aria-pressed={
                                        activeConversationView === "saved"
                                    }
                                    onClick={() =>
                                        setActiveConversationView("saved")
                                    }
                                >
                                    {t(
                                        "components.dashboard.messagesworkspace.saved",
                                    )}
                                </button>
                            </div>

                            <div
                                className="conversation-messages"
                                aria-label={`Conversation with ${displayThread.name}`}
                            >
                                <div className="conversation-date">
                                    {activeConversationView === "saved"
                                        ? "Saved"
                                        : t(
                                              "components.dashboard.messagesworkspace.today",
                                          )}
                                </div>
                                {activeMessages.length === 0 ? (
                                    <p className="messages-empty">
                                        {activeConversationView === "saved"
                                            ? "Saved messages from this thread will appear here."
                                            : "Send the first message to begin."}
                                    </p>
                                ) : null}
                                {activeMessages.map((message, index) => {
                                    const messageKey = `${displayThread.id || "empty"}-${message.from}-${message.time}-${index}`;
                                    return (
                                        <article
                                            className={`conversation-bubble${message.own ? " own" : ""}`}
                                            key={messageKey}
                                        >
                                            <div className="conversation-bubble-top">
                                                <strong>{message.from}</strong>
                                                <time>{message.time}</time>
                                                <div className="message-menu-wrap">
                                                    <button
                                                        className="message-more-button"
                                                        type="button"
                                                        aria-label={t(
                                                            "components.dashboard.messagesworkspace.messageActions",
                                                        )}
                                                        aria-expanded={
                                                            openMessageMenu ===
                                                            messageKey
                                                        }
                                                        onClick={(event) => {
                                                            event.stopPropagation();
                                                            setOpenMessageMenu(
                                                                (current) =>
                                                                    current ===
                                                                    messageKey
                                                                        ? null
                                                                        : messageKey,
                                                            );
                                                            setConversationMenuOpen(
                                                                false,
                                                            );
                                                        }}
                                                    >
                                                        <Icon name="moreHorizontal" />
                                                    </button>
                                                    <div
                                                        className={`message-action-menu bubble-action-menu${openMessageMenu === messageKey ? " is-open" : ""}`}
                                                        role="menu"
                                                    >
                                                        <button
                                                            type="button"
                                                            role="menuitem"
                                                        >
                                                            <Icon name="reply" />{" "}
                                                            {t(
                                                                "components.dashboard.messagesworkspace.reply",
                                                            )}{" "}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            role="menuitem"
                                                            onClick={async () => {
                                                                if (
                                                                    message.saved
                                                                ) {
                                                                    await unsaveMessage(
                                                                        message.id,
                                                                    );
                                                                    setSavedMessages(
                                                                        (
                                                                            current,
                                                                        ) =>
                                                                            current.filter(
                                                                                (
                                                                                    item,
                                                                                ) =>
                                                                                    item.id !==
                                                                                    message.id,
                                                                            ),
                                                                    );
                                                                } else {
                                                                    const saved =
                                                                        await saveMessage(
                                                                            message.id,
                                                                        );
                                                                    setSavedMessages(
                                                                        (
                                                                            current,
                                                                        ) =>
                                                                            current.some(
                                                                                (
                                                                                    item,
                                                                                ) =>
                                                                                    item.id ===
                                                                                    saved.id,
                                                                            )
                                                                                ? current
                                                                                : [
                                                                                      ...current,
                                                                                      saved,
                                                                                  ],
                                                                    );
                                                                }
                                                                setOpenMessageMenu(
                                                                    null,
                                                                );
                                                            }}
                                                        >
                                                            <Icon name="star" />{" "}
                                                            {message.saved
                                                                ? "Remove from saved"
                                                                : "Save message"}{" "}
                                                        </button>
                                                        <button
                                                            className="danger"
                                                            type="button"
                                                            role="menuitem"
                                                        >
                                                            <Icon name="flag" />{" "}
                                                            {t(
                                                                "components.dashboard.messagesworkspace.report",
                                                            )}{" "}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            {message.text ? (
                                                <p>{message.text}</p>
                                            ) : null}
                                            {message.customOffer ? (
                                                <CustomOfferMessageCard
                                                    isLoading={customOfferLoading}
                                                    offer={message.customOffer}
                                                    onAction={
                                                        handleCustomOfferAction
                                                    }
                                                />
                                            ) : null}
                                            {message.attachments?.length ? (
                                                <div className="message-attachments">
                                                    {message.attachments.map(
                                                        (attachment) => (
                                                            <a
                                                                href={
                                                                    attachment.url ||
                                                                    "#files"
                                                                }
                                                                key={
                                                                    attachment.id ||
                                                                    attachment.name
                                                                }
                                                                target="_blank"
                                                                rel="noreferrer"
                                                            >
                                                                <Icon name="paperclip" />
                                                                {attachment.name ||
                                                                    "Attachment"}
                                                            </a>
                                                        ),
                                                    )}
                                                </div>
                                            ) : null}
                                        </article>
                                    );
                                })}
                            </div>

                            {activeConversationView === "messages" ? (
                                <form
                                    className="conversation-composer"
                                    onClick={(event) => event.stopPropagation()}
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        handleSendMessage();
                                    }}
                                >
                                    <label
                                        className="sr-only"
                                        htmlFor="messageReply"
                                    >
                                        {" "}
                                        {t(
                                            "components.dashboard.messagesworkspace.replyToConversation",
                                        )}{" "}
                                    </label>
                                    <textarea
                                        ref={textareaRef}
                                        id="messageReply"
                                        value={draft}
                                        rows="3"
                                        maxLength="2000"
                                        placeholder={t(
                                            "components.dashboard.messagesworkspace.writeAMessage",
                                        )}
                                        disabled={!activeThread}
                                        onChange={(event) => {
                                            setDraft(event.target.value);

                                            const now = Date.now();

                                            if (
                                                activeThread?.id &&
                                                now -
                                                    lastTypingSentAtRef.current >
                                                    2000
                                            ) {
                                                lastTypingSentAtRef.current =
                                                    now;
                                                sendTyping(activeThread.id);
                                            }
                                        }}
                                        onKeyDown={handleComposerKeyDown}
                                    />
                                    <div className="composer-footer">
                                        <div
                                            className="composer-tools"
                                            aria-label={t(
                                                "components.dashboard.messagesworkspace.messageTools",
                                            )}
                                        >
                                            <button
                                                type="button"
                                                aria-label={t(
                                                    "components.dashboard.messagesworkspace.attachFile",
                                                )}
                                                onClick={() =>
                                                    attachmentInputRef.current?.click()
                                                }
                                            >
                                                <Icon name="paperclip" />
                                            </button>
                                            <input
                                                ref={attachmentInputRef}
                                                className="sr-only"
                                                type="file"
                                                multiple
                                                onChange={selectAttachments}
                                            />
                                            <button
                                                type="button"
                                                aria-label={t(
                                                    "components.dashboard.messagesworkspace.addEmoji",
                                                )}
                                            >
                                                <Icon name="smile" />
                                            </button>
                                            <span>
                                                {t(
                                                    "components.dashboard.messagesworkspace.shiftEnterForNewLine",
                                                )}
                                            </span>
                                        </div>
                                        <button
                                            className="composer-send"
                                            type="submit"
                                            aria-label={t(
                                                "components.dashboard.messagesworkspace.sendMessage",
                                            )}
                                            disabled={
                                                (!draft.trim() &&
                                                    attachments.length === 0) ||
                                                !activeThread
                                            }
                                        >
                                            <Icon name="send" />
                                        </button>
                                    </div>
                                    {attachments.length ? (
                                        <div className="composer-attachments">
                                            {attachments.map((file) => (
                                                <span key={`${file.name}-${file.size}`}>
                                                    <Icon name="paperclip" />
                                                    {file.name}
                                                </span>
                                            ))}
                                            <button
                                                type="button"
                                                onClick={() => setAttachments([])}
                                            >
                                                Clear
                                            </button>
                                        </div>
                                    ) : null}
                                </form>
                            ) : null}
                        </>
                    ) : isConversationsLoading ? (
                        <ConversationPanelSkeleton />
                    ) : (
                        <NoConversationSelected />
                    )}
                </article>

                <aside
                    className="conversation-details-panel"
                    aria-label={t(
                        "components.dashboard.messagesworkspace.conversationDetails",
                    )}
                >
                    {displayThread ? (
                        <>
                            <section className="details-card">
                                <div className="details-heading">
                                    <h2>
                                        {t(
                                            "components.dashboard.messagesworkspace.ordersWithYou",
                                        )}
                                    </h2>
                                    <a href="#orders">
                                        {t(
                                            "components.dashboard.messagesworkspace.total",
                                        )}
                                        {displayThread.context?.order
                                            ? "1"
                                            : "0"}
                                        )
                                    </a>
                                </div>
                                {displayThread.context?.order ? (
                                    <div className="details-order">
                                        <span
                                            className={`status-badge ${displayThread.context.order.statusClass}`}
                                        >
                                            {displayThread.context.order.status}
                                        </span>
                                        <strong>
                                            {
                                                displayThread.context.order
                                                    .service
                                            }
                                        </strong>
                                        <small>
                                            {displayThread.context.order
                                                .dueDate || "No delivery date"}
                                        </small>
                                    </div>
                                ) : (
                                    <p className="messages-empty">
                                        No order is attached to this
                                        conversation.
                                    </p>
                                )}
                            </section>

                            <section className="details-card">
                                <h2>
                                    {t(
                                        "components.dashboard.messagesworkspace.about",
                                    )}{" "}
                                    {displayThread.name}
                                </h2>
                                <dl className="details-list">
                                    <div>
                                        <dt>
                                            {t(
                                                "components.dashboard.messagesworkspace.from",
                                            )}
                                        </dt>
                                        <dd>
                                            {displayThread.counterpart
                                                ?.country || "Not shared"}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>
                                            {t(
                                                "components.dashboard.messagesworkspace.onBdgigsSince",
                                            )}
                                        </dt>
                                        <dd>
                                            {formatJoined(
                                                displayThread.counterpart
                                                    ?.joinedAt,
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Status</dt>
                                        <dd>
                                            {displayThread.status || "Open"}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Online</dt>
                                        <dd>
                                            {displayThread.counterpart?.online
                                                ? "Now"
                                                : "Offline"}
                                        </dd>
                                    </div>
                                </dl>
                            </section>

                            <section className="details-card">
                                <div className="details-heading">
                                    <h2>
                                        {t(
                                            "components.dashboard.messagesworkspace.attachedFiles",
                                        )}
                                    </h2>
                                    <a href="#files">
                                        {t(
                                            "components.dashboard.messagesworkspace.viewAll",
                                        )}
                                    </a>
                                </div>
                                <div className="attachment-list">
                                    {(displayThread.attachments || []).map(
                                        (attachment) => (
                                            <a
                                                href={
                                                    attachment.url || "#files"
                                                }
                                                className="attachment-item"
                                                key={attachment.id}
                                            >
                                                <span>
                                                    <strong>
                                                        {attachment.name ||
                                                            attachment.originalName}
                                                    </strong>
                                                    <small>
                                                        {attachment.mimeType ||
                                                            "Attachment"}
                                                    </small>
                                                </span>
                                            </a>
                                        ),
                                    )}
                                    {displayThread.attachments?.length === 0 ? (
                                        <p className="messages-empty">
                                            No files have been attached yet.
                                        </p>
                                    ) : null}
                                </div>
                            </section>

                            <section className="details-card">
                                <div className="details-heading">
                                    <h2>
                                        {t(
                                            "components.dashboard.messagesworkspace.relatedServices",
                                        )}
                                    </h2>
                                    <a href="#services">
                                        {t(
                                            "components.dashboard.messagesworkspace.seeMore",
                                        )}
                                    </a>
                                </div>
                                <div className="related-service-list">
                                    {displayThread.context?.gig ? (
                                        <a
                                            className="related-service-item"
                                            href={`/gigs/${displayThread.context.gig.id}`}
                                        >
                                            {displayThread.context.gig.image ? (
                                                <img
                                                    src={
                                                        displayThread.context
                                                            .gig.image
                                                    }
                                                    alt=""
                                                />
                                            ) : null}
                                            <span>
                                                <strong>
                                                    {
                                                        displayThread.context
                                                            .gig.title
                                                    }
                                                </strong>
                                                <small>
                                                    Conversation gig context
                                                </small>
                                            </span>
                                            <em>
                                                {
                                                    displayThread.context.gig
                                                        .price
                                                }
                                            </em>
                                        </a>
                                    ) : (
                                        <p className="messages-empty">
                                            No related service is attached.
                                        </p>
                                    )}
                                </div>
                            </section>
                        </>
                    ) : (
                        <section className="details-card messages-details-empty">
                            <h2>Conversation details</h2>
                            <p>
                                Select a thread to see order context, files, and
                                participant details.
                            </p>
                        </section>
                    )}
                </aside>
            </section>
            <CustomOfferModal
                isLoading={customOfferLoading}
                isOpen={isCustomOfferModalOpen}
                onClose={() => setIsCustomOfferModalOpen(false)}
                onSubmit={handleCreateCustomOffer}
                services={customOfferOptions}
            />
        </main>
    );
}

function CustomOfferMessageCard({ isLoading, offer, onAction }) {
    return (
        <section className="custom-offer-card">
            <div className="custom-offer-card-head">
                {offer.gig?.image ? <img src={offer.gig.image} alt="" /> : null}
                <span>
                    <small>Custom offer</small>
                    <strong>{offer.title}</strong>
                    {offer.gig?.title ? <em>{offer.gig.title}</em> : null}
                </span>
                <b>{offer.priceFormatted}</b>
            </div>
            {offer.description ? <p>{offer.description}</p> : null}
            <dl>
                <div>
                    <dt>Delivery</dt>
                    <dd>
                        {offer.deliveryDays} day
                        {offer.deliveryDays === 1 ? "" : "s"}
                    </dd>
                </div>
                <div>
                    <dt>Revisions</dt>
                    <dd>{offer.revisions}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd>{offer.statusLabel}</dd>
                </div>
            </dl>
            {offer.terms ? <p className="custom-offer-terms">{offer.terms}</p> : null}
            <div className="custom-offer-actions">
                {offer.orderPath ? (
                    <a href={offer.orderPath} target="_blank" rel="noreferrer">
                        View order
                    </a>
                ) : null}
                {offer.canPay ? (
                    <>
                        <button
                            type="button"
                            disabled={isLoading === `accept-${offer.id}`}
                            onClick={() => onAction(offer, "accept")}
                        >
                            {isLoading === `accept-${offer.id}`
                                ? "Accepting..."
                                : "Accept"}
                        </button>
                        <button
                            type="button"
                            disabled={isLoading === `pay-${offer.id}`}
                            onClick={() => onAction(offer, "pay")}
                        >
                            {isLoading === `pay-${offer.id}`
                                ? "Paying..."
                                : "Pay from balance"}
                        </button>
                    </>
                ) : null}
                {offer.canDecline ? (
                    <button
                        type="button"
                        disabled={isLoading === `decline-${offer.id}`}
                        onClick={() => onAction(offer, "decline")}
                    >
                        {isLoading === `decline-${offer.id}`
                            ? "Declining..."
                            : "Decline"}
                    </button>
                ) : null}
                {offer.canCancel ? (
                    <button
                        type="button"
                        disabled={isLoading === `cancel-${offer.id}`}
                        onClick={() => onAction(offer, "cancel")}
                    >
                        {isLoading === `cancel-${offer.id}`
                            ? "Cancelling..."
                            : "Cancel offer"}
                    </button>
                ) : null}
            </div>
        </section>
    );
}

function CustomOfferModal({ isLoading, isOpen, onClose, onSubmit, services }) {
    const [draft, setDraft] = useState({
        gigId: "",
        title: "",
        description: "",
        price: "",
        deliveryDays: "3",
        revisions: "2 revisions",
        terms: "",
        expiresInDays: "7",
    });
    const [error, setError] = useState("");

    useEffect(() => {
        if (!isOpen) {
            setDraft({
                gigId: "",
                title: "",
                description: "",
                price: "",
                deliveryDays: "3",
                revisions: "2 revisions",
                terms: "",
                expiresInDays: "7",
            });
            setError("");
        }
    }, [isOpen]);

    useEffect(() => {
        if (!isOpen || draft.gigId || services.length === 0) return;
        const service = services[0];

        setDraft((current) => ({
            ...current,
            gigId: service.id,
            title: service.title,
            price: service.priceValue ? String(service.priceValue) : "",
            deliveryDays: String(service.deliveryDays || 3),
        }));
    }, [draft.gigId, isOpen, services]);

    const updateDraft = (field, value) => {
        setDraft((current) => ({ ...current, [field]: value }));
    };
    const submitOffer = async (event) => {
        event.preventDefault();

        if (!draft.gigId) {
            setError("Choose a service for this offer.");
            return;
        }

        if (Number(draft.price) <= 0) {
            setError("Add a valid offer price.");
            return;
        }

        setError("");
        await onSubmit({
            ...draft,
            price: Number(draft.price),
            deliveryDays: Number(draft.deliveryDays),
            expiresInDays: Number(draft.expiresInDays),
        });
    };

    if (!isOpen) return null;

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
                className="order-note-modal custom-offer-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="customOfferModalTitle"
            >
                <header>
                    <div>
                        <span>Conversation offer</span>
                        <h2 id="customOfferModalTitle">Create Custom Offer</h2>
                    </div>
                    <button
                        type="button"
                        aria-label="Close custom offer"
                        onClick={onClose}
                    >
                        <Icon name="close" />
                    </button>
                </header>
                {isLoading === "options" ? (
                    <p className="messages-empty">Loading your services...</p>
                ) : services.length === 0 ? (
                    <p className="messages-empty">
                        Publish a service before sending a custom offer.
                    </p>
                ) : (
                    <form
                        className="custom-offer-form"
                        onSubmit={submitOffer}
                    >
                        <label>
                            <span>Service</span>
                            <select
                                value={draft.gigId}
                                onChange={(event) => {
                                    const service = services.find(
                                        (item) => item.id === event.target.value,
                                    );
                                    updateDraft("gigId", event.target.value);

                                    if (service) {
                                        setDraft((current) => ({
                                            ...current,
                                            gigId: service.id,
                                            title: service.title,
                                            price: service.priceValue
                                                ? String(service.priceValue)
                                                : current.price,
                                            deliveryDays: String(
                                                service.deliveryDays || 3,
                                            ),
                                        }));
                                    }
                                }}
                            >
                                {services.map((service) => (
                                    <option
                                        value={service.id}
                                        key={service.id}
                                    >
                                        {service.title}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            <span>Offer title</span>
                            <input
                                value={draft.title}
                                onChange={(event) =>
                                    updateDraft("title", event.target.value)
                                }
                                required
                            />
                        </label>
                        <label>
                            <span>Description</span>
                            <textarea
                                rows="4"
                                value={draft.description}
                                placeholder="Describe the exact custom scope."
                                onChange={(event) =>
                                    updateDraft(
                                        "description",
                                        event.target.value,
                                    )
                                }
                            />
                        </label>
                        <div className="custom-offer-form-grid">
                            <label>
                                <span>Price</span>
                                <input
                                    min="1"
                                    step="1"
                                    type="number"
                                    value={draft.price}
                                    onChange={(event) =>
                                        updateDraft("price", event.target.value)
                                    }
                                    required
                                />
                            </label>
                            <label>
                                <span>Delivery days</span>
                                <input
                                    min="1"
                                    max="90"
                                    type="number"
                                    value={draft.deliveryDays}
                                    onChange={(event) =>
                                        updateDraft(
                                            "deliveryDays",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </label>
                            <label>
                                <span>Revisions</span>
                                <input
                                    value={draft.revisions}
                                    onChange={(event) =>
                                        updateDraft(
                                            "revisions",
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                            </label>
                            <label>
                                <span>Expires in days</span>
                                <input
                                    min="1"
                                    max="30"
                                    type="number"
                                    value={draft.expiresInDays}
                                    onChange={(event) =>
                                        updateDraft(
                                            "expiresInDays",
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                        </div>
                        <label>
                            <span>Offer terms</span>
                            <textarea
                                rows="3"
                                value={draft.terms}
                                placeholder="Add delivery terms, accepted files, or limits."
                                onChange={(event) =>
                                    updateDraft("terms", event.target.value)
                                }
                            />
                        </label>
                        {error ? (
                            <p className="order-note-error" role="alert">
                                {error}
                            </p>
                        ) : null}
                        <button
                            type="submit"
                            disabled={isLoading === "create"}
                        >
                            {isLoading === "create"
                                ? "Sending..."
                                : "Send custom offer"}
                        </button>
                    </form>
                )}
            </section>
        </div>
    );
}

function ConversationListSkeleton() {
    return (
        <div
            className="message-thread-skeleton-list"
            aria-label="Loading conversations"
            role="status"
        >
            {Array.from({ length: 6 }, (_, index) => (
                <div className="message-thread-skeleton" key={index}>
                    <LoadingSkeleton className="message-thread-skeleton-avatar" />
                    <span>
                        <LoadingSkeleton className="message-thread-skeleton-name" />
                        <LoadingSkeleton className="message-thread-skeleton-copy" />
                    </span>
                </div>
            ))}
        </div>
    );
}

function ConversationPanelSkeleton() {
    return (
        <section
            className="conversation-panel-skeleton"
            aria-label="Loading messages"
            role="status"
        >
            <div className="conversation-panel-skeleton-head">
                <LoadingSkeleton className="message-thread-skeleton-avatar" />
                <span>
                    <LoadingSkeleton />
                    <LoadingSkeleton />
                </span>
            </div>
            <div className="conversation-panel-skeleton-thread">
                <LoadingSkeleton />
                <LoadingSkeleton />
                <LoadingSkeleton />
                <LoadingSkeleton />
            </div>
        </section>
    );
}

function NoConversationSelected() {
    return (
        <section className="conversation-empty-graphic">
            <svg viewBox="0 0 320 220" aria-hidden="true">
                <path
                    d="M55 48c0-13 11-24 24-24h162c13 0 24 11 24 24v91c0 13-11 24-24 24h-76l-38 30v-30H79c-13 0-24-11-24-24V48Z"
                    fill="currentColor"
                    opacity="0.08"
                />
                <path
                    d="M82 68h120M82 96h88M82 124h62"
                    stroke="currentColor"
                    strokeLinecap="round"
                    strokeWidth="10"
                    opacity="0.24"
                />
                <circle
                    cx="239"
                    cy="58"
                    r="25"
                    fill="currentColor"
                    opacity="0.12"
                />
                <path
                    d="m228 58 8 8 15-18"
                    stroke="currentColor"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="7"
                />
            </svg>
            <h1 id="activeConversationTitle">No message selected</h1>
            <p>
                Choose a conversation from the inbox to read updates and reply.
            </p>
        </section>
    );
}

function formatJoined(value) {
    if (!value) return "Not shared";

    return new Intl.DateTimeFormat("en", {
        month: "short",
        year: "numeric",
    }).format(new Date(value));
}

function ConversationAvatar({ thread }) {
    const avatar = thread?.counterpart?.avatar;

    if (avatar) {
        return (
            <img
                className="avatar conversation-avatar-image"
                src={avatar}
                alt={`${thread.name || "Member"} profile`}
                loading="lazy"
            />
        );
    }

    return <span className="avatar">{thread?.initials || "BD"}</span>;
}
export default MessagesWorkspace;
