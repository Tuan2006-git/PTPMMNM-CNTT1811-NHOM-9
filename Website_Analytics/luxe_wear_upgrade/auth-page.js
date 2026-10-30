(() => {
  const message = document.getElementById("authMessage");
  const showMessage = (text, type = "error") => {
    message.textContent = text;
    message.className = `auth-message show ${type}`;
  };
  async function send(action, data) {
    const response = await fetch(`auth.php?action=${action}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify(data)
    });
    let result;
    try { result = await response.json(); }
    catch { throw new Error("Không đọc được phản hồi từ máy chủ. Hãy chạy website bằng PHP server, không mở file trực tiếp."); }
    if (!response.ok || !result.ok) throw new Error(result.message || "Không thể thực hiện yêu cầu.");
    return result;
  }

  document.querySelectorAll("[data-toggle-password]").forEach(button => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.togglePassword);
      const reveal = input.type === "password";
      input.type = reveal ? "text" : "password";
      button.textContent = reveal ? "ẨN" : "HIỆN";
    });
  });

  const remembered = localStorage.getItem("luxeWearRememberedEmail");
  const emailInput = document.getElementById("email");
  const remember = document.getElementById("rememberEmail");
  if (emailInput && remembered) {
    emailInput.value = remembered;
    if (remember) remember.checked = true;
  }

  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", async event => {
      event.preventDefault();
      message.className = "auth-message";
      const button = loginForm.querySelector('[type="submit"]');
      button.disabled = true;
      button.textContent = "ĐANG ĐĂNG NHẬP...";
      try {
        const email = emailInput.value.trim();
        const result = await send("login", { email, password: document.getElementById("password").value });
        window.LuxeTracker?.track("login_success");
        if (remember?.checked) localStorage.setItem("luxeWearRememberedEmail", email);
        else localStorage.removeItem("luxeWearRememberedEmail");
        showMessage(`Xin chào ${result.user.name}! Đang chuyển về cửa hàng...`, "success");
        setTimeout(() => location.href = "index.html", 800);
      } catch (error) {
        showMessage(error.message);
      } finally {
        button.disabled = false;
        button.textContent = "ĐĂNG NHẬP →";
      }
    });
  }

  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", async event => {
      event.preventDefault();
      message.className = "auth-message";
      const name = document.getElementById("name").value.trim();
      const email = emailInput.value.trim();
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirmPassword").value;
      if (name.length < 2) return showMessage("Họ tên cần ít nhất 2 ký tự.");
      if (password.length < 8) return showMessage("Mật khẩu cần ít nhất 8 ký tự.");
      if (password !== confirmPassword) return showMessage("Mật khẩu xác nhận không khớp.");
      if (!document.getElementById("agreeTerms").checked) return showMessage("Vui lòng xác nhận đồng ý để tiếp tục.");
      const button = registerForm.querySelector('[type="submit"]');
      button.disabled = true;
      button.textContent = "ĐANG TẠO TÀI KHOẢN...";
      try {
        const result = await send("register", { name, email, password });
        window.LuxeTracker?.track("register_success");
        showMessage(`Tạo tài khoản thành công. Xin chào ${result.user.name}!`, "success");
        setTimeout(() => location.href = "index.html", 900);
      } catch (error) {
        showMessage(error.message);
      } finally {
        button.disabled = false;
        button.textContent = "TẠO TÀI KHOẢN →";
      }
    });
  }
})();
