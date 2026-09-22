/* Progressive enhancement only: existing forms keep their server actions. */
(() => {
  window.gpCopyMessage = (button, message) => {
    let feedback = button.parentElement.querySelector('.gp-copy-feedback');
    if (!feedback) {
      feedback = document.createElement('p');
      feedback.className = 'gp-copy-feedback';
      feedback.setAttribute('role', 'status');
      button.parentElement.after(feedback);
    }
    feedback.textContent = message;
  };
  document.querySelectorAll('label').forEach((label, index) => {
    if (label.htmlFor || label.querySelector('input,select,textarea')) return;
    let control = label.nextElementSibling;
    if (!control?.matches('input,select,textarea')) {
      control = control?.querySelector('input,select,textarea');
    }
    if (!control) return;
    if (!control.id) control.id = `gp-field-${index}`;
    label.htmlFor = control.id;
  });
  document.querySelectorAll('.gp-auth input[type="password"]').forEach(input => {
    const wrapper = document.createElement('div');
    wrapper.className = 'gp-password';
    input.before(wrapper);
    wrapper.append(input);
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'gp-password-toggle';
    button.textContent = 'Показать';
    button.setAttribute('aria-label', 'Показать пароль');
    button.setAttribute('aria-controls', input.id);
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', () => {
      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      button.textContent = reveal ? 'Скрыть' : 'Показать';
      button.setAttribute('aria-label', reveal ? 'Скрыть пароль' : 'Показать пароль');
      button.setAttribute('aria-pressed', String(reveal));
    });
    wrapper.append(button);
  });
  document.querySelectorAll('.gp-docs table, .gp-panel table').forEach(table => {
    const wrapper = document.createElement('div');
    wrapper.className = 'gp-table-scroll';
    wrapper.tabIndex = 0;
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', 'Таблица с горизонтальной прокруткой');
    table.before(wrapper);
    wrapper.append(table);
  });
  document.querySelectorAll('.side-link').forEach(link => {
    link.setAttribute('aria-label', link.textContent.trim());
  });
  document.querySelectorAll('.icon-button[title]').forEach(button => {
    button.setAttribute('aria-label', button.title);
  });
  const menu = document.querySelector('.menu-btn');
  const links = document.querySelector('#main-nav');
  const closeMenu = () => {
    if (!menu || !links) return;
    links.dataset.open = '0';
    menu.setAttribute('aria-expanded', 'false');
  };
  links?.addEventListener('click', event => {
    if (event.target.closest('a')) closeMenu();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && links?.dataset.open === '1') {
      closeMenu();
      menu.focus();
    }
  });
  if (menu) window.matchMedia('(min-width:761px)').addEventListener('change', closeMenu);
})();
