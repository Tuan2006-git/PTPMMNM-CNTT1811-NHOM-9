/* Mã JavaScript chung cho trang quản trị */
(function () {
  'use strict';

  // Mở / đóng menu trên điện thoại
  var btn = document.getElementById('menuBtn');
  var side = document.getElementById('sidebar');
  if (btn && side) {
    btn.addEventListener('click', function () { side.classList.toggle('open'); });
  }

  // Xác nhận trước khi thực hiện thao tác nguy hiểm: <form data-confirm="Nội dung hỏi">
  document.addEventListener('submit', function (ev) {
    var msg = ev.target.getAttribute && ev.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { ev.preventDefault(); }
  });

  // Nút sao chép: <button data-copy="#id-của-ô-chứa-nội-dung">
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest ? ev.target.closest('[data-copy]') : null;
    if (!b) { return; }
    var src = document.querySelector(b.getAttribute('data-copy'));
    if (!src) { return; }
    src.select();
    try { document.execCommand('copy'); b.textContent = 'Đã sao chép'; } catch (e) { /* bỏ qua */ }
  });
})();
