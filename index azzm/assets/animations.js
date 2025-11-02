/* ===== Animations Layer – Azm Al Enjaz ===== */
(() => {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

  function applyVars(el) {
    const delay = parseInt(el.dataset.delay || '0', 10);
    const dur   = parseInt(el.dataset.duration || '700', 10);
    const dist  = parseInt(el.dataset.distance || '0', 10);

    el.style.setProperty('--aa-delay', `${clamp(delay, 0, 5000)}ms`);
    el.style.setProperty('--aa-duration', `${clamp(dur, 120, 5000)}ms`);
    if (dist) el.style.setProperty('--aa-distance', `${clamp(dist, 0, 200)}px`);
  }

  function applyStagger(container) {
    const step = parseInt(container.dataset.stagger || '100', 10);
    const children = Array.from(container.children);
    children.forEach((child, i) => {
      const baseDelay = parseInt(container.dataset.delay || '0', 10);
      const extra     = parseInt(child.dataset.delay || '0', 10);
      const total = baseDelay + i * step + extra;
      child.style.setProperty('--aa-delay', `${clamp(total, 0, 8000)}ms`);
      if (container.dataset.duration && !child.dataset.duration) {
        child.style.setProperty('--aa-duration', `${clamp(parseInt(container.dataset.duration, 10), 120, 5000)}ms`);
      }
      if (container.dataset.distance && !child.dataset.distance) {
        child.style.setProperty('--aa-distance', `${clamp(parseInt(container.dataset.distance, 10), 0, 200)}px`);
      }
    });
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      const el = entry.target;
      if (entry.isIntersecting) {
        applyVars(el);
        el.classList.add('is-visible');
        io.unobserve(el);
      }
    });
  }, { root: null, rootMargin: '0px 0px -10% 0px', threshold: 0.12 });

  // راقب عناصر منفردة
  document.querySelectorAll('[data-anim]').forEach((el) => io.observe(el));

  // راقب الحاويات المتتابعة
  document.querySelectorAll('[data-stagger]').forEach((wrap) => {
    applyStagger(wrap);
    const type = wrap.dataset.anim || 'fade-up';
    Array.from(wrap.children).forEach((child) => {
      if (!child.hasAttribute('data-anim')) child.setAttribute('data-anim', type);
      io.observe(child);
    });
  });

  // عناصر الهيرو تظهر بعد التحميل
  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-hero]').forEach((el, idx) => {
      const base = parseInt(el.dataset.delay || '0', 10);
      const d = base + idx * 120;
      el.style.setProperty('--aa-delay', `${d}ms`);
      requestAnimationFrame(() => el.classList.add('is-visible'));
    });
  });
})();
