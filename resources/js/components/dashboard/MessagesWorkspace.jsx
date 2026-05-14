import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
    buyerMessageThreads,
    sellerMessageThreads,
} from "../../data/dashboardData.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";
import { useTranslation } from "react-i18next";
const relatedServiceImages = [
    "/assets/img/gig_images/6.png",
    "/assets/img/gig_images/8.png",
    "/assets/img/gig_images/11.png",
];
function MessagesWorkspace({ variant = "buyer" }) {
    const { t } = useTranslation();
    const isSeller = variant === "seller";
    const threads = isSeller ? sellerMessageThreads : buyerMessageThreads;
    const [activeThreadIds, setActiveThreadIds] = useState({});
    const [isInboxSearchOpen, setIsInboxSearchOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState("");
    const [conversationMenuOpen, setConversationMenuOpen] = useState(false);
    const [openMessageMenu, setOpenMessageMenu] = useState(null);
    const [draft, setDraft] = useState("");
    const [sentMessages, setSentMessages] = useState({});
    const searchInputRef = useRef(null);
    const textareaRef = useRef(null);
    const workspaceRef = useRef(null);
    const activeThreadId = activeThreadIds[variant] || threads[0]?.id;
    const closeMenus = useCallback(() => {
        setConversationMenuOpen(false);
        setOpenMessageMenu(null);
    }, []);
    useDismissOnInteractOutside(
        workspaceRef,
        conversationMenuOpen || openMessageMenu !== null,
        closeMenus,
    );
    useEffect(() => {
        if (isInboxSearchOpen) {
            searchInputRef.current?.focus();
        }
    }, [isInboxSearchOpen]);
    useEffect(() => {
        if (!textareaRef.current) return;
        textareaRef.current.style.height = "auto";
        textareaRef.current.style.height = `${Math.min(textareaRef.current.scrollHeight, 180)}px`;
    }, [draft, activeThreadId]);
    const activeThread = useMemo(
        () =>
            threads.find((thread) => thread.id === activeThreadId) ||
            threads[0],
        [activeThreadId, threads],
    );
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
    const activeMessages = useMemo(
        () => [
            ...activeThread.messages,
            ...(sentMessages[activeThread.id] || []),
        ],
        [activeThread, sentMessages],
    );
    const relatedServices = useMemo(
        () => [
            {
                title: activeThread.service,
                seller: activeThread.name,
                rating: "4.9",
                price: isSeller ? "$480" : "$75",
                image: relatedServiceImages[0],
            },
            {
                title: isSeller
                    ? "Conversion-focused dashboard design"
                    : "Responsive marketplace homepage",
                seller: isSeller ? "BDGigs Pro" : "Marco L.",
                rating: "5.0",
                price: isSeller ? "$360" : "$120",
                image: relatedServiceImages[1],
            },
            {
                title: isSeller
                    ? "Premium brand system starter pack"
                    : "Product UX audit with notes",
                seller: isSeller ? "Design Partner" : "Elena V.",
                rating: "4.8",
                price: isSeller ? "$210" : "$95",
                image: relatedServiceImages[2],
            },
        ],
        [activeThread.name, activeThread.service, isSeller],
    );
    const handleSendMessage = () => {
        const text = draft.trim();
        if (!text) return;
        const now = new Date().toLocaleTimeString([], {
            hour: "numeric",
            minute: "2-digit",
        });
        const newMessage = {
            from: "Jahid",
            text,
            time: now,
            own: true,
        };
        setSentMessages((current) => ({
            ...current,
            [activeThread.id]: [
                ...(current[activeThread.id] || []),
                newMessage,
            ],
        }));
        setDraft("");
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
                        <button
                            className="inbox-title-button"
                            type="button"
                            aria-label={t(
                                "components.dashboard.messagesworkspace.filterAllMessages",
                            )}
                        >
                            {" "}
                            {t(
                                "components.dashboard.messagesworkspace.allMessages",
                            )}{" "}
                            <Icon name="chevronDown" />
                        </button>
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
                        {filteredThreads.length > 0 ? (
                            filteredThreads.map((thread) => (
                                <button
                                    className={`message-thread${thread.id === activeThread.id ? " active" : ""}`}
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
                    <header className="conversation-header">
                        <div className="conversation-person">
                            <span className="avatar">
                                {activeThread.initials}
                            </span>
                            <div>
                                <h1 id="activeConversationTitle">
                                    {activeThread.name}{" "}
                                    <span>
                                        @
                                        {activeThread.name
                                            .toLowerCase()
                                            .replace(/[^a-z0-9]/g, "")}
                                    </span>
                                </h1>
                                <p>
                                    {t(
                                        "components.dashboard.messagesworkspace.lastSeen",
                                    )}{" "}
                                    {activeThread.time}{" "}
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
                                    <button type="button" role="menuitem">
                                        <Icon name="message" />{" "}
                                        {t(
                                            "components.dashboard.messagesworkspace.markAsUnread",
                                        )}{" "}
                                    </button>
                                    <button type="button" role="menuitem">
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
                        <button className="active" type="button">
                            {" "}
                            {t(
                                "components.dashboard.messagesworkspace.messages",
                            )}{" "}
                        </button>
                        <button type="button">
                            {t("components.dashboard.messagesworkspace.saved")}
                        </button>
                    </div>

                    <div
                        className="conversation-messages"
                        aria-label={`Conversation with ${activeThread.name}`}
                    >
                        <div className="conversation-date">
                            {t("components.dashboard.messagesworkspace.today")}
                        </div>
                        {activeMessages.map((message, index) => {
                            const messageKey = `${activeThread.id}-${message.from}-${message.time}-${index}`;
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

                    <form
                        className="conversation-composer"
                        onClick={(event) => event.stopPropagation()}
                        onSubmit={(event) => {
                            event.preventDefault();
                            handleSendMessage();
                        }}
                    >
                        <label className="sr-only" htmlFor="messageReply">
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
                            onChange={(event) => setDraft(event.target.value)}
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
                                disabled={!draft.trim()}
                            >
                                <Icon name="send" />
                            </button>
                        </div>
                    </form>
                </article>

                <aside
                    className="conversation-details-panel"
                    aria-label={t(
                        "components.dashboard.messagesworkspace.conversationDetails",
                    )}
                >
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
                                {threads.length})
                            </a>
                        </div>
                        <div className="details-order">
                            <span
                                className={`status-badge ${activeThread.statusClass}`}
                            >
                                {activeThread.status}
                            </span>
                            <strong>{activeThread.service}</strong>
                            <small>
                                {activeThread.priority}{" "}
                                {t(
                                    "components.dashboard.messagesworkspace.dueThisWeek",
                                )}
                            </small>
                        </div>
                    </section>

                    <section className="details-card">
                        <h2>
                            {t("components.dashboard.messagesworkspace.about")}{" "}
                            {activeThread.name}
                        </h2>
                        <dl className="details-list">
                            <div>
                                <dt>
                                    {t(
                                        "components.dashboard.messagesworkspace.from",
                                    )}
                                </dt>
                                <dd>
                                    {isSeller ? "United States" : "Pakistan"}
                                </dd>
                            </div>
                            <div>
                                <dt>
                                    {t(
                                        "components.dashboard.messagesworkspace.onBdgigsSince",
                                    )}
                                </dt>
                                <dd>
                                    {t(
                                        "components.dashboard.messagesworkspace.jan2023",
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt>
                                    {isSeller ? "Buyer type" : "Seller level"}
                                </dt>
                                <dd>{isSeller ? "Business" : "Level 2"}</dd>
                            </div>
                            <div>
                                <dt>
                                    {t(
                                        "components.dashboard.messagesworkspace.responseRate",
                                    )}
                                </dt>
                                <dd>
                                    {t(
                                        "components.dashboard.messagesworkspace.1h",
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt>
                                    {t(
                                        "components.dashboard.messagesworkspace.rating",
                                    )}
                                </dt>
                                <dd>
                                    {t(
                                        "components.dashboard.messagesworkspace.4985",
                                    )}
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
                            <a href="#files" className="attachment-item">
                                <img
                                    src="/assets/img/gig_images/2.png"
                                    alt=""
                                />
                                <span>
                                    <strong>
                                        {t(
                                            "components.dashboard.messagesworkspace.designReferencePng",
                                        )}
                                    </strong>
                                    <small>
                                        {t(
                                            "components.dashboard.messagesworkspace.image24Mb",
                                        )}
                                    </small>
                                </span>
                            </a>
                            <a href="#files" className="attachment-item">
                                <img
                                    src="/assets/img/gig_images/3.png"
                                    alt=""
                                />
                                <span>
                                    <strong>
                                        {t(
                                            "components.dashboard.messagesworkspace.revisionNotesJpg",
                                        )}
                                    </strong>
                                    <small>
                                        {t(
                                            "components.dashboard.messagesworkspace.image18Mb",
                                        )}
                                    </small>
                                </span>
                            </a>
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
                            {relatedServices.map((service) => (
                                <a
                                    className="related-service-item"
                                    href="#services"
                                    key={`${service.title}-${service.price}`}
                                >
                                    <img src={service.image} alt="" />
                                    <span>
                                        <strong>{service.title}</strong>
                                        <small>
                                            {service.seller} - {service.rating}
                                        </small>
                                    </span>
                                    <em>{service.price}</em>
                                </a>
                            ))}
                        </div>
                    </section>
                </aside>
            </section>
        </main>
    );
}
export default MessagesWorkspace;
