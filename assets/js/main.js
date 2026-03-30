/* NewCareer – main.js */
(function () {
  'use strict';

  // ── Mobile navigation toggle ─────────────────────────────────────────────
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      const expanded = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!expanded));
      navLinks.classList.toggle('open', !expanded);
    });

    // Close nav when any link is clicked
    navLinks.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  // ── Auto-dismiss flash messages ──────────────────────────────────────────
  const flash = document.getElementById('flash-message');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity .5s ease';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 500);
    }, 4500);
  }

  // ── Password visibility toggle ───────────────────────────────────────────
  document.querySelectorAll('.btn-toggle-pw').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const input    = document.getElementById(targetId);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type        = isPassword ? 'text' : 'password';
      btn.textContent   = isPassword ? '🙈' : '👁';
      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
  });

  // ── Registration form – live password match indicator ───────────────────
  const pwInput    = document.getElementById('password');
  const pwConfirm  = document.getElementById('password_confirm');

  if (pwInput && pwConfirm) {
    function checkPasswordMatch() {
      if (pwConfirm.value.length === 0) {
        pwConfirm.style.borderColor = '';
        return;
      }
      const match = pwInput.value === pwConfirm.value;
      pwConfirm.style.borderColor = match ? '#16a34a' : '#dc2626';
    }

    pwInput.addEventListener('input',   checkPasswordMatch);
    pwConfirm.addEventListener('input', checkPasswordMatch);
  }

  // ── Registration form – live password strength hint ──────────────────────
  if (pwInput) {
    const hint = pwInput.closest('.form-group')?.querySelector('.form-hint');

    pwInput.addEventListener('input', () => {
      if (!hint) return;
      const v   = pwInput.value;
      const len = v.length >= 8;
      const uc  = /[A-Z]/.test(v);
      const num = /[0-9]/.test(v);

      if (!len)       { hint.textContent = 'At least 8 characters required.'; hint.style.color = '#dc2626'; }
      else if (!uc)   { hint.textContent = 'Add at least one uppercase letter.'; hint.style.color = '#d97706'; }
      else if (!num)  { hint.textContent = 'Add at least one number.'; hint.style.color = '#d97706'; }
      else            { hint.textContent = '✓ Strong password!'; hint.style.color = '#16a34a'; }
    });
  }

  // ── Generic form validation feedback ─────────────────────────────────────
  document.querySelectorAll('form[novalidate]').forEach(form => {
    form.addEventListener('submit', e => {
      const invalids = form.querySelectorAll('[required]');
      let first = null;

      invalids.forEach(el => {
        if (!el.value.trim()) {
          el.style.borderColor = '#dc2626';
          if (!first) first = el;
        } else {
          el.style.borderColor = '';
        }
      });

      if (first) {
        e.preventDefault();
        first.focus();
      }
    });
  });

  // ── Job search – client-side keyword highlight ───────────────────────────
  function highlightKeyword(container, keyword) {
    if (!keyword || keyword.length < 2) return;
    const items = container.querySelectorAll('.job-title a, .job-company, .job-excerpt');
    const re    = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    items.forEach(el => {
      el.innerHTML = el.textContent.replace(re, '<mark>$1</mark>');
    });
  }

  const jobList    = document.querySelector('.job-list');
  const searchForm = document.getElementById('jobSearchForm');
  if (jobList && searchForm) {
    const kw = searchForm.querySelector('[name="keyword"]');
    if (kw && kw.value.length >= 2) {
      highlightKeyword(jobList, kw.value);
    }
  }

  // ── Confirm dangerous actions (data-confirm attribute) ───────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // ── Table row click-through (data-href attribute) ─────────────────────────
  document.querySelectorAll('tr[data-href]').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', e => {
      if (!e.target.closest('a, button, input, select')) {
        window.location.href = row.dataset.href;
      }
    });
  });

})();
