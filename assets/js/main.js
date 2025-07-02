document.addEventListener("DOMContentLoaded", () => {
    initModals();
    initForms();
    initNavigation();
    initAnimations();
});

// === Модальные окна ===
function initModals() {
    const modals = document.querySelectorAll(".modal");
    const modalTriggers = document.querySelectorAll("[data-modal]");
    const closeBtns = document.querySelectorAll(".close");

    modalTriggers.forEach(trigger => {
        trigger.addEventListener("click", function (e) {
            e.preventDefault();
            const modalId = this.getAttribute("data-modal");
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "block";
                document.body.style.overflow = "hidden";

                const focusable = modal.querySelector("input, textarea, select");
                if (focusable) focusable.focus();
            }
        });
    });

    closeBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            const modal = this.closest(".modal");
            if (modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        });
    });

    modals.forEach(modal => {
        modal.addEventListener("click", function (e) {
            if (e.target === this) {
                this.style.display = "none";
                document.body.style.overflow = "auto";
            }
        });
    });

    // Закрытие по ESC
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            modals.forEach(modal => modal.style.display = "none");
            document.body.style.overflow = "auto";
        }
    });
}

// === Обработка форм через AJAX ===
function initForms() {
    const forms = document.querySelectorAll("form[data-ajax]");

    forms.forEach(form => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : "";

            if (submitBtn) {
                submitBtn.textContent = "Отправка...";
                submitBtn.disabled = true;
            }

            fetch(this.action, {
                method: "POST",
                body: formData,
            })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || "Ошибка запроса");

                    if (data.success) {
                        showNotification(data.message, "success");
                        if (data.redirect) {
                            setTimeout(() => window.location.href = data.redirect, 1000);
                        } else {
                            location.reload();
                        }
                    } else {
                        showNotification(data.message || "Что-то пошло не так", "error");
                    }
                })
                .catch(error => {
                    console.error("Ошибка:", error);
                    showNotification(error.message || "Произошла ошибка. Попробуйте ещё раз.", "error");
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }
                });
        });
    });
}

// === Навигация по якорям ===
function initNavigation() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const targetId = this.getAttribute("href").substring(1);
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }
        });
    });
}

// === Анимации при скролле ===
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: "0px 0px -50px 0px"
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("animate-in");
                observer.unobserve(entry.target); // Отключаем наблюдение
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll(".card, .hero, .section-title, .stats-card");
    animatedElements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
        el.classList.add("fade-in");
        observer.observe(el);
    });
}

// === Уведомления ===
function showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    Object.assign(notification.style, {
        position: "fixed",
        top: "20px",
        right: "20px",
        padding: "12px 18px",
        borderRadius: "6px",
        color: "#fff",
        fontWeight: "400",
        zIndex: "3000",
        transform: "translateX(100%)",
        transition: "transform 0.3s ease",
        fontFamily: '"Roboto", sans-serif',
        fontSize: "0.9rem",
        maxWidth: "350px",
        boxShadow: "0 4px 12px rgba(0,0,0,0.3)"
    });

    switch (type) {
        case "success":
            notification.style.background = "linear-gradient(135deg, var(--success-green, #4CAF50), #45A049)";
            break;
        case "error":
            notification.style.background = "linear-gradient(135deg, #F44336, #D32F2F)";
            break;
        case "warning":
            notification.style.background = "linear-gradient(135deg, var(--energy-orange, #FF9800), var(--hover-orange, #FF5722))";
            break;
        default:
            notification.style.background = "linear-gradient(135deg, var(--accent-blue, #2196F3), var(--hover-blue, #1976D2))";
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.transform = "translateX(0)";
    }, 100);

    setTimeout(() => {
        notification.style.transform = "translateX(100%)";
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 4000);
}

// Экспорт для внешнего доступа
window.FitLab = {
    showNotification
};

window.showNotification = showNotification;
