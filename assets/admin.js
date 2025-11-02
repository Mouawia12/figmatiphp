// === Sidebar toggle (موجود سابقًا) ===
// ... كودك الحالي لفتح/إغلاق السايدبار ...

// === Theme Toggle ===
(function(){
  const root = document.documentElement;
  const saved = localStorage.getItem('crosing-theme');
  if(saved === 'dark' || saved === 'light') root.setAttribute('data-theme', saved);

  const btn = document.getElementById('themeToggle');
  const applyIcon = () => {
    if(!btn) return;
    const dark = root.getAttribute('data-theme') === 'dark';
    btn.textContent = dark ? '🌙' : '☀️';
  };
  applyIcon();

  if(btn){
    btn.addEventListener('click', ()=>{
      const dark = root.getAttribute('data-theme') === 'dark';
      const next = dark ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('crosing-theme', next);
      applyIcon();
    });
  }
  (function(){
  const root = document.documentElement;
  // تفعيل تلقائي أول مرة حسب نظام المستخدم
  const saved = localStorage.getItem('crosing-theme');
  if (saved === 'dark' || saved === 'light') {
    root.setAttribute('data-theme', saved);
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    root.setAttribute('data-theme', 'dark');
  }

  const btn = document.getElementById('themeToggle');
  const setIcon = () => btn && (btn.textContent = root.getAttribute('data-theme') === 'dark' ? '🌙' : '☀️');
  setIcon();

  btn?.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('crosing-theme', next);
    setIcon();
  });
})();

