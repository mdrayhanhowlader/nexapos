// ─────────────────────────────────────────────
//  NexaPOS — Product Loading & Grid Rendering
// ─────────────────────────────────────────────

const POSProducts = {

  activeCatId: null,
  searchTimer: null,
  _catsLoaded: false,

  // ── Load categories ────────────────────────
  async loadCategories() {
    if (this._catsLoaded) return;
    this._catsLoaded = true;
    const res = await POS.get(`${POS.API}?module=products&action=categories`);
    const cats = res.data || [];
    const tabs = document.getElementById('catTabs');
    if (!tabs) return;
    // Remove old category chips (keep first 'All Products' chip)
    const existing = tabs.querySelectorAll('.cat-chip:not([data-id="all"])');
    existing.forEach(e => e.remove());
    cats.forEach(c => {
      const el = document.createElement('div');
      el.className = 'cat-chip';
      el.textContent = c.name;
      el.onclick = () => this.filterByCat(c.id, el);
      tabs.appendChild(el);
    });
  },

  // ── Filter by category ─────────────────────
  filterByCat(catId, el) {
    document.querySelectorAll('.cat-chip').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    this.activeCatId = catId;
    const q = document.getElementById('posSearch')?.value || '';
    this.load(catId, q);
  },

  // ── Search debounce ────────────────────────
  onSearch(q) {
    clearTimeout(this.searchTimer);
    this.searchTimer = setTimeout(() => this.load(this.activeCatId, q), 300);
  },

  _loading: false,

  // ── Load products ──────────────────────────
  async load(catId = null, search = '') {
    if (this._loading) return;
    this._loading = true;
    const grid = document.getElementById('productGrid');
    if (!grid) { this._loading = false; return; }
    grid.innerHTML = `<div class="grid-loading"><div class="spin"></div> Loading...</div>`;

    let url = `${POS.API}?module=products&action=list&per_page=80&status=active`;
    if (catId)  url += `&category=${catId}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;

    const res = await POS.get(url);
    POS.allProducts = res.data?.products || [];
    this.render(POS.allProducts);
    this._loading = false;
  },

  // ── Render grid ────────────────────────────
  render(products) {
    const grid = document.getElementById('productGrid');
    if (!grid) return;

    if (!products.length) {
      grid.innerHTML = `
        <div class="grid-empty">
          <svg viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg>
          <p>No products found</p>
        </div>`;
      return;
    }

    grid.innerHTML = products.map(p => this.cardHTML(p)).join('');
  },

  // ── Product card HTML ──────────────────────
  cardHTML(p) {
    const stock    = parseFloat(p.stock || 0);
    const outClass = stock <= 0 && p.track_stock ? ' out-of-stock' : '';
    const stkClass = stock <= 0 ? 'stk-zero' : stock <= p.stock_alert_qty ? 'stk-low' : 'stk-ok';
    const stkLabel = stock <= 0 ? 'Out' : stock;
    const img      = p.image
      ? `<img src="/nexapos/public/uploads/products/${p.image}" alt="${p.name}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`+`<svg style="display:none" viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg>`
      : `<svg viewBox="0 0 24 24"><path d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.72V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.72c.57-.38 1-.99 1-1.71V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm3-8H6V4h12v2z"/></svg>`;
    const stockTag = p.track_stock
      ? `<span class="stk-tag ${stkClass}">${stkLabel}</span>`
      : '';

    return `
      <div class="prod-card${outClass}" onclick="POSCart.addById(${p.id})">
        <div class="prod-img">${img}${stockTag}</div>
        <div class="prod-info">
          <div class="prod-name">${p.name}</div>
          <div class="prod-price">${POS.fmt(p.selling_price || p.price)}</div>
          ${p.sku ? `<div class="prod-sku">${p.sku}</div>` : ''}
        </div>
      </div>`;
  },
};
