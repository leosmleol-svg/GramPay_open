(() => {
  'use strict';
  const pages = [...document.querySelectorAll('.doc-page')];
  const navLinks = [...document.querySelectorAll('.sidebar nav a')];
  const feedback = document.querySelector('#feedback');
  const search = document.querySelector('#search');
  const sidebar = document.querySelector('#sidebar');
  const menu = document.querySelector('#menu');
  const header = document.querySelector('.topbar');
  const updateHeaderHeight = () => document.documentElement.style.setProperty('--header-height', header.getBoundingClientRect().height + 'px');
  updateHeaderHeight();
  if (typeof ResizeObserver !== 'undefined') new ResizeObserver(updateHeaderHeight).observe(header);
  else window.addEventListener('resize', updateHeaderHeight);
  let timer;
  const notify = message => {
    clearTimeout(timer);
    feedback.textContent = message;
    timer = setTimeout(() => { feedback.textContent = ''; }, 4000);
  };
  const copy = async text => {
    try { await navigator.clipboard.writeText(text); notify('Скопировано'); }
    catch (_) { notify('Копирование недоступно. Выделите и скопируйте текст вручную.'); }
  };
  const closeMenu = () => { sidebar.classList.remove('open'); menu.setAttribute('aria-expanded', 'false'); };
  function showPage(scroll = false) {
    let id;
    try { id = decodeURIComponent(location.hash.slice(1)); } catch (_) { id = ''; }
    if (!pages.some(page => page.id === id)) id = 'overview';
    pages.forEach(page => { page.hidden = page.id !== id; });
    navLinks.forEach(link => {
      if (link.hash === '#' + id) link.setAttribute('aria-current', 'page');
      else link.removeAttribute('aria-current');
    });
    document.title = document.getElementById(id).dataset.title + ' — GramPay API';
    closeMenu();
    if (scroll) {
      window.scrollTo({top:0, behavior:'instant'});
      document.querySelector('#main').focus({preventScroll:true});
    }
  }
  showPage();
  window.addEventListener('hashchange', () => showPage(true));
  search.addEventListener('input', () => {
    const q = search.value.trim().toLocaleLowerCase('ru');
    let count = 0;
    navLinks.forEach(link => {
      link.hidden = !link.dataset.search.toLocaleLowerCase('ru').includes(q);
      if (!link.hidden) count++;
    });
    document.querySelectorAll('.nav-group').forEach(group => {
      group.hidden = ![...group.querySelectorAll('a')].some(a => !a.hidden);
    });
    document.querySelector('#search-empty').hidden = count > 0;
  });
  menu.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    menu.setAttribute('aria-expanded', String(open));
    if (open) search.focus();
  });
  document.querySelector('#theme').addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('grampay_theme', theme); } catch (_) {}
  });
  document.addEventListener('keydown', event => {
    if (event.key === '/' && !event.ctrlKey && !event.metaKey && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) {
      event.preventDefault();
      if (window.matchMedia('(max-width:760px)').matches) { sidebar.classList.add('open'); menu.setAttribute('aria-expanded', 'true'); }
      search.focus();
    }
    if (event.key === 'Escape' && sidebar.classList.contains('open')) { closeMenu(); menu.focus(); }
  });
  document.querySelector('#copy-link').addEventListener('click', () => {
    const active = pages.find(page => !page.hidden);
    const url = new URL(location.href);
    url.hash = active.id;
    copy(url.href);
  });
  document.querySelectorAll('.copy-code').forEach(button => {
    button.addEventListener('click', () => copy(button.parentElement.querySelector('code').textContent));
  });
  document.querySelectorAll('.language').forEach(select => {
    select.addEventListener('change', () => {
      select.closest('.samples').querySelectorAll('.sample').forEach(sample => { sample.hidden = sample.dataset.lang !== select.value; });
    });
  });
  window.matchMedia('(min-width:761px)').addEventListener('change', closeMenu);
})();
