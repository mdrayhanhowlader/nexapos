// ─────────────────────────────────────────────
//  NexaPOS — Offline Mode (IndexedDB + Sync)
// ─────────────────────────────────────────────

const POSOffline = {

  DB_NAME:    'nexapos_offline',
  DB_VERSION: 1,
  _db:        null,
  _online:    true,          // optimistic default; real check runs on init
  _pingUrl:   null,          // set after POS.API is available

  // ── Init ───────────────────────────────────
  async init() {
    this._pingUrl = `${POS.API}?module=pos&action=ping`;
    this._db = await this._openDB();
    this._watchConnectivity();
    this._updatePendingBadge();

    // Real server check first — then cache + sync
    await this._checkServer();
    if (this._online) {
      await this._cacheProducts();
      await this._cachePaymentMethods();
      setTimeout(() => this.syncPending(), 2000);
    }
    this._updateBadge();

    // Re-check every 30 s so stale offline state self-heals
    setInterval(() => this._checkServer(), 30_000);
  },

  // ── IndexedDB setup ────────────────────────
  _openDB() {
    return new Promise((resolve, reject) => {
      const req = indexedDB.open(this.DB_NAME, this.DB_VERSION);
      req.onupgradeneeded = e => {
        const db = e.target.result;
        // Pending orders waiting to sync
        if (!db.objectStoreNames.contains('pending_orders')) {
          const s = db.createObjectStore('pending_orders', { keyPath: 'local_id', autoIncrement: true });
          s.createIndex('created_at', 'created_at');
        }
        // Cached products for offline search
        if (!db.objectStoreNames.contains('products_cache')) {
          db.createObjectStore('products_cache', { keyPath: 'id' });
        }
        // Cached payment methods
        if (!db.objectStoreNames.contains('payment_methods_cache')) {
          db.createObjectStore('payment_methods_cache', { keyPath: 'id' });
        }
      };
      req.onsuccess = e => resolve(e.target.result);
      req.onerror   = e => reject(e.target.error);
    });
  },

  _store(name, mode = 'readonly') {
    return this._db.transaction(name, mode).objectStore(name);
  },

  _all(storeName) {
    return new Promise((resolve, reject) => {
      const req = this._store(storeName).getAll();
      req.onsuccess = e => resolve(e.target.result);
      req.onerror   = e => reject(e.target.error);
    });
  },

  _put(storeName, record) {
    return new Promise((resolve, reject) => {
      const req = this._store(storeName, 'readwrite').put(record);
      req.onsuccess = e => resolve(e.target.result);
      req.onerror   = e => reject(e.target.error);
    });
  },

  _delete(storeName, key) {
    return new Promise((resolve, reject) => {
      const req = this._store(storeName, 'readwrite').delete(key);
      req.onsuccess = () => resolve();
      req.onerror   = e => reject(e.target.error);
    });
  },

  // ── Real server ping ───────────────────────
  // Returns true if server responded, false otherwise.
  async _checkServer() {
    try {
      const ctrl = new AbortController();
      const tid  = setTimeout(() => ctrl.abort(), 5000); // 5 s timeout
      const res  = await fetch(this._pingUrl + '&_t=' + Date.now(), {
        method: 'GET',
        cache:  'no-store',
        signal: ctrl.signal,
      });
      clearTimeout(tid);
      const wasOffline = !this._online;
      this._online = res.ok;
      this._updateBadge();
      if (wasOffline && this._online) {
        POS.toast('Connection restored — syncing pending orders…', 'success');
        this._cacheProducts();
        this._cachePaymentMethods();
        setTimeout(() => this.syncPending(), 500);
      }
      return this._online;
    } catch (_) {
      const wasOnline = this._online;
      this._online = false;
      this._updateBadge();
      if (wasOnline) {
        POS.toast('You are offline — sales will be saved locally', 'warning');
      }
      return false;
    }
  },

  // ── Connectivity watch ─────────────────────
  // Browser events are a hint — always confirm with a real ping.
  _watchConnectivity() {
    window.addEventListener('online',  () => this._checkServer());
    window.addEventListener('offline', () => {
      this._online = false;
      this._updateBadge();
      POS.toast('You are offline — sales will be saved locally', 'warning');
    });
  },

  _updateBadge() {
    const badge = document.getElementById('offlineBadge');
    if (!badge) return;
    if (this._online) {
      badge.style.display = 'none';
    } else {
      badge.style.display = 'flex';
    }
  },

  async _updatePendingBadge() {
    const items = await this._all('pending_orders');
    const count = items.length;
    const badge = document.getElementById('pendingCount');
    const wrap  = document.getElementById('syncWrap');
    if (badge) badge.textContent = count;
    if (wrap)  wrap.style.display = count > 0 ? 'flex' : 'none';
  },

  // ── Product cache ──────────────────────────
  async _cacheProducts() {
    if (!this._online) return;
    try {
      const res = await fetch(`${POS.API}?module=pos&action=lookup_product&q=`);
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        const tx = this._db.transaction('products_cache', 'readwrite');
        const s  = tx.objectStore('products_cache');
        s.clear();
        data.data.forEach(p => s.put(p));
      }
    } catch (_) {}
  },

  async _cachePaymentMethods() {
    if (!this._online) return;
    try {
      const res = await fetch(`${POS.API}?module=pos&action=get_payment_methods`);
      const data = await res.json();
      if (data.success && Array.isArray(data.data)) {
        const tx = this._db.transaction('payment_methods_cache', 'readwrite');
        const s  = tx.objectStore('payment_methods_cache');
        s.clear();
        data.data.forEach(m => s.put(m));
      }
    } catch (_) {}
  },

  // ── Offline product search ─────────────────
  async searchOffline(query) {
    const all = await this._all('products_cache');
    const q   = query.toLowerCase();
    return all.filter(p =>
      (p.name    || '').toLowerCase().includes(q) ||
      (p.sku     || '').toLowerCase().includes(q) ||
      (p.barcode || '').toLowerCase().includes(q)
    ).slice(0, 20);
  },

  // ── Save order to offline queue ────────────
  async saveOrder(payload) {
    const record = {
      created_at:  new Date().toISOString(),
      synced:      false,
      payload,
      // Local invoice for receipt display
      local_invoice: 'OFF-' + Date.now(),
    };
    const localId = await this._put('pending_orders', record);
    record.local_id = localId;
    await this._updatePendingBadge();
    return record;
  },

  // ── Sync pending to server ─────────────────
  async syncPending() {
    // Always do a real server check before syncing — don't trust stale _online flag
    const reachable = await this._checkServer();
    if (!reachable) {
      POS.toast('Server unreachable — sync aborted', 'warning');
      return;
    }
    const pending = await this._all('pending_orders');
    if (!pending.length) return;

    const syncBtn = document.getElementById('syncBtn');
    if (syncBtn) {
      syncBtn.disabled  = true;
      syncBtn.innerHTML = '<div class="spin"></div> Syncing…';
    }

    let synced = 0, failed = 0;

    for (const order of pending) {
      try {
        const res = await POS.post(
          `${POS.API}?module=pos&action=checkout`,
          order.payload
        );
        if (res.success) {
          await this._delete('pending_orders', order.local_id);
          synced++;
        } else {
          failed++;
        }
      } catch (_) {
        failed++;
      }
    }

    await this._updatePendingBadge();

    if (syncBtn) {
      syncBtn.disabled  = false;
      syncBtn.innerHTML = `
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:14px;height:14px">
          <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0020 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 004 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
        </svg>
        Sync`;
    }

    if (synced > 0) POS.toast(`${synced} offline order(s) synced`, 'success');
    if (failed  > 0) POS.toast(`${failed} order(s) failed to sync — will retry`, 'warning');
  },

  // ── Show offline pending orders list ───────
  async showPendingModal() {
    const pending = await this._all('pending_orders');
    const modal   = document.getElementById('offlinePendingModal');
    const list    = document.getElementById('offlinePendingList');
    if (!modal || !list) return;

    list.innerHTML = pending.length === 0
      ? '<p style="text-align:center;color:var(--text2);padding:20px">No pending orders</p>'
      : pending.map(o => {
          const items  = (o.payload.items || []).length;
          const total  = (o.payload.items || []).reduce((s, i) => s + (i.unit_price * i.quantity), 0);
          const dt     = new Date(o.created_at).toLocaleString();
          return `
            <div style="display:flex;justify-content:space-between;align-items:center;
                        padding:10px 14px;border-bottom:1px solid var(--border);gap:10px">
              <div>
                <div style="font-weight:600;font-size:14px">${o.local_invoice}</div>
                <div style="font-size:12px;color:var(--text2)">${dt} · ${items} item(s)</div>
              </div>
              <div style="font-weight:700;color:var(--accent)">${POS.fmt(total)}</div>
            </div>`;
        }).join('');

    modal.style.display = 'flex';
  },

  isOnline() { return this._online; },
};
