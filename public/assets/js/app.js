// JS dùng chung. Gửi request AJAX kèm CSRF token (dùng cho POS ở tuần 4):
//   postJSON(url, data).then(res => ...)
function postJSON(url, data) {
    const token = document.querySelector('input[name="_csrf"]')?.value ?? '';
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
        body: JSON.stringify(data),
    }).then(res => res.json());
}

// Form nhiều dòng (phiếu nhập kho...). Cách dùng:
//   <div data-repeater data-max="50">
//     <div data-repeater-rows> ...các dòng đã có, mỗi dòng có data-repeater-row... </div>
//     <template data-repeater-template> <div data-repeater-row> <input name="items[__i__][qty]"> ... <button type="button" data-repeater-remove>…</button> </div> </template>
//     <button type="button" data-repeater-add>Thêm dòng</button>
//   </div>
// Tên input dùng "__i__" làm chỗ giữ chỗ chỉ số. Chỉ số luôn được đánh lại 0,1,2… sau khi thêm/xóa
// nên khớp với lỗi "items.0.qty" do server trả về. Luôn giữ lại ít nhất 1 dòng.
function initRepeater(root) {
    const rows = root.querySelector('[data-repeater-rows]');
    const template = root.querySelector('template[data-repeater-template]');
    const addBtn = root.querySelector('[data-repeater-add]');
    const max = parseInt(root.dataset.max || '0', 10);

    const items = () => rows.querySelectorAll(':scope > [data-repeater-row]');

    function reindex() {
        items().forEach((row, i) => {
            row.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/^([^\[]+)\[(?:\d+|__i__)\]/, `$1[${i}]`);
            });
            row.querySelectorAll('[data-repeater-index]').forEach(el => { el.textContent = i + 1; });
        });
        const count = items().length;
        if (addBtn) addBtn.disabled = max > 0 && count >= max;
        root.querySelectorAll('[data-repeater-remove]').forEach(btn => { btn.disabled = count <= 1; });
    }

    addBtn?.addEventListener('click', () => {
        if (max > 0 && items().length >= max) return;
        rows.appendChild(template.content.cloneNode(true));
        reindex();
        items()[items().length - 1].querySelector('input, select')?.focus();
    });

    root.addEventListener('click', e => {
        const btn = e.target.closest('[data-repeater-remove]');
        if (!btn || items().length <= 1) return;
        btn.closest('[data-repeater-row]').remove();
        reindex();
    });

    reindex();
}
document.querySelectorAll('[data-repeater]').forEach(initRepeater);

// Tổng giá trị nhập trên form phiếu nhập: Σ (SL × giá nhập), tự cập nhật khi gõ/thêm/xóa dòng.
function initReceiptTotal(form) {
    const out = form.querySelector('[data-receipt-total]');
    if (!out) return;
    const update = () => {
        let total = 0;
        form.querySelectorAll('[data-repeater-row]').forEach(row => {
            const qty = parseInt(row.querySelector('[data-line-qty]')?.value, 10);
            const cost = parseInt(row.querySelector('[data-line-cost]')?.value, 10);
            if (qty > 0 && cost >= 0) total += qty * cost;
        });
        out.textContent = total.toLocaleString('vi-VN') + ' ₫';
    };
    form.addEventListener('input', update);
    form.addEventListener('click', e => { if (e.target.closest('[data-repeater-remove], [data-repeater-add]')) setTimeout(update, 0); });
    update();
}
document.querySelectorAll('form').forEach(f => { if (f.querySelector('[data-receipt-total]')) initReceiptTotal(f); });

// Chống bấm lưu 2 lần: form có data-once chỉ gửi được 1 lần (nút lưu bị khóa sau khi gửi).
document.querySelectorAll('form[data-once]').forEach(form => {
    form.addEventListener('submit', e => {
        if (form.dataset.submitted === '1') { e.preventDefault(); return; }
        form.dataset.submitted = '1';
        setTimeout(() => form.querySelectorAll('button:not([type="button"])').forEach(b => { b.disabled = true; }), 0);
    });
});
window.addEventListener('pageshow', e => {   // quay lại bằng nút Back: cho gửi lại
    if (e.persisted) document.querySelectorAll('form[data-once]').forEach(f => {
        f.dataset.submitted = '';
        f.querySelectorAll('button').forEach(b => { b.disabled = false; });
    });
});

// ---- POS (Tuần 4) ----
// Logic giỏ hàng thuần (không đụng DOM) để dễ kiểm thử; phần giao diện nối ở Bước 2.
// Tiền là số nguyên (đồng). Server luôn tính lại giá/tổng; client chỉ gửi product_id + qty.
const PosCart = {
    /** items: [{id, name, price, qty, stock}] */
    add(items, product, qty = 1) {
        const line = items.find(i => i.id === product.id);
        const stock = Number(product.stock);
        if (line) { line.stock = stock; line.qty = Math.min(line.qty + qty, stock); }
        else if (stock > 0) items.push({ id: product.id, name: product.name, price: Number(product.sale_price), qty: Math.min(qty, stock), stock });
        return items;
    },
    setQty(items, id, qty) {
        const line = items.find(i => i.id === id);
        if (!line) return items;
        qty = Math.floor(Number(qty));
        if (!(qty > 0)) return PosCart.remove(items, id);
        line.qty = Math.min(qty, line.stock);
        return items;
    },
    remove(items, id) {
        const i = items.findIndex(l => l.id === id);
        if (i >= 0) items.splice(i, 1);
        return items;
    },
    subtotal: items => items.reduce((sum, i) => sum + i.price * i.qty, 0),
    /** Giảm giá bị ép về [0, tạm tính]. */
    total: (items, discount = 0) => PosCart.subtotal(items) - Math.min(Math.max(0, Math.floor(discount) || 0), PosCart.subtotal(items)),
    /** Tiền thối; âm nghĩa là khách đưa thiếu (chặn thanh toán). */
    change: (total, given) => Math.floor(given) - total,
    money: n => Math.round(n).toLocaleString('vi-VN') + ' ₫',
};

// Giao diện POS: tìm SP, giỏ hàng, khách, thanh toán. Mọi chữ hiển thị đều qua textContent (không innerHTML) để chống XSS.
function h(tag, props = {}, ...kids) {
    const n = document.createElement(tag);
    for (const [k, v] of Object.entries(props)) {
        if (k === 'class') n.className = v;
        else if (k.startsWith('on')) n.addEventListener(k.slice(2), v);
        else n.setAttribute(k, v);
    }
    n.append(...kids.flat().filter(x => x != null));
    return n;
}

function initPos(root) {
    const q = s => root.querySelector(s);
    const isAdmin = root.dataset.admin === '1';
    const el = {
        search: q('[data-pos-search]'), results: q('[data-pos-results]'), lines: q('[data-pos-lines]'),
        empty: q('[data-pos-empty]'), alert: q('[data-pos-alert]'), phone: q('[data-pos-phone]'),
        customer: q('[data-pos-customer]'), discount: q('[data-pos-discount]'), cashBox: q('[data-pos-cash-box]'),
        cash: q('[data-pos-cash]'), change: q('[data-pos-change]'), subtotal: q('[data-pos-subtotal]'),
        discountText: q('[data-pos-discount-text]'), total: q('[data-pos-total]'), pay: q('[data-pos-pay]'),
        cartCard: q('[data-pos-cart-card]'), done: q('[data-pos-done]'),
    };
    const newId = () => (crypto.randomUUID ? crypto.randomUUID() : Date.now().toString(16) + Math.random().toString(16).slice(2, 12));
    let items = [];
    let customer = null;      // khách đã tra thấy
    let busy = false;
    let requestId = newId();  // đổi sau mỗi lần bán thành công; server dùng để không bán trùng khi gửi lại
    let searchSeq = 0;

    const method = () => root.querySelector('[data-pos-method]:checked')?.value ?? '';
    const intVal = input => { const v = parseInt(input?.value, 10); return Number.isFinite(v) ? v : NaN; };
    function showAlert(text) { el.alert.textContent = text; el.alert.classList.toggle('d-none', !text); }

    // ---- Tìm sản phẩm ----
    async function fetchProducts(term) {
        const res = await fetch(root.dataset.searchUrl + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('search');
        return (await res.json()).items;
    }
    function renderResults(list, term) {
        el.results.replaceChildren();
        if (!list.length && term) el.results.append(h('div', { class: 'text-muted' }, 'Không tìm thấy sản phẩm.'));
        for (const p of list) {
            const out = Number(p.stock) <= 0;
            el.results.append(h('button', {
                type: 'button', class: 'list-group-item list-group-item-action d-flex justify-content-between align-items-center' + (out ? ' disabled' : ''),
                onclick: () => { if (!out) { addProduct(p); } },
            },
                h('span', {}, h('span', { class: 'fw-semibold' }, p.name), h('br'), h('small', { class: 'text-muted' }, p.sku + (p.barcode ? ' · ' + p.barcode : ''))),
                h('span', { class: 'text-end' }, h('span', { class: 'fw-semibold' }, PosCart.money(p.sale_price)), h('br'),
                    h('small', { class: out ? 'text-danger' : 'text-muted' }, out ? 'Hết hàng' : 'Còn ' + p.stock + ' ' + p.unit))));
        }
    }
    let timer;
    el.search.addEventListener('input', () => {
        clearTimeout(timer);
        const term = el.search.value.trim();
        const seq = ++searchSeq;
        if (!term) { renderResults([], ''); return; }
        timer = setTimeout(async () => {
            try { const list = await fetchProducts(term); if (seq === searchSeq) renderResults(list, term); }
            catch { if (seq === searchSeq) { el.results.replaceChildren(h('div', { class: 'text-danger' }, 'Không tìm được sản phẩm, thử lại.')); } }
        }, 250);
    });
    el.search.addEventListener('keydown', async e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const term = el.search.value.trim();
        if (!term) return;
        clearTimeout(timer);
        const seq = ++searchSeq;
        try {
            const list = await fetchProducts(term);
            if (seq !== searchSeq) return;
            const exact = list.find(p => p.barcode === term || p.sku.toLowerCase() === term.toLowerCase());
            if (exact && Number(exact.stock) > 0) addProduct(exact);
            else if (exact) { showAlert('"' + exact.name + '" đã hết hàng.'); renderResults(list, term); }
            else renderResults(list, term);
        } catch { showAlert('Không tìm được sản phẩm, thử lại.'); }
    });

    function addProduct(p) {
        showAlert('');
        const before = items.find(i => i.id === p.id)?.qty ?? 0;
        PosCart.add(items, p, 1);
        const line = items.find(i => i.id === p.id);
        if (line && line.qty === before) showAlert('"' + p.name + '" chỉ còn ' + p.stock + ' ' + p.unit + ', không thêm được nữa.');
        el.search.value = '';
        renderResults([], '');
        el.search.focus();
        render();
    }

    // ---- Giỏ hàng ----
    function lineInvalid(i) { return i.qty > i.stock; }
    function render() {
        el.lines.replaceChildren();
        for (const i of items) {
            const bad = lineInvalid(i);
            const qtyInput = h('input', { type: 'number', class: 'form-control form-control-sm text-center' + (bad ? ' is-invalid' : ''), min: '1', max: String(i.stock), step: '1', value: String(i.qty), 'aria-label': 'Số lượng ' + i.name,
                onchange: e => { PosCart.setQty(items, i.id, e.target.value); showAlert(''); render(); } });
            el.lines.append(h('tr', {},
                h('td', {}, h('div', { class: 'fw-semibold' }, i.name), h('small', { class: bad ? 'text-danger' : 'text-muted' }, bad ? (i.stock > 0 ? 'Chỉ còn ' + i.stock : 'Hết hàng') : PosCart.money(i.price))),
                h('td', {}, h('div', { class: 'input-group input-group-sm flex-nowrap' },
                    h('button', { type: 'button', class: 'btn btn-outline-secondary', 'aria-label': 'Giảm', onclick: () => { PosCart.setQty(items, i.id, i.qty - 1); render(); } }, '−'),
                    qtyInput,
                    h('button', { type: 'button', class: 'btn btn-outline-secondary', 'aria-label': 'Tăng', onclick: () => { PosCart.setQty(items, i.id, i.qty + 1); render(); } }, '+'))),
                h('td', { class: 'text-end' }, PosCart.money(i.price * i.qty)),
                h('td', {}, h('button', { type: 'button', class: 'btn btn-sm btn-outline-danger', 'aria-label': 'Xóa ' + i.name, onclick: () => { PosCart.remove(items, i.id); render(); } }, '×'))));
        }
        el.empty.classList.toggle('d-none', items.length > 0);
        updateTotals();
    }

    function discountValue() {
        if (!isAdmin) return { value: 0, ok: true };
        const raw = el.discount.value.trim();
        const v = raw === '' ? 0 : Number(raw);
        const ok = Number.isInteger(v) && v >= 0 && v <= PosCart.subtotal(items);
        el.discount.classList.toggle('is-invalid', !ok);
        return { value: ok ? v : 0, ok };
    }
    function updateTotals() {
        const sub = PosCart.subtotal(items);
        const d = discountValue();
        const total = PosCart.total(items, d.value);
        el.subtotal.textContent = PosCart.money(sub);
        el.discountText.textContent = d.value ? '− ' + PosCart.money(d.value) : PosCart.money(0);
        el.total.textContent = PosCart.money(total);

        const cashMode = method() === 'cash';
        el.cashBox.classList.toggle('d-none', !cashMode);
        let cashOk = true;
        if (cashMode) {
            const given = intVal(el.cash);
            const change = PosCart.change(total, given);
            cashOk = Number.isFinite(given) && change >= 0;
            el.change.className = 'form-text fw-semibold ' + (cashOk ? 'text-success' : 'text-danger');
            el.change.textContent = !Number.isFinite(given) ? 'Nhập tiền khách đưa.'
                : change >= 0 ? 'Tiền thối: ' + PosCart.money(change) : 'Còn thiếu: ' + PosCart.money(-change);
        }
        el.pay.disabled = busy || !items.length || items.some(lineInvalid) || !d.ok || total <= 0 || !cashOk;
    }

    el.discount?.addEventListener('input', updateTotals);
    el.cash.addEventListener('input', updateTotals);
    root.querySelectorAll('[data-pos-method]').forEach(r => r.addEventListener('change', updateTotals));
    q('[data-pos-cash-exact]').addEventListener('click', () => { el.cash.value = PosCart.total(items, discountValue().value); updateTotals(); });
    q('[data-pos-clear]').addEventListener('click', () => { items = []; showAlert(''); render(); el.search.focus(); });

    // ---- Khách hàng ----
    async function lookupCustomer() {
        const phone = el.phone.value.trim();
        customer = null;
        el.customer.className = 'form-text';
        if (!phone) { el.customer.textContent = ''; return; }
        try {
            const res = await (await fetch(root.dataset.customerUrl + '?phone=' + encodeURIComponent(phone), { headers: { Accept: 'application/json' } })).json();
            if (res.found) {
                customer = res;
                el.customer.className = 'form-text text-success';
                el.customer.textContent = (res.name || 'Khách hàng') + ' · ' + res.points + ' điểm';
            } else {
                el.customer.className = 'form-text text-danger';
                el.customer.textContent = 'Không tìm thấy khách hàng. Xóa SĐT để bán không tích điểm.';
            }
        } catch { el.customer.className = 'form-text text-danger'; el.customer.textContent = 'Không tra được khách hàng, thử lại.'; }
    }
    el.phone.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookupCustomer(); } });
    el.phone.addEventListener('change', lookupCustomer);
    q('[data-pos-phone-btn]').addEventListener('click', lookupCustomer);
    el.phone.addEventListener('input', () => { customer = null; el.customer.textContent = ''; });

    // ---- Thanh toán ----
    async function pay() {
        if (el.pay.disabled || busy) return;
        if (el.phone.value.trim() && !customer) { showAlert('Hãy bấm "Tìm" để xác nhận khách hàng, hoặc xóa SĐT.'); return; }
        busy = true; updateTotals(); showAlert('');
        const payload = {
            request_id: requestId,
            items: items.map(i => ({ product_id: i.id, qty: i.qty })),
            method: method(),
            discount: discountValue().value,
            phone: customer ? customer.phone : '',
            cash_given: method() === 'cash' ? intVal(el.cash) : null,
        };
        try {
            const res = await postJSON(root.dataset.checkoutUrl, payload);
            if (res.ok) { showDone(res.order); return; }
            showAlert(res.message || 'Không thanh toán được.');
            for (const p of res.problems || []) {          // giữ giỏ, cập nhật tồn thật để người bán chỉnh số lượng
                const line = items.find(i => i.id === p.product_id);
                if (line) line.stock = Number(p.available) || 0;
            }
            render();
        } catch {
            showAlert('Không nhận được phản hồi (mất kết nối hoặc phiên hết hạn). Giỏ hàng được giữ nguyên; bấm Thanh toán lần nữa sẽ không bị bán trùng, hoặc tải lại trang nếu vẫn lỗi.');
        } finally { busy = false; updateTotals(); }
    }
    function showDone(order) {
        el.cartCard.classList.add('d-none');
        el.done.classList.remove('d-none');
        q('[data-pos-done-code]').textContent = order.code;
        q('[data-pos-done-view]').href = root.dataset.ordersUrl + '/' + order.id;
        q('[data-pos-done-print]').href = root.dataset.ordersUrl + '/' + order.id + '/print';
        q('[data-pos-done-total]').textContent = PosCart.money(order.total);
        const cash = method() === 'cash';
        q('[data-pos-done-change-label]').classList.toggle('d-none', !cash);
        q('[data-pos-done-change]').classList.toggle('d-none', !cash);
        q('[data-pos-done-change]').textContent = PosCart.money(order.change);
        const pts = order.points > 0;
        q('[data-pos-done-points-label]').classList.toggle('d-none', !pts);
        q('[data-pos-done-points]').classList.toggle('d-none', !pts);
        q('[data-pos-done-points]').textContent = '+' + order.points;
        q('[data-pos-new]').focus();
    }
    function reset() {
        items = []; customer = null; requestId = newId();
        el.phone.value = ''; el.customer.textContent = ''; el.cash.value = '';
        if (el.discount) el.discount.value = '0';
        showAlert('');
        el.done.classList.add('d-none'); el.cartCard.classList.remove('d-none');
        render(); el.search.focus();
    }
    el.pay.addEventListener('click', pay);
    q('[data-pos-new]').addEventListener('click', reset);

    // Phím tắt
    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); el.search.focus(); el.search.select(); }
        else if (e.key === 'F9') { e.preventDefault(); if (el.done.classList.contains('d-none')) pay(); else reset(); }
    });

    render();
}
document.querySelectorAll('[data-pos]').forEach(initPos);
