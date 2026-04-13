async function api(url) {
  try {
    const r = await fetch(url);
    return await r.json();
  } catch(e) {
    console.error('API error:', e);
    return { success: false, message: 'Network error' };
  }
}

async function apiFD(url, formData) {
  try {
    const r = await fetch(url, { method: 'POST', body: formData });
    return await r.json();
  } catch(e) {
    console.error('API error:', e);
    return { success: false, message: 'Network error' };
  }
}

async function apiJSON(url, data) {
  try {
    const r = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return await r.json();
  } catch(e) {
    console.error('API error:', e);
    return { success: false, message: 'Network error' };
  }
}

const toastIcons = {
  success: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>',
  error:   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
  info:    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
  warning: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
};

function toast(message, type = 'info', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const el = document.createElement('div');
  el.className = `toast ${type}`;
  el.innerHTML = (toastIcons[type] || '') + `<span>${message}</span>`;
  container.appendChild(el);
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(20px)';
    el.style.transition = 'all .3s';
    setTimeout(() => el.remove(), 300);
  }, duration);
}

function debounce(fn, delay) {
  let t;
  return function(...args) {
    clearTimeout(t);
    t = setTimeout(() => fn.apply(this, args), delay);
  };
}

// containerId is optional — defaults to 'pagination' for backward compat
// pageVar is optional — variable name to set (defaults to 'currentPage')
function renderPagination(pg, fnName, containerId, pageVar) {
  const elId = containerId || 'pagination';
  const pVar = pageVar || 'currentPage';
  const el   = document.getElementById(elId);
  if (!el || !pg) return;
  const { total, current_page, total_pages, per_page } = pg;
  if (!total_pages || total_pages < 1) { el.style.display='none'; return; }
  const from = ((current_page - 1) * per_page) + 1;
  const to   = Math.min(current_page * per_page, total);
  el.innerHTML = `
    <span>Showing ${from}–${to} of ${total}</span>
    <div class="page-btns">
      <button class="page-btn" onclick="${pVar}=${current_page-1};${fnName}()" ${current_page<=1?'disabled':''}>‹</button>
      ${Array.from({length:Math.min(5,total_pages)},(_,i)=>{
        const p = Math.max(1, Math.min(current_page-2, total_pages-4)) + i;
        return p<=total_pages ? `<button class="page-btn ${p===current_page?'active':''}" onclick="${pVar}=${p};${fnName}()">${p}</button>` : '';
      }).join('')}
      <button class="page-btn" onclick="${pVar}=${current_page+1};${fnName}()" ${current_page>=total_pages?'disabled':''}>›</button>
    </div>`;
  el.style.display = total_pages > 1 ? 'flex' : 'none';
}

function openSidebar() {
  document.getElementById('sidebar')?.classList.add('open');
  document.getElementById('overlay')?.classList.add('show');
}

function closeSidebar() {
  document.getElementById('sidebar')?.classList.remove('open');
  document.getElementById('overlay')?.classList.remove('show');
}
