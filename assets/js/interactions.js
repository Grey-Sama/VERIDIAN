(() => {
  'use strict';
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const compact = window.matchMedia('(max-width: 760px)');

  // Real links and native disclosures remain usable if JavaScript is unavailable.
  document.querySelectorAll('[data-menu-target]').forEach(button => {
    const nav = document.getElementById(button.dataset.menuTarget);
    if (!nav) return;
    const container = button.parentElement;
    container.classList.add('has-menu');
    const setOpen = open => {
      nav.classList.toggle('menu-open', open);
      button.setAttribute('aria-expanded', String(!compact.matches || open));
      button.setAttribute('aria-label', open && compact.matches ? 'Close Navigation' : 'Open Navigation');
    };
    setOpen(false);
    button.addEventListener('click', () => setOpen(!nav.classList.contains('menu-open')));
    nav.addEventListener('click', event => {
      if (event.target.closest('a') && compact.matches) setOpen(false);
    });
    container.addEventListener('keydown', event => {
      if (event.key === 'Escape' && compact.matches && nav.classList.contains('menu-open')) {
        setOpen(false);
        button.focus();
      }
    });
    document.addEventListener('click', event => {
      if (compact.matches && !container.contains(event.target)) setOpen(false);
    });
    compact.addEventListener('change', () => setOpen(false));
  });

  document.querySelectorAll('.app-sidebar nav a.active').forEach(link => link.setAttribute('aria-current', 'page'));

  if ('IntersectionObserver' in window && !reducedMotion.matches) {
    const targets = document.querySelectorAll('.section, .closing, .auth-form, .welcome, .page-heading, .stat, .panel, .gentle-banner');
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.remove('is-waiting');
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0, rootMargin: '0px 0px -24px 0px' });
    document.documentElement.classList.add('motion-ready');
    targets.forEach((target, index) => {
      target.classList.add('reveal');
      // Keep near-viewport content immediately readable, including anchor targets.
      if (target.getBoundingClientRect().top < window.innerHeight) {
        target.classList.add('is-visible');
        if (target.animate) target.animate([{ opacity: .3, transform: 'translateY(12px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 450, easing: 'ease-out' });
      } else {
        target.style.setProperty('--reveal-delay', `${Math.min(index % 3, 2) * 55}ms`);
        target.classList.add('is-waiting');
        observer.observe(target);
      }
    });
    const revealAll = () => {
      targets.forEach(target => target.classList.remove('is-waiting'));
      observer.disconnect();
    };
    reducedMotion.addEventListener('change', event => { if (event.matches) revealAll(); });
    window.addEventListener('beforeprint', revealAll);
    document.addEventListener('focusin', event => event.target.closest('.reveal')?.classList.remove('is-waiting'));
  }

  const sectionLinks = [...document.querySelectorAll('.hero header nav a[href^="#"]')];
  const sections = sectionLinks.map(link => document.getElementById(link.hash.slice(1))).filter(Boolean);
  if ('IntersectionObserver' in window && sections.length) {
    const observer = new IntersectionObserver(entries => {
      const entry = entries.find(item => item.isIntersecting);
      if (!entry) return;
      sectionLinks.forEach(link => {
        if (link.hash === `#${entry.target.id}`) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    }, { rootMargin: '-10% 0px -45% 0px', threshold: 0 });
    sections.forEach(section => observer.observe(section));
  }

  document.querySelectorAll('details').forEach(details => {
    details.addEventListener('toggle', () => {
      if (!details.open || reducedMotion.matches) return;
      const paragraph = details.querySelector('p');
      if (paragraph?.animate) paragraph.animate([{ opacity: 0, transform: 'translateY(-5px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 220, easing: 'ease-out' });
    });
  });

  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', event => {
      if (event.defaultPrevented) return;
      if(form.dataset.confirmReset && !window.confirm('Reset the password for ' + form.dataset.confirmReset + '? Their current sign-ins will end.')){event.preventDefault();return;}
      if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
      form.dataset.submitting = 'true';
      form.setAttribute('aria-busy', 'true');
      const button = event.submitter;
      if (button) {
        button.dataset.originalText = button.textContent;
        button.classList.add('is-pending');
        // Keep successful controls enabled so approval decisions reach PHP.
        button.textContent = 'Please wait…';
      }
    });
  });
  window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting]').forEach(form => {
      delete form.dataset.submitting;
      form.removeAttribute('aria-busy');
      form.querySelectorAll('[data-original-text]').forEach(button => {
        button.textContent = button.dataset.originalText;
        delete button.dataset.originalText;
        button.classList.remove('is-pending');
      });
    });
  });
})();
