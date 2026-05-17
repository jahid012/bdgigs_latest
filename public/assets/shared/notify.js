(function () {
  const DEFAULT_DURATION = 4500;
  const DEFAULT_TITLES = {
    success: 'Success',
    error: 'Something went wrong',
    warning: 'Heads up',
    info: 'Notice',
  };

  function ensureRoot() {
    let root = document.getElementById('bdgigs-notify-root');

    if (!root) {
      root = document.createElement('div');
      root.id = 'bdgigs-notify-root';
      root.className = 'bdgigs-toast-root';
      root.setAttribute('aria-live', 'polite');
      root.setAttribute('aria-atomic', 'false');
      document.body.appendChild(root);
    }

    return root;
  }

  function normalize(input, options) {
    if (typeof input === 'string') {
      return {
        type: options?.type || 'info',
        title: options?.title,
        message: input,
        duration: options?.duration,
        action: options?.action,
      };
    }

    return {
      type: input?.type || 'info',
      title: input?.title,
      message: input?.message || '',
      duration: input?.duration,
      action: input?.action,
    };
  }

  function closeToast(toast) {
    if (!toast || toast.classList.contains('is-hiding')) {
      return;
    }

    toast.classList.add('is-hiding');
    window.setTimeout(() => toast.remove(), 180);
  }

  function createToast(payload) {
    const root = ensureRoot();
    const toast = document.createElement('section');
    const type = ['success', 'error', 'warning', 'info'].includes(payload.type) ? payload.type : 'info';
    const duration = Number(payload.duration || DEFAULT_DURATION);
    let remaining = duration;
    let startedAt = Date.now();
    let timer = null;

    toast.className = 'bdgigs-toast';
    toast.dataset.type = type;
    toast.style.setProperty('--toast-duration', `${duration}ms`);
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const icon = document.createElement('span');
    icon.className = 'bdgigs-toast-icon';
    icon.setAttribute('aria-hidden', 'true');

    const body = document.createElement('div');
    body.className = 'bdgigs-toast-body';

    const title = document.createElement('p');
    title.className = 'bdgigs-toast-title';
    title.textContent = payload.title || DEFAULT_TITLES[type] || DEFAULT_TITLES.info;
    body.appendChild(title);

    if (payload.message) {
      const message = document.createElement('p');
      message.className = 'bdgigs-toast-message';
      message.textContent = payload.message;
      body.appendChild(message);
    }

    if (payload.action?.label && payload.action?.url) {
      const action = document.createElement('a');
      action.className = 'bdgigs-toast-action';
      action.href = payload.action.url;
      action.textContent = payload.action.label;
      body.appendChild(action);
    }

    const close = document.createElement('button');
    close.className = 'bdgigs-toast-close';
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.textContent = 'x';
    close.addEventListener('click', () => closeToast(toast));

    const progress = document.createElement('span');
    progress.className = 'bdgigs-toast-progress';
    progress.setAttribute('aria-hidden', 'true');

    toast.append(icon, body, close, progress);
    root.appendChild(toast);

    function startTimer(timeout) {
      startedAt = Date.now();
      timer = window.setTimeout(() => closeToast(toast), timeout);
    }

    toast.addEventListener('pointerenter', () => {
      toast.classList.add('is-paused');
      remaining -= Date.now() - startedAt;
      window.clearTimeout(timer);
    });

    toast.addEventListener('pointerleave', () => {
      toast.classList.remove('is-paused');
      startTimer(Math.max(700, remaining));
    });

    if (duration > 0) {
      startTimer(duration);
    } else {
      progress.remove();
    }

    return toast;
  }

  function notify(input, options) {
    return createToast(normalize(input, options));
  }

  notify.success = (message, options) => notify(message, { ...options, type: 'success' });
  notify.error = (message, options) => notify(message, { ...options, type: 'error' });
  notify.warning = (message, options) => notify(message, { ...options, type: 'warning' });
  notify.info = (message, options) => notify(message, { ...options, type: 'info' });

  window.notify = notify;
  window.toast = notify;

  document.addEventListener('DOMContentLoaded', () => {
    ensureRoot();

    const payload = document.getElementById('bdgigs-notify-payload');
    if (!payload?.textContent) {
      return;
    }

    try {
      JSON.parse(payload.textContent).forEach((item, index) => {
        window.setTimeout(() => notify(item), index * 120);
      });
    } catch (error) {
      console.warn('Unable to parse notification payload.', error);
    }
  });
})();
