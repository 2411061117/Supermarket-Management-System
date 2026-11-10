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
        if (line) line.qty = Math.min(line.qty + qty, stock);
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
