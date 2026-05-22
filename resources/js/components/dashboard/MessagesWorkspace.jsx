import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";
import LoadingSkeleton from "../common/LoadingSkeleton.jsx";
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
    const [draft, setDraft] = useState("");
    const searchInputRef = useRef(null);
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
                thread.role,
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
        if (!text || !activeThread?.id) return;
        setDraft("");

        try {
            await sendMessage(activeThread.id, text);
        } catch {
            setDraft(text);
        }
    };
    const handleComposerKeyDown = (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            handleSendMessage();
        }
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
                                    <span className="avatar">
                                        {thread.initials}
                                    </span>
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
                                    <span className="avatar">
                                        {displayThread.initials}
                                    </span>
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
                                            <p>{message.text}</p>
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
                                            >
                                                <Icon name="paperclip" />
                                            </button>
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
                                                !draft.trim() || !activeThread
                                            }
                                        >
                                            <Icon name="send" />
                                        </button>
                                    </div>
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
                                        <dt>Conversation role</dt>
                                        <dd>
                                            {displayThread.role || "Member"}
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
                        <ConversationDetailsSkeleton />
                    )}
                </aside>
            </section>
        </main>
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

function ConversationDetailsSkeleton() {
    return (
        <section
            className="conversation-details-skeleton"
            aria-label="Conversation detail placeholder"
            role="status"
        >
            <span className="sr-only">
                Select a message thread to load details.
            </span>
            <div className="details-card">
                <LoadingSkeleton className="conversation-details-skeleton-title" />
                <LoadingSkeleton className="conversation-details-skeleton-line" />
                <LoadingSkeleton className="conversation-details-skeleton-line short" />
            </div>
            <div className="details-card">
                <LoadingSkeleton className="conversation-details-skeleton-title" />
                {Array.from({ length: 4 }, (_, index) => (
                    <LoadingSkeleton
                        className="conversation-details-skeleton-row"
                        key={index}
                    />
                ))}
            </div>
            <div className="details-card">
                <LoadingSkeleton className="conversation-details-skeleton-title" />
                <LoadingSkeleton className="conversation-details-skeleton-file" />
                <LoadingSkeleton className="conversation-details-skeleton-file" />
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
export default MessagesWorkspace;
