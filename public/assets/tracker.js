(() => {
  'use strict';
  const cfg = window.ANALYTICS_CONFIG;
  if (!cfg) return;
  const consent = document.getElementById('consent');
  const status = document.getElementById('tracker-status');
  const makeId = () => (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`);
  const sessionKey = `analytics-session-${cfg.apiKey}`;
  let sessionId = sessionStorage.getItem(sessionKey);
  if (!sessionId) { sessionId = makeId(); sessionStorage.setItem(sessionKey, sessionId); }
  async function send(name) {
    if (!consent || !consent.checked) { if(status) status.textContent='Hãy đồng ý trước khi ghi nhận.'; return; }
    const body = {event_id:makeId(),session_id:sessionId,event_name:name,page_path:cfg.pagePath,occurred_at:new Date().toISOString()};
    try {
      const r = await fetch(cfg.apiUrl,{method:'POST',headers:{'Content-Type':'application/json','X-Analytics-Key':cfg.apiKey},body:JSON.stringify(body)});
      const data = await r.json();
      if(status) status.textContent = r.ok ? `Đã gửi: ${data.status}` : `Không ghi nhận: ${data.error || r.status}`;
    } catch (_) { if(status) status.textContent='Không kết nối được API.'; }
  }
  consent?.addEventListener('change', () => {
    if (consent.checked) send('page_view');
    else if(status) status.textContent='Đã tắt ghi nhận cho trang demo này.';
  });
  document.querySelectorAll('[data-track]').forEach(button => button.addEventListener('click', () => send(button.dataset.track)));
})();
