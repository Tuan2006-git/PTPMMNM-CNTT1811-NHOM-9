
/* LUXE WEAR - client-side activity tracker */
(function () {
    "use strict";

    const ENDPOINT = "api/track-event.php";
    const SESSION_KEY = "luxeWearAnalyticsSession";

    function makeId() {
        if (window.crypto && typeof window.crypto.randomUUID === "function") {
            return window.crypto.randomUUID();
        }

        return "s-" + Date.now().toString(36) + "-" +
            Math.random().toString(36).slice(2, 12);
    }

    let sessionId = sessionStorage.getItem(SESSION_KEY);

    if (!sessionId) {
        sessionId = makeId();
        sessionStorage.setItem(SESSION_KEY, sessionId);
    }

    function send(eventName, details = {}) {
        const payload = {
            event_name: String(eventName).slice(0, 80),
            page_url: window.location.pathname + window.location.search,
            session_id: sessionId,
            details: details && typeof details === "object" ? details : {}
        };

        fetch(ENDPOINT, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            keepalive: true,
            body: JSON.stringify(payload)
        }).then(response => {
            if (!response.ok) {
                console.warn("Analytics endpoint returned", response.status);
            }
        }).catch(error => {
            console.warn("Không gửi được sự kiện thống kê:", error);
        });
    }

    window.LuxeTracker = {
        track: send,
        sessionId
    };

    document.addEventListener("DOMContentLoaded", function () {

        // 1. Theo dõi lượt truy cập
        send("page_view", {
            page_title: document.title,
            referrer: document.referrer || ""
        });

        // 2. Theo dõi click danh mục và sản phẩm
        document.addEventListener("click", function (event) {
            const target = event.target.closest("[data-wi-track]");

            if (!target) return;

            const trackingName = target.getAttribute("data-wi-track");

            if (!trackingName) return;

            send("category_click", {
                category: trackingName,
                element_text: target.innerText.trim().slice(0, 100),
                element_class: String(target.className).slice(0, 100)
            });

            console.log("Luxe Wear tracking:", trackingName);
        });

        // 3. Theo dõi gửi biểu mẫu
        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", function () {
                const id = form.id || "unnamed_form";

                if (id === "searchForm") return;

                send("form_submit", {
                    form_id: id
                });
            });
        });

    });

})();
