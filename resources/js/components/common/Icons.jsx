const iconPaths = {
  brand: (
    <>
      <path d="M12 3 20 7.5V16.5L12 21 4 16.5V7.5L12 3Z" stroke="currentColor" strokeWidth="2" />
      <path d="M8.5 12H15.5M12 8.5V15.5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </>
  ),
  search: (
    <path
      d="m21 21-4.4-4.4M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
    />
  ),
  home: (
    <path
      d="M4 11.5 12 4l8 7.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-8.5Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
    />
  ),
  chevronDown: (
    <path d="m7 10 5 5 5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
  ),
  star: (
    <path
      d="m12 2.8 2.7 5.6 6.2.9-4.5 4.4 1.1 6.2L12 17l-5.5 2.9 1.1-6.2-4.5-4.4 6.2-.9L12 2.8Z"
      fill="currentColor"
    />
  ),
  heart: (
    <path
      d="M12 20s-7-4.4-9.2-8.8C1.2 8 3.3 5 6.6 5c2 0 3.3 1 4.1 2.1C11.5 6 12.8 5 14.8 5c3.3 0 5.4 3 3.8 6.2C16.4 15.6 12 20 12 20Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
    />
  ),
  bolt: (
    <path d="M13 3 4 14h7l-1 7 10-12h-7V3Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
  ),
  arrowRight: (
    <path
      d="M5 12h14M13 6l6 6-6 6"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  palette: (
    <path
      d="M12 3a9 9 0 0 0 0 18h1.5a2 2 0 0 0 0-4H13a1.5 1.5 0 0 1 0-3h2a6 6 0 0 0 0-12h-3Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
    />
  ),
  code: (
    <path
      d="m8 9-4 3 4 3M16 9l4 3-4 3M14 5l-4 14"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  megaphone: (
    <path
      d="M4 13h3l9 5V6L7 11H4v2ZM7 13v5a2 2 0 0 0 2 2h1"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
    />
  ),
  video: (
    <>
      <path d="M5 5h14v14H5V5Z" stroke="currentColor" strokeWidth="2" />
      <path d="m10 9 5 3-5 3V9Z" fill="currentColor" />
    </>
  ),
  document: (
    <path
      d="M6 4h9l3 3v13H6V4ZM9 12h6M9 16h6M14 4v4h4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  spark: (
    <>
      <path
        d="M12 3v3M12 18v3M4.6 5.6l2.1 2.1M17.3 17.3l2.1 2.1M3 12h3M18 12h3M5.6 19.4l2.1-2.1M17.3 6.7l2.1-2.1"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
      />
      <path d="M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0Z" stroke="currentColor" strokeWidth="2" />
    </>
  ),
  verifiedUser: (
    <path
      d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM17 11l2 2 4-4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  packageCheck: (
    <path
      d="M20 7.5 12 3 4 7.5m16 0v9L12 21m8-13.5-8 4.5m0 9-8-4.5v-9m8 13.5v-9M4 7.5l8 4.5M16.5 5.5l-8 4.5M15 15l1.5 1.5L20 13"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  dashboard: <path d="M4 5h7v7H4V5ZM13 5h7v4h-7V5ZM13 11h7v8h-7v-8ZM4 14h7v5H4v-5Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />,
  orders: (
    <path
      d="M6 4h12v16H6V4ZM9 8h6M9 12h6M9 16h4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
    />
  ),
  message: <path d="M4 5h16v11H8l-4 4V5Z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />,
  payment: (
    <path
      d="M4 7h16v10H4V7ZM4 10h16M8 15h3"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
    />
  ),
  user: (
    <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 20a8 8 0 0 1 16 0" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
  ),
  settings: (
    <>
      <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" strokeWidth="2" />
      <path
        d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2 3.4-.2-.1a1.8 1.8 0 0 0-2 .1 1.7 1.7 0 0 0-.9 1.6v.3H10v-.3a1.7 1.7 0 0 0-.9-1.6 1.8 1.8 0 0 0-2-.1l-.2.1-2-3.4.1-.1A1.7 1.7 0 0 0 5.6 15a1.7 1.7 0 0 0-1.4-1H4v-4h.2a1.7 1.7 0 0 0 1.4-1A1.7 1.7 0 0 0 5.3 7l-.1-.1 2-3.4.2.1a1.8 1.8 0 0 0 2-.1A1.7 1.7 0 0 0 10.3 2h3.4a1.7 1.7 0 0 0 .9 1.5 1.8 1.8 0 0 0 2 .1l.2-.1 2 3.4-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.4 1h.2v4h-.2a1.7 1.7 0 0 0-1.4 1Z"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinejoin="round"
      />
    </>
  ),
  menu: <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />,
  close: <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />,
  bell: (
    <path
      d="M18 10a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  chart: (
    <path
      d="M4 19V5M4 19h16M8 16V11M12 16V8M16 16v-6"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  moreHorizontal: (
    <path
      d="M5 12h.01M12 12h.01M19 12h.01"
      stroke="currentColor"
      strokeWidth="3"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  tag: (
    <path
      d="M20 13.2 13.2 20a2 2 0 0 1-2.8 0L4 13.6V4h9.6l6.4 6.4a2 2 0 0 1 0 2.8ZM8.5 8.5h.01"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  archive: (
    <path
      d="M4 8h16M6 8v11h12V8M4 5h16v3H4V5ZM10 12h4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  trash: (
    <path
      d="M5 7h14M10 11v6M14 11v6M9 7l1-3h4l1 3M7 7l1 13h8l1-13"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  paperclip: (
    <path
      d="m21 11.5-8.7 8.7a5 5 0 0 1-7.1-7.1l9.2-9.2a3.3 3.3 0 0 1 4.7 4.7l-9.2 9.2a1.7 1.7 0 0 1-2.4-2.4l8.6-8.6"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  smile: (
    <path
      d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  send: (
    <path
      d="m22 2-7 20-4-9-9-4 20-7ZM11 13l11-11"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  reply: (
    <path
      d="M10 8 5 13l5 5M5 13h9a5 5 0 0 1 5 5v1"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  plus: <path d="M12 5v14M5 12h14" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />,
  edit: (
    <path
      d="M4 20h4l10.5-10.5a2.2 2.2 0 0 0-3.1-3.1L5 16.8 4 20ZM13.8 8.2l2 2"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  eye: (
    <path
      d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  share: (
    <path
      d="M14 4h6v6M20 4l-9 9M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  location: (
    <path
      d="M12 21s7-5.4 7-12a7 7 0 1 0-14 0c0 6.6 7 12 7 12ZM12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  camera: (
    <path
      d="M4 8h4l1.5-2h5L16 8h4v11H4V8ZM12 16a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  play: (
    <path
      d="M8 5v14l11-7L8 5Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinejoin="round"
      fill="currentColor"
    />
  ),
  building: (
    <path
      d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M3 21h18M8 7h4M8 11h4M8 15h4M16 9h2a2 2 0 0 1 2 2v10"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  graduation: (
    <path
      d="m3 9 9-5 9 5-9 5-9-5ZM7 11.2v4.2c0 1.4 2.2 2.6 5 2.6s5-1.2 5-2.6v-4.2M21 9v6"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  flag: (
    <path
      d="M5 21V4h9l1 3h4v10h-9l-1-3H5"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  thumbsUp: (
    <path
      d="M7 21H4a1 1 0 0 1-1-1v-7a1 1 0 0 1 1-1h3M7 21h9a3 3 0 0 0 3-2.4l1.2-6A2 2 0 0 0 18.2 10H14l.7-3.6A2.8 2.8 0 0 0 12 3l-5 9v9Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
  thumbsDown: (
    <path
      d="M17 3h3a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-3M17 3H8a3 3 0 0 0-3 2.4l-1.2 6A2 2 0 0 0 5.8 14H10l-.7 3.6A2.8 2.8 0 0 0 12 21l5-9V3Z"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    />
  ),
};

export function Icon({ name, className, title, ...props }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" aria-hidden={title ? undefined : true} {...props}>
      {title ? <title>{title}</title> : null}
      {iconPaths[name]}
    </svg>
  );
}

export function BrandMark() {
  return (
    <span className="brand-mark" aria-hidden="true">
      <Icon name="brand" />
    </span>
  );
}

export function Rating({ value, reviews }) {
  return (
    <span className="rating">
      <Icon name="star" />
      {value}
      {reviews ? ` (${reviews})` : ""}
    </span>
  );
}
