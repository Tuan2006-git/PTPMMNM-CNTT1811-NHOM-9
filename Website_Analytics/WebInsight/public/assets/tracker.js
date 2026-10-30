/*
 * WebInsight Tracker
 * Nhúng vào website cần thống kê:
 *   <script src="https://<máy-chủ-webinsight>/assets/tracker.js" data-key="wi_xxxxxxxx" defer></script>
 *
 * Nguyên tắc riêng tư: tracker CHỈ gửi loại sự kiện, mã mục tiêu (id nút / id biểu mẫu / đường dẫn trang)
 * và mã phiên ngẫu nhiên. KHÔNG bao giờ đọc nội dung ô nhập, mật khẩu hay dữ liệu cá nhân.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) { return; }
  var apiKey = script.getAttribute('data-key');
  if (!apiKey) { return; }

  var endpoint = script.getAttribute('data-endpoint') || new URL('../api/collect.php', script.src).href;
  var configUrl = new URL('../api/config.php', script.src).href + '?key=' + encodeURIComponent(apiKey);

  var SESSION_KEY = 'wi_session';
  var QUEUE_KEY = 'wi_queue';
  var SESSION_GAP = 30 * 60 * 1000; // 30 phút không hoạt động => phiên mới

  var rules = {}; // khóa "loại|mục tiêu" => true

  // ---- Tiện ích ----
  function randomHex(bytes) {
    var a = new Uint8Array(bytes);
    (window.crypto || window.msCrypto).getRandomValues(a);
    var out = '';
    for (var i = 0; i < a.length; i++) { out += ('0' + a[i].toString(16)).slice(-2); }
    return out;
  }

  function getSessionId() {
    var now = Date.now();
    var s = null;
    try { s = JSON.parse(sessionStorage.getItem(SESSION_KEY)); } catch (e) { s = null; }
    if (!s || !s.id || now - s.last > SESSION_GAP) { s = { id: 's' + randomHex(12), last: now }; }
    s.last = now;
    try { sessionStorage.setItem(SESSION_KEY, JSON.stringify(s)); } catch (e) { /* bỏ qua */ }
    return s.id;
  }

  function readQueue() {
    try { return JSON.parse(sessionStorage.getItem(QUEUE_KEY)) || []; } catch (e) { return []; }
  }
  function writeQueue(q) {
    try { sessionStorage.setItem(QUEUE_KEY, JSON.stringify(q.slice(-20))); } catch (e) { /* bỏ qua */ }
  }

  function notify(type, target, status) {
    try {
      document.dispatchEvent(new CustomEvent('webinsight:event', { detail: { type: type, target: target, status: status } }));
    } catch (e) { /* bỏ qua */ }
  }

  // ---- Gửi dữ liệu (JSON) ----
  // Dùng Content-Type text/plain để tránh yêu cầu preflight CORS; máy chủ vẫn đọc nội dung như JSON.
  function post(payload) {
    return fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
      body: JSON.stringify(payload),
      keepalive: true,
      credentials: 'omit'
    });
  }

  function send(type, target) {
    var payload = {
      api_key: apiKey,
      event_id: 'e' + randomHex(12),   // mã duy nhất: gửi lại cũng không bị đếm trùng
      session_id: getSessionId(),
      type: type,
      target: target,
      path: location.pathname
    };
    post(payload).then(function (res) {
      notify(type, target, res.status);
      if (res.status >= 500) { var q = readQueue(); q.push(payload); writeQueue(q); }
    }).catch(function () {
      notify(type, target, 0);
      var q = readQueue(); q.push(payload); writeQueue(q); // lỗi mạng: thử gửi lại sau
    });
  }

  function flushQueue() {
    var q = readQueue();
    if (!q.length) { return; }
    writeQueue([]);
    q.forEach(function (p) {
      post(p).then(function (res) {
        if (res.status >= 500) { var n = readQueue(); n.push(p); writeQueue(n); }
      }).catch(function () { var n = readQueue(); n.push(p); writeQueue(n); });
    });
  }

  // ---- Lắng nghe sự kiện theo quy tắc ----
  function keyOf(el) {
    return (el.getAttribute && (el.getAttribute('data-wi-track') || el.id)) || '';
  }

  function onClick(ev) {
    for (var el = ev.target; el && el !== document; el = el.parentElement) {
      var key = keyOf(el);
      if (key && rules['click|' + key]) { send('click', key); return; }
    }
  }

  function onSubmit(ev) {
    var key = keyOf(ev.target); // chỉ lấy id biểu mẫu, không đọc giá trị các ô nhập
    if (key && rules['submit|' + key]) { send('submit', key); }
  }

  function start(cfg) {
    (cfg.rules || []).forEach(function (r) { rules[r.type + '|' + r.target] = true; });
    document.addEventListener('click', onClick, true);
    document.addEventListener('submit', onSubmit, true);
    if (rules['pageview|' + location.pathname]) { send('pageview', location.pathname); }
    flushQueue();
  }

  // Tải danh sách quy tắc đang bật; website bị tắt hoặc key sai => không theo dõi gì
  fetch(configUrl, { credentials: 'omit' })
    .then(function (res) { return res.ok ? res.json() : null; })
    .then(function (cfg) { if (cfg && cfg.ok) { start(cfg); } })
    .catch(function () { /* im lặng */ });

  window.addEventListener('online', flushQueue);
})();
