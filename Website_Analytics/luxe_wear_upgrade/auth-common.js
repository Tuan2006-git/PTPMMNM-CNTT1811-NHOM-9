(() => {
  async function request(action, options = {}) {
    const response = await fetch(`auth.php?action=${encodeURIComponent(action)}`, {
      credentials: "same-origin",
      ...options
    });
    let result;
    try { result = await response.json(); }
    catch { throw new Error("Máy chủ trả về dữ liệu không hợp lệ. Hãy chạy website bằng PHP server."); }
    if (!response.ok || !result.ok) throw new Error(result.message || "Có lỗi xảy ra.");
    return result;
  }

  document.addEventListener("DOMContentLoaded", async () => {
    const accountLink = document.querySelector('.header-actions a[href="login.html"]');
    if (!accountLink) return;
    try {
      const result = await request("me");
      if (result.authenticated && result.user) {
        accountLink.textContent = `♙ ${result.user.name}`;
        accountLink.href = "javascript:void(0)";
        accountLink.title = result.user.email;
        accountLink.addEventListener("click", async (event) => {
          event.preventDefault();
          if (!confirm(`Bạn đang đăng nhập bằng ${result.user.email}. Đăng xuất?`)) return;
          try {
            await request("logout", { method: "POST" });
            location.reload();
          } catch (error) { alert(error.message); }
        });
      }
    } catch (error) {
      console.warn("Không kiểm tra được phiên đăng nhập:", error.message);
    }
  });
})();
