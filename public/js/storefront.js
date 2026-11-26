const app = document.querySelector("[data-support-app]");

if (app) {
    const thread = document.querySelector("[data-chat-thread]");
    const chatWidget = document.querySelector("[data-chat-widget]");
    const chatBackdrop = document.querySelector("[data-chat-backdrop]");
    const detailOverlay = document.querySelector("[data-detail-overlay]");
    const detailDialog = document.querySelector("[data-detail-dialog]");
    const detailImage = document.querySelector("[data-detail-image]");
    const detailBrand = document.querySelector("[data-detail-brand]");
    const detailCategory = document.querySelector("[data-detail-category]");
    const detailTitle = document.querySelector("[data-detail-title]");
    const detailDescription = document.querySelector(
        "[data-detail-description]",
    );
    const detailPrice = document.querySelector("[data-detail-price]");
    const detailStock = document.querySelector("[data-detail-stock]");
    const detailSpecs = document.querySelector("[data-detail-specs]");
    const form = document.querySelector("[data-chat-form]");
    const input = document.querySelector("[data-chat-input]");
    const sendButton = document.querySelector("[data-send-button]");
    const modelStatus = document.querySelector("[data-model-status]");
    const modelIndicator = document.querySelector("[data-model-indicator]");
    const emptyState = thread.innerHTML;
    const allProducts = [...document.querySelectorAll("[data-product-card]")];
    const navLinks = [...document.querySelectorAll("[data-nav-target]")];
    const resultCount = document.querySelector("[data-result-count]");
    const loadMore = document.querySelector("[data-load-more]");
    const emptyProducts = document.querySelector("[data-empty-products]");
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let activeConversation = null;
    let selectedCategory = "all";
    let searchQuery = "";
    let visibleLimit = 8;
    let detailCloseTimer;

    function showToast(message) {
        document.querySelector(".store-toast")?.remove();
        const toast = document.createElement("div");
        toast.className = "store-toast";
        toast.setAttribute("role", "status");
        toast.textContent = message;
        document.body.append(toast);
        window.setTimeout(() => toast.remove(), 3200);
    }

    function updateProducts() {
        let matches = 0;
        const visibleCards = [];

        allProducts.forEach((card) => {
            const categoryMatch =
                selectedCategory === "all" ||
                card.dataset.category === selectedCategory;
            const searchMatch =
                !searchQuery || card.dataset.searchText.includes(searchQuery);
            const matched = categoryMatch && searchMatch;
            const shouldShow = matched && matches < visibleLimit;
            card.hidden = !shouldShow;
            if (shouldShow) {
                visibleCards.push(card);
                matches += 1;
            }
        });
        const shown = Math.min(matches, visibleLimit);
        resultCount.textContent = `Menampilkan ${shown} dari ${matches} produk`;
        loadMore.hidden = matches <= visibleLimit;
        emptyProducts.hidden = matches > 0;

        visibleCards.forEach((card, index) => {
            card.style.animation = "none";
            card.offsetHeight;
            card.style.animation = `product-arrive 420ms cubic-bezier(.2,.7,.2,1) ${Math.min(index * 38, 266)}ms both`;
        });
    }

    function setActiveNavigation(targetId) {
        navLinks.forEach((link) => {
            const isActive = link.dataset.navTarget === targetId;
            link.classList.toggle("is-current", isActive);

            if (isActive) link.setAttribute("aria-current", "location");
            else link.removeAttribute("aria-current");
        });
    }

    function updateActiveNavigationFromScroll() {
        const atPageEnd =
            window.scrollY + window.innerHeight >=
            document.documentElement.scrollHeight - 4;
        if (atPageEnd) {
            setActiveNavigation("panduan");
            return;
        }

        const activeLine =
            window.scrollY + Math.round(window.innerHeight * 0.35);
        let currentLink = navLinks[0];

        navLinks.forEach((link) => {
            const section = document.getElementById(link.dataset.navTarget);

            if (
                section &&
                section.getBoundingClientRect().top + window.scrollY <=
                    activeLine
            ) {
                currentLink = link;
            }
        });

        if (currentLink) setActiveNavigation(currentLink.dataset.navTarget);
    }

    function openChat(prefill = "") {
        chatWidget.hidden = false;
        chatBackdrop.hidden = false;
        document
            .querySelectorAll("[data-chat-open]")
            .forEach((button) => button.setAttribute("aria-expanded", "true"));
        document
            .querySelector("[data-chat-open].cs-launcher")
            ?.classList.add("is-open");
        document.body.classList.add("chat-open");
        if (prefill) input.value = prefill;
        window.setTimeout(() => input.focus(), 120);
    }

    function closeChat() {
        chatWidget.hidden = true;
        chatBackdrop.hidden = true;
        document
            .querySelectorAll("[data-chat-open]")
            .forEach((button) => button.setAttribute("aria-expanded", "false"));
        document
            .querySelector("[data-chat-open].cs-launcher")
            ?.classList.remove("is-open");
        document.body.classList.remove("chat-open");
    }

    function openProductDetails(button) {
        const card = button.closest("[data-product-card]");
        const image = card.querySelector("[data-product-image]");

        detailBrand.textContent = card
            .querySelector(".product-sku")
            .textContent.trim();
        detailCategory.textContent = card.dataset.category;
        detailTitle.textContent = card.querySelector("h3").textContent;
        detailDescription.textContent = card.querySelector(
            ".product-description",
        ).textContent;
        detailPrice.textContent =
            card.querySelector(".product-price").textContent;
        detailStock.textContent =
            card.querySelector(".product-stock").textContent;
        detailImage.src = image.currentSrc || image.src;
        detailImage.alt = image.alt;
        detailSpecs.replaceChildren(
            ...[...card.querySelector(".laptop-specs").children].map((spec) =>
                spec.cloneNode(true),
            ),
        );

        window.clearTimeout(detailCloseTimer);
        detailOverlay.hidden = false;
        document.body.classList.add("detail-open");
        window.requestAnimationFrame(() =>
            detailOverlay.classList.add("is-open"),
        );
        window.setTimeout(() => detailDialog.focus(), 90);
    }

    function closeProductDetails() {
        if (detailOverlay.hidden) return;

        detailOverlay.classList.remove("is-open");
        document.body.classList.remove("detail-open");
        detailCloseTimer = window.setTimeout(() => {
            if (!detailOverlay.classList.contains("is-open"))
                detailOverlay.hidden = true;
        }, 240);
    }

    function addMessage(sender, body, source = null, createdAt = null) {
        const row = document.createElement("div");
        row.className = `message-row ${sender === "customer" ? "is-user" : "is-assistant"}`;
        const avatar = document.createElement("span");
        avatar.className = "message-avatar";
        avatar.textContent = sender === "customer" ? "Kamu" : "m.";
        const content = document.createElement("div");
        content.className = "message-content";
        const bubble = document.createElement("div");
        bubble.className = "message-bubble";
        bubble.textContent = body;
        const meta = document.createElement("span");
        meta.className = "message-meta";
        const time = createdAt
            ? new Date(createdAt).toLocaleTimeString("id-ID", {
                  hour: "2-digit",
                  minute: "2-digit",
              })
            : new Date().toLocaleTimeString("id-ID", {
                  hour: "2-digit",
                  minute: "2-digit",
              });
        const sourceLabel =
            source === "ollama"
                ? "CS Microdata"
                : source === "recommendations"
                  ? "Pilihan CS"
                  : source === "faq"
                    ? "Panduan CS"
                    : source === "catalog"
                      ? "Data katalog"
                      : source === "offline"
                        ? "Tersimpan"
                        : "";
        meta.textContent = `${time}${sourceLabel ? ` · ${sourceLabel}` : ""}`;
        content.append(bubble, meta);
        row.append(avatar, content);
        thread.append(row);
        thread.scrollTop = thread.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement("div");
        typing.className = "typing-indicator";
        typing.dataset.typing = "true";
        typing.setAttribute("aria-label", "CS sedang menyiapkan jawaban");
        typing.innerHTML = "<i></i><i></i><i></i>";
        thread.append(typing);
        thread.scrollTop = thread.scrollHeight;
    }

    async function loadConversation() {
        if (!activeConversation) {
            thread.innerHTML = emptyState;
            return;
        }
        const response = await fetch(
            `${app.dataset.messagesUrl}/${activeConversation}/messages`,
            { headers: { Accept: "application/json" } },
        );
        if (!response.ok) throw new Error("Percakapan tidak dapat dimuat.");
        const data = await response.json();
        thread.replaceChildren();
        data.messages.forEach((message) =>
            addMessage(
                message.sender,
                message.body,
                message.source,
                message.created_at,
            ),
        );
    }

    async function submitMessage(message) {
        const payload = { message };
        if (activeConversation) payload.conversation_id = activeConversation;
        const response = await fetch(app.dataset.chatUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
                Accept: "application/json",
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok)
            throw new Error(data.message || "Pesan belum terkirim. Coba lagi.");
        activeConversation = data.conversation_id;
        addMessage(
            "assistant",
            data.message.body,
            data.source,
            data.message.created_at,
        );
    }

    document
        .querySelectorAll("[data-chat-open]")
        .forEach((button) =>
            button.addEventListener("click", () => openChat()),
        );

    navLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            const section = document.getElementById(link.dataset.navTarget);
            if (!section) return;

            event.preventDefault();
            history.pushState(null, "", `#${section.id}`);
            setActiveNavigation(section.id);
            section.scrollIntoView({
                behavior: window.matchMedia("(prefers-reduced-motion: reduce)")
                    .matches
                    ? "auto"
                    : "smooth",
                block: "start",
            });
        });
    });

    window.addEventListener("scroll", updateActiveNavigationFromScroll, {
        passive: true,
    });
    window.addEventListener("resize", updateActiveNavigationFromScroll);
    window.addEventListener("popstate", updateActiveNavigationFromScroll);
    updateActiveNavigationFromScroll();

    document
        .querySelector("[data-chat-close]")
        .addEventListener("click", closeChat);
    chatBackdrop.addEventListener("click", closeChat);

    document.querySelectorAll("[data-detail-open]").forEach((button) => {
        button.addEventListener("click", () => openProductDetails(button));
    });

    document.querySelectorAll("[data-detail-close]").forEach((button) => {
        button.addEventListener("click", closeProductDetails);
    });
    detailOverlay.addEventListener("click", (event) => {
        if (event.target === detailOverlay) closeProductDetails();
    });

    document.querySelectorAll("[data-category-filter]").forEach((button) => {
        button.addEventListener("click", () => {
            selectedCategory = button.dataset.categoryFilter;
            visibleLimit = 8;
            document
                .querySelectorAll("[data-category-filter]")
                .forEach((tab) =>
                    tab.classList.toggle("is-selected", tab === button),
                );
            updateProducts();
        });
    });

    document
        .querySelector("[data-product-search]")
        .addEventListener("input", (event) => {
            searchQuery = event.target.value.trim().toLocaleLowerCase("id");
            visibleLimit = 8;
            updateProducts();
            document
                .querySelector("#produk")
                .scrollIntoView({ behavior: "smooth", block: "start" });
        });

    loadMore.addEventListener("click", () => {
        visibleLimit += 8;
        updateProducts();
    });

    document.querySelectorAll("[data-suggestion]").forEach((button) =>
        button.addEventListener("click", () => {
            input.value = button.dataset.suggestion;
            input.focus();
        }),
    );

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || sendButton.disabled) return;
        if (thread.querySelector(".welcome-message")) thread.replaceChildren();
        addMessage("customer", message);
        input.value = "";
        sendButton.disabled = true;
        showTyping();

        try {
            await submitMessage(message);
        } catch (error) {
            showToast(error.message);
        } finally {
            thread.querySelector("[data-typing]")?.remove();
            sendButton.disabled = false;
            input.focus();
        }
    });

    input.addEventListener("keydown", (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            if (!detailOverlay.hidden) closeProductDetails();
            else closeChat();
        }
    });

    fetch(app.dataset.statusUrl, { headers: { Accept: "application/json" } })
        .then((response) => response.json())
        .then((status) => {
            modelStatus.textContent = status.available
                ? `${status.model} · siap`
                : `${status.model} · offline`;
            modelIndicator.classList.toggle("is-ready", status.available);
        })
        .catch(() => {
            modelStatus.textContent = "Asisten lokal tidak terjangkau";
        });

    updateProducts();
}
