<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ruang Layanan | Microdata</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="app-shell" data-support-app data-chat-url="{{ route('chat.store') }}" data-status-url="{{ route('ollama.status') }}" data-messages-url="{{ url('/conversations') }}">
    <aside class="sidebar">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="Ruang Layanan, beranda">
            <span class="brand-mark">m<span>.</span></span>
            <span class="brand-copy"><strong>microdata</strong><small>RUANG LAYANAN</small></span>
        </a>
        <div class="side-label">WORKSPACE</div>
        <nav class="side-nav" aria-label="Navigasi utama">
            <button class="nav-item is-active" type="button" data-view-target="inbox">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5h16v13H4zM4 14h4l1.5 2h5L16 14h4M8 9h8"/></svg>
                <span>Ringkasan</span><span class="nav-count">{{ $metrics['openConversations'] }}</span>
            </button>
            <button class="nav-item" type="button" data-view-target="customers">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.8"/></svg>
                <span>Pelanggan</span>
            </button>
            <button class="nav-item" type="button" data-view-target="products">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 8 8-4 8 4-8 4zM4 8v9l8 4 8-4V8M12 12v9"/></svg>
                <span>Katalog produk</span>
            </button>
        </nav>
        <div class="sidebar-spacer"></div>
        <div class="sidebar-note"><span class="live-dot"></span><div><strong>Asisten lokal</strong><small>Data tetap di perangkat ini</small></div></div>
        <div class="profile"><span class="profile-avatar">CS</span><span><strong>Tim layanan</strong><small>Administrator</small></span><span class="profile-menu" aria-hidden="true">···</span></div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="breadcrumb"><span>Microdata</span><span class="crumb-slash">/</span><strong>Customer care</strong></div>
            <div class="topbar-right"><span class="today-label">{{ now()->format('d M Y') }}</span><span class="top-avatar">CS</span></div>
        </header>

        <div class="page-content">
            <section class="page-heading">
                <div><p class="eyebrow">PUSAT LAYANAN PELANGGAN</p><h1>Ruang layanan</h1><p class="page-subtitle">Pantau percakapan dan bantu pelanggan dari satu tempat.</p></div>
                <button class="button button-primary" type="button" data-new-chat><span class="button-plus">+</span>Percakapan baru</button>
            </section>

            <section class="metric-grid" aria-label="Ringkasan layanan">
                <article class="metric metric-green"><div class="metric-top"><span>Percakapan aktif</span><span class="metric-icon">↗</span></div><strong>{{ number_format($metrics['openConversations']) }}</strong><small>Menunggu tindak lanjut</small></article>
                <article class="metric metric-blue"><div class="metric-top"><span>Total pelanggan</span><span class="metric-icon">◎</span></div><strong>{{ number_format($metrics['customers']) }}</strong><small>Profil tersimpan</small></article>
                <article class="metric metric-orange"><div class="metric-top"><span>Produk aktif</span><span class="metric-icon">◇</span></div><strong>{{ number_format($metrics['products']) }}</strong><small>Siap dicari asisten AI</small></article>
                <article class="metric metric-lilac"><div class="metric-top"><span>Panduan jawaban</span><span class="metric-icon">✳</span></div><strong>{{ number_format($metrics['faqs']) }}</strong><small>Artikel FAQ tersedia</small></article>
            </section>

            <section class="view-panel is-visible" data-view="inbox">
                <div class="workspace-grid">
                    <section class="inbox-panel panel">
                        <div class="panel-heading"><div><p class="eyebrow">INBOX</p><h2>Percakapan terbaru</h2></div><span class="tiny-count">{{ $conversations->count() }}</span></div>
                        <label class="search-box"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="Cari pelanggan" data-search-conversation aria-label="Cari percakapan"></label>
                        <div class="conversation-list" data-conversation-list>
                            @forelse ($conversations as $conversation)
                                <button class="conversation-item" type="button" data-conversation-id="{{ $conversation->id }}" data-customer-name="{{ $conversation->customer->name }}" data-subject="{{ $conversation->subject }}">
                                    <span class="conversation-avatar avatar-{{ $loop->iteration % 5 }}">{{ collect(explode(' ', $conversation->customer->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
                                    <span class="conversation-copy"><span class="conversation-row"><strong>{{ $conversation->customer->name }}</strong><time>{{ $conversation->last_message_at?->diffForHumans(short: true) ?? 'Baru' }}</time></span><span class="conversation-preview">{{ $conversation->latestMessage?->body ?? $conversation->subject }}</span><span class="conversation-topic">{{ $conversation->subject }}</span></span>
                                    <i class="status-dot status-{{ $conversation->status }}" aria-label="{{ $conversation->status }}"></i>
                                </button>
                            @empty
                                <div class="empty-inbox"><span>✳</span><strong>Belum ada percakapan</strong><small>Mulai chat untuk melihat pesan di sini.</small></div>
                            @endforelse
                        </div>
                        <div class="inbox-footer"><span class="live-dot"></span>{{ $conversations->count() }} percakapan tersimpan</div>
                    </section>

                    <section class="chat-panel panel" aria-label="Chat asisten customer service">
                        <div class="chat-header"><div class="assistant-badge"><span>✳</span></div><div class="chat-title"><h2 data-chat-title>Asisten layanan</h2><p><span class="model-indicator" data-model-indicator></span><span data-model-status>Mengecek model lokal...</span></p></div><button class="icon-button" type="button" title="Percakapan baru" aria-label="Mulai percakapan baru" data-new-chat><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg></button></div>
                        <div class="chat-context"><span class="context-mark">i</span><span>Jawaban AI merujuk ke katalog produk dan panduan layanan tersimpan.</span></div>
                        <div class="chat-thread" data-chat-thread aria-live="polite" aria-relevant="additions text">
                            <div class="welcome-message"><span class="welcome-spark">✳</span><p class="eyebrow">ASISTEN MICRODATA</p><h3>Halo, ada yang bisa dibantu?</h3><p>Tanyakan produk, stok, garansi, pengiriman, atau kebijakan layanan.</p><div class="suggestion-list"><button type="button" data-suggestion="Berapa harga Ponsel Seri 01?">Harga produk</button><button type="button" data-suggestion="Bagaimana cara melacak pesanan?">Lacak pesanan</button><button type="button" data-suggestion="Bagaimana kebijakan retur?">Info retur</button></div></div>
                        </div>
                        <form class="composer" data-chat-form>
                            <label class="sr-only" for="chat-message">Tulis pesan</label>
                            <textarea id="chat-message" name="message" rows="1" placeholder="Tulis pertanyaan pelanggan..." maxlength="2000" required data-chat-input></textarea>
                            <div class="composer-bottom"><span>Enter untuk kirim · Shift + Enter untuk baris baru</span><button class="send-button" type="submit" title="Kirim pesan" aria-label="Kirim pesan" data-send-button><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 4 16 8-16 8 3-8zM7 12h13"/></svg></button></div>
                        </form>
                    </section>
                </div>
            </section>

            <section class="view-panel panel table-panel" data-view="customers" hidden>
                <div class="panel-heading"><div><p class="eyebrow">DATA PELANGGAN</p><h2>Pelanggan</h2></div><label class="search-box table-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="Cari nama atau email" data-table-search="customers-table" aria-label="Cari pelanggan"></label></div>
                <div class="table-wrap"><table id="customers-table"><thead><tr><th>Nama pelanggan</th><th>Email</th><th>Telepon</th><th>Segmen</th><th>Bergabung</th></tr></thead><tbody>
                    @foreach ($customersList as $customer)
                        <tr><td><span class="table-person"><span class="table-avatar">{{ mb_substr($customer->name, 0, 1) }}</span><strong>{{ $customer->name }}</strong></span></td><td>{{ $customer->email }}</td><td>{{ $customer->phone ?? '—' }}</td><td><span class="soft-tag">{{ $customer->segment }}</span></td><td>{{ $customer->created_at->format('d M Y') }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>

            <section class="view-panel panel table-panel" data-view="products" hidden>
                <div class="panel-heading"><div><p class="eyebrow">KATALOG</p><h2>Produk aktif</h2></div><label class="search-box table-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="Cari produk atau kategori" data-table-search="products-table" aria-label="Cari produk"></label></div>
                <div class="table-wrap"><table id="products-table"><thead><tr><th>Nama produk</th><th>SKU</th><th>Kategori</th><th>Harga</th><th>Stok</th></tr></thead><tbody>
                    @foreach ($products as $product)
                        <tr><td><span class="product-name"><span class="product-glyph">{{ mb_substr($product->category, 0, 1) }}</span><strong>{{ $product->name }}</strong></span></td><td class="sku-cell">{{ $product->sku }}</td><td>{{ $product->category }}</td><td class="price-cell">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</td><td><span class="stock-badge {{ $product->stock < 10 ? 'stock-low' : '' }}">{{ $product->stock }} unit</span></td></tr>
                    @endforeach
                </tbody></table></div>
                <p class="table-footnote">Menampilkan 12 dari {{ number_format($metrics['products']) }} produk aktif.</p>
            </section>
            <footer class="page-footer"><span>Ruang Layanan <span class="footer-dot">·</span> Microdata</span><span>Didukung model lokal <strong>Qwen 2.5 0.5B</strong></span></footer>
        </div>
    </main>
</div>
<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>