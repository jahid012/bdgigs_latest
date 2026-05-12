import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { buyerMessageThreads, sellerMessageThreads } from "../../data/dashboardData.js";
import { useDismissOnInteractOutside } from "../../hooks/useDismissOnInteractOutside.js";
import { Icon } from "../common/Icons.jsx";

const relatedServiceImages = [
  "/assets/img/gig_images/6.png",
  "/assets/img/gig_images/8.png",
  "/assets/img/gig_images/11.png",
];

function MessagesWorkspace({ variant = "buyer" }) {
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

  useDismissOnInteractOutside(workspaceRef, conversationMenuOpen || openMessageMenu !== null, closeMenus);

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
    () => threads.find((thread) => thread.id === activeThreadId) || threads[0],
    [activeThreadId, threads],
  );

  const filteredThreads = useMemo(() => {
    const query = searchTerm.trim().toLowerCase();

    if (!query) return threads;

    return threads.filter((thread) => {
      const searchable = [thread.name, thread.role, thread.service, thread.status, thread.priority, thread.preview].join(" ").toLowerCase();
      return searchable.includes(query);
    });
  }, [searchTerm, threads]);

  const activeMessages = useMemo(
    () => [...activeThread.messages, ...(sentMessages[activeThread.id] || [])],
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
        title: isSeller ? "Conversion-focused dashboard design" : "Responsive marketplace homepage",
        seller: isSeller ? "BDGigs Pro" : "Marco L.",
        rating: "5.0",
        price: isSeller ? "$360" : "$120",
        image: relatedServiceImages[1],
      },
      {
        title: isSeller ? "Premium brand system starter pack" : "Product UX audit with notes",
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

    const now = new Date().toLocaleTimeString([], { hour: "numeric", minute: "2-digit" });
    const newMessage = {
      from: "Jahid",
      text,
      time: now,
      own: true,
    };

    setSentMessages((current) => ({
      ...current,
      [activeThread.id]: [...(current[activeThread.id] || []), newMessage],
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
    <main className="dashboard-content messages-page" ref={workspaceRef} onClick={closeMenus}>
      <section className="messages-shell" aria-label="Dashboard messages">
        <aside className="messages-thread-list" aria-label="Conversation list">
          <div className="messages-inbox-toolbar">
            <button className="inbox-title-button" type="button" aria-label="Filter all messages">
              All messages
              <Icon name="chevronDown" />
            </button>
            <button
              className={`inbox-search-toggle${isInboxSearchOpen ? " active" : ""}`}
              type="button"
              aria-expanded={isInboxSearchOpen}
              aria-label="Search conversations"
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
            aria-label="Search messages"
            onClick={(event) => event.stopPropagation()}
            onSubmit={(event) => event.preventDefault()}
          >
            <Icon name="search" />
            <label className="sr-only" htmlFor="messagesSearch">
              Search messages
            </label>
            <input
              ref={searchInputRef}
              id="messagesSearch"
              type="search"
              value={searchTerm}
              placeholder="Search conversations..."
              autoComplete="off"
              onChange={(event) => setSearchTerm(event.target.value)}
            />
            <button
              className="messages-search-close"
              type="button"
              onClick={() => {
                setIsInboxSearchOpen(false);
                setSearchTerm("");
              }}
            >
              Close
            </button>
          </form>

          <div className="message-thread-items">
            {filteredThreads.length > 0 ? (
              filteredThreads.map((thread) => (
                <button
                  className={`message-thread${thread.id === activeThread.id ? " active" : ""}`}
                  type="button"
                  key={thread.id}
                  onClick={() => setActiveThreadIds((current) => ({ ...current, [variant]: thread.id }))}
                >
                  <span className="avatar">{thread.initials}</span>
                  <span className="message-thread-body">
                    <span className="message-thread-top">
                      <strong>{thread.name}</strong>
                      <small>{thread.time}</small>
                    </span>
                    <span className="message-thread-preview">{thread.preview}</span>
                  </span>
                  <span className="message-thread-favorite" aria-hidden="true">
                    <Icon name="star" />
                  </span>
                </button>
              ))
            ) : (
              <p className="messages-empty">No conversations found.</p>
            )}
          </div>
        </aside>

        <article className="conversation-panel" aria-labelledby="activeConversationTitle">
          <header className="conversation-header">
            <div className="conversation-person">
              <span className="avatar">{activeThread.initials}</span>
              <div>
                <h1 id="activeConversationTitle">
                  {activeThread.name} <span>@{activeThread.name.toLowerCase().replace(/[^a-z0-9]/g, "")}</span>
                </h1>
                <p>Last seen {activeThread.time} - Local time 4:29 AM</p>
              </div>
            </div>

            <div className="conversation-header-tools">
              <button className="icon-button ghost" type="button" aria-label="Tag conversation">
                <Icon name="tag" />
              </button>
              <button className="icon-button ghost" type="button" aria-label="Save conversation">
                <Icon name="star" />
              </button>
              <div className="conversation-menu-wrap">
                <button
                  className="icon-button ghost"
                  type="button"
                  aria-label="More conversation actions"
                  aria-expanded={conversationMenuOpen}
                  onClick={(event) => {
                    event.stopPropagation();
                    setConversationMenuOpen((isOpen) => !isOpen);
                    setOpenMessageMenu(null);
                  }}
                >
                  <Icon name="moreHorizontal" />
                </button>
                <div className={`message-action-menu conversation-more-menu${conversationMenuOpen ? " is-open" : ""}`} role="menu">
                  <button type="button" role="menuitem">
                    <Icon name="message" />
                    Mark as unread
                  </button>
                  <button type="button" role="menuitem">
                    <Icon name="archive" />
                    Move to archive
                  </button>
                  <button className="danger" type="button" role="menuitem">
                    <Icon name="trash" />
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </header>

          <div className="conversation-tabs" aria-label="Conversation views">
            <button className="active" type="button">
              Messages
            </button>
            <button type="button">Saved</button>
          </div>

          <div className="conversation-messages" aria-label={`Conversation with ${activeThread.name}`}>
            <div className="conversation-date">Today</div>
            {activeMessages.map((message, index) => {
              const messageKey = `${activeThread.id}-${message.from}-${message.time}-${index}`;

              return (
                <article className={`conversation-bubble${message.own ? " own" : ""}`} key={messageKey}>
                  <div className="conversation-bubble-top">
                    <strong>{message.from}</strong>
                    <time>{message.time}</time>
                    <div className="message-menu-wrap">
                      <button
                        className="message-more-button"
                        type="button"
                        aria-label="Message actions"
                        aria-expanded={openMessageMenu === messageKey}
                        onClick={(event) => {
                          event.stopPropagation();
                          setOpenMessageMenu((current) => (current === messageKey ? null : messageKey));
                          setConversationMenuOpen(false);
                        }}
                      >
                        <Icon name="moreHorizontal" />
                      </button>
                      <div className={`message-action-menu bubble-action-menu${openMessageMenu === messageKey ? " is-open" : ""}`} role="menu">
                        <button type="button" role="menuitem">
                          <Icon name="reply" />
                          Reply
                        </button>
                        <button type="button" role="menuitem">
                          <Icon name="archive" />
                          Move to archive
                        </button>
                        <button className="danger" type="button" role="menuitem">
                          <Icon name="flag" />
                          Report
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
              Reply to conversation
            </label>
            <textarea
              ref={textareaRef}
              id="messageReply"
              value={draft}
              rows="3"
              maxLength="2000"
              placeholder="Write a message..."
              onChange={(event) => setDraft(event.target.value)}
              onKeyDown={handleComposerKeyDown}
            />
            <div className="composer-footer">
              <div className="composer-tools" aria-label="Message tools">
                <button type="button" aria-label="Attach file">
                  <Icon name="paperclip" />
                </button>
                <button type="button" aria-label="Add emoji">
                  <Icon name="smile" />
                </button>
                <span>Shift + Enter for new line</span>
              </div>
              <button className="composer-send" type="submit" aria-label="Send message" disabled={!draft.trim()}>
                <Icon name="send" />
              </button>
            </div>
          </form>
        </article>

        <aside className="conversation-details-panel" aria-label="Conversation details">
          <section className="details-card">
            <div className="details-heading">
              <h2>Orders with you</h2>
              <a href="#orders">Total ({threads.length})</a>
            </div>
            <div className="details-order">
              <span className={`status-badge ${activeThread.statusClass}`}>{activeThread.status}</span>
              <strong>{activeThread.service}</strong>
              <small>{activeThread.priority} - due this week</small>
            </div>
          </section>

          <section className="details-card">
            <h2>About {activeThread.name}</h2>
            <dl className="details-list">
              <div>
                <dt>From</dt>
                <dd>{isSeller ? "United States" : "Pakistan"}</dd>
              </div>
              <div>
                <dt>On BDGigs since</dt>
                <dd>Jan 2023</dd>
              </div>
              <div>
                <dt>{isSeller ? "Buyer type" : "Seller level"}</dt>
                <dd>{isSeller ? "Business" : "Level 2"}</dd>
              </div>
              <div>
                <dt>Response rate</dt>
                <dd>1h</dd>
              </div>
              <div>
                <dt>Rating</dt>
                <dd>4.9 (85)</dd>
              </div>
            </dl>
          </section>

          <section className="details-card">
            <div className="details-heading">
              <h2>Attached files</h2>
              <a href="#files">View all</a>
            </div>
            <div className="attachment-list">
              <a href="#files" className="attachment-item">
                <img src="/assets/img/gig_images/2.png" alt="" />
                <span>
                  <strong>design-reference.png</strong>
                  <small>Image - 2.4 MB</small>
                </span>
              </a>
              <a href="#files" className="attachment-item">
                <img src="/assets/img/gig_images/3.png" alt="" />
                <span>
                  <strong>revision-notes.jpg</strong>
                  <small>Image - 1.8 MB</small>
                </span>
              </a>
            </div>
          </section>

          <section className="details-card">
            <div className="details-heading">
              <h2>Related services</h2>
              <a href="#services">See more</a>
            </div>
            <div className="related-service-list">
              {relatedServices.map((service) => (
                <a className="related-service-item" href="#services" key={`${service.title}-${service.price}`}>
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
