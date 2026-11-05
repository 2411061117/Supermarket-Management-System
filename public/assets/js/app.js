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
