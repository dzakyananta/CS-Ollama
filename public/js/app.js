const app = document.querySelector("[data-support-app]");

if (app) {
    const thread = document.querySelector("[data-chat-thread]");
    const form = document.querySelector("[data-chat-form]");
    const input = document.querySelector("[data-chat-input]");
    const sendButton = document.querySelector("[data-send-button]");
    const title = document.querySelector("[data-chat-title]");
    const modelStatus = document.querySelector("[data-model-status]");
    const modelIndicator = document.querySelector("[data-model-indicator]");
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const emptyState = thread.innerHTML;
    let activeConversation = null;

    function showToast(message) {
        document.querySelector(".toast")?.remove();
        const toast = document.createElement("div");
        toast.className = "toast";
        toast.setAttribute("role", "status");
        toast.textContent = message;
        document.body.append(toast);
        window.setTimeout(() => toast.remove(), 4800);
    }

    function addMessage(sender, body, source = null, createdAt = null) {
        const row = document.createElement("div");
        row.className = `message-row ${sender === "customer" ? "is-user" : "is-assistant"}`;
        const avatar = document.createElement("span");
        avatar.className = "message-avatar";
        avatar.textContent = sender === "customer" ? "CS" : "✳";
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
        meta.textContent = `${time}${source === "ollama" ? " · AI lokal" : source === "offline" ? " · Tersimpan" : ""}`;
        content.append(bubble, meta);
        row.append(avatar, content);
        thread.append(row);
        thread.scrollTop = thread.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement("div");
        typing.className = "typing-indicator";
        typing.dataset.typing = "true";
        typing.setAttribute("aria-label", "Asisten sedang membalas");
        typing.innerHTML = "<i></i><i></i><i></i>";
        thread.append(typing);
        thread.scrollTop = thread.scrollHeight;
    }

    async function openConversation(button) {
        activeConversation = Number(button.dataset.conversationId);
        document
            .querySelectorAll(".conversation-item")
            .forEach((item) =>
                item.classList.toggle("is-selected", item === button),
            );
        title.textContent = button.dataset.customerName;
        thread.replaceChildren();

        try {
            const response = await fetch(
                `${app.dataset.messagesUrl}/${activeConversation}/messages`,
                { headers: { Accept: "application/json" } },
            );
            if (!response.ok) throw new Error("Percakapan tidak dapat dimuat.");
            const data = await response.json();
            data.messages.forEach((message) =>
                addMessage(
                    message.sender,
                    message.body,
                    message.source,
                    message.created_at,
                ),
            );
        } catch (error) {
            thread.innerHTML = emptyState;
            showToast(error.message);
        }
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
        return data;
    }

    document.querySelectorAll("[data-conversation-id]").forEach((button) => {
        button.addEventListener("click", () => openConversation(button));
    });

    document.querySelectorAll("[data-new-chat]").forEach((button) => {
        button.addEventListener("click", () => {
            activeConversation = null;
            document
                .querySelectorAll(".conversation-item")
                .forEach((item) => item.classList.remove("is-selected"));
            title.textContent = "Asisten layanan";
            thread.innerHTML = emptyState;
            input.focus();
        });
    });

    document.querySelectorAll("[data-view-target]").forEach((button) => {
        button.addEventListener("click", () => {
            document
                .querySelectorAll("[data-view-target]")
                .forEach((item) =>
                    item.classList.toggle("is-active", item === button),
                );
            document.querySelectorAll("[data-view]").forEach((panel) => {
                const visible =
                    panel.dataset.view === button.dataset.viewTarget;
                panel.hidden = !visible;
                panel.classList.toggle("is-visible", visible);
            });
        });
    });

    document.querySelectorAll("[data-table-search]").forEach((search) => {
        search.addEventListener("input", () => {
            const table = document.getElementById(search.dataset.tableSearch);
            const query = search.value.toLocaleLowerCase("id");
            table.querySelectorAll("tbody tr").forEach((row) => {
                row.hidden = !row.textContent
                    .toLocaleLowerCase("id")
                    .includes(query);
            });
        });
    });

    document
        .querySelector("[data-search-conversation]")
        ?.addEventListener("input", (event) => {
            const query = event.target.value.toLocaleLowerCase("id");
            document.querySelectorAll(".conversation-item").forEach((item) => {
                item.hidden = !item.textContent
                    .toLocaleLowerCase("id")
                    .includes(query);
            });
        });

    document.querySelectorAll("[data-suggestion]").forEach((button) => {
        button.addEventListener("click", () => {
            input.value = button.dataset.suggestion;
            input.focus();
        });
    });

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
            const data = await submitMessage(message);
            if (
                !document.querySelector(
                    `[data-conversation-id="${data.conversation_id}"]`,
                )
            )
                window.location.reload();
        } catch (error) {
            thread.querySelector("[data-typing]")?.remove();
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

    fetch(app.dataset.statusUrl, { headers: { Accept: "application/json" } })
        .then((response) => response.json())
        .then((status) => {
            modelStatus.textContent = status.available
                ? `${status.model} · siap`
                : `${status.model} · tidak aktif`;
            modelIndicator.classList.toggle("is-ready", status.available);
        })
        .catch(() => {
            modelStatus.textContent = "Model lokal tidak terjangkau";
        });

    const firstConversation = document.querySelector(".conversation-item");
    if (firstConversation) openConversation(firstConversation);
}
