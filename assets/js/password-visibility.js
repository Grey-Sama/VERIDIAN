(() => {
  'use strict';
  const eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-eye-slash" d="m3 3 18 18"/></svg>';
  document.querySelectorAll('input[type="password"]').forEach((input, index) => {
    const wrapper = document.createElement('span');
    wrapper.className = 'password-field';
    input.before(wrapper);
    wrapper.append(input);
    if (!input.id) input.id = `password-field-${index}`;
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.innerHTML = eye;
    button.setAttribute('aria-controls', input.id);
    button.setAttribute('aria-pressed', 'false');
    const label = input.closest('label');
    const fieldName = label ? label.textContent.trim() : 'password';
    const update = (visible) => {
      input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-pressed', String(visible));
      button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${fieldName}`);
      button.title = `${visible ? 'Hide' : 'Show'} ${fieldName}`;
    };
    update(false);
    button.addEventListener('click', () => update(input.type === 'password'));
    wrapper.append(button);
    window.addEventListener('pagehide', () => update(false));
    if (input.form) input.form.addEventListener('reset', () => update(false));
  });
})();

