<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#183b34">
    <title>Microdata | Toko Laptop</title>
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}">
</head>
<body>
<div class="storefront" data-support-app data-chat-url="{{ route('chat.store') }}" data-status-url="{{ route('ollama.status') }}" data-messages-url="{{ url('/conversations') }}">
    <div class="announcement-bar"><span class="announcement-pulse"></span>Temukan laptop yang sesuai kebutuhanmu <a href="#produk">Lihat koleksi <span aria-hidden="true">↘</span></a></div>

    <header class="store-header">
        <a class="store-brand" href="{{ route('dashboard') }}" aria-label="Microdata, beranda">
            <span class="brand-symbol">m<span>.</span></span>
            <span class="brand-name">microdata<small>TECH FOR EVERYDAY</small></span>
        </a>
        <nav class="store-nav" aria-label="Navigasi toko">
            <a class="nav-link is-current" href="#produk" data-nav-target="produk">Laptop</a>
            <a class="nav-link" href="#panduan" data-nav-target="panduan">Panduan memilih</a>
            <a class="nav-link" href="#spesifikasi" data-nav-target="spesifikasi">Spesifikasi</a>
        </nav>
        <div class="header-actions">
            <label class="header-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4 4"/></svg><input type="search" placeholder="Cari laptop" aria-label="Cari laptop" data-product-search></label>
        </div>
    </header>

    <main>
        <section class="hero" id="pilihan" aria-label="Koleksi laptop Microdata">
            <img class="hero-image" src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=2000&q=88" alt="Laptop dan perlengkapan kerja di atas meja" fetchpriority="high">
            <div class="hero-shade"></div>
            <div class="hero-content">
                <p class="hero-kicker"><span></span>TOKO LAPTOP MICRODATA</p>
                <h1>Laptop yang pas<br>untuk langkahmu.</h1>
                <p class="hero-copy">Bandingkan harga dan spesifikasi untuk kuliah, kerja, desain, atau gaming. Butuh pertimbangan? CS kami siap membantu.</p>
                <div class="hero-actions"><a class="hero-button" href="#produk">Lihat koleksi laptop <span aria-hidden="true">↘</span></a><a class="hero-chat-link" href="#produk">Bandingkan spesifikasi</a></div>
            </div>
            <div class="hero-index"><span>01</span><i></i><span>KATALOG PILIHAN</span></div>
            <div class="hero-caption"><span>MICRODATA / {{ now()->format('Y') }}</span><span>{{ number_format($products->count()) }} laptop dalam katalog</span></div>
        </section>

        <section class="benefit-strip" aria-label="Informasi toko laptop">
            <div class="benefit"><span class="benefit-icon benefit-shield"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 19 6v5c0 5-3 8-7 10-4-2-7-5-7-10V6zM9 12l2 2 4-4"/></svg></span><span><strong>Spesifikasi jelas</strong><small>Bandingkan sebelum bertanya</small></span></div>
            <div class="benefit"><span class="benefit-icon benefit-box"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 8 8-4 8 4-8 4zM4 8v9l8 4 8-4V8M12 12v9"/></svg></span><span><strong>Garansi tercatat</strong><small>Info garansi ada di katalog</small></span></div>
            <div class="benefit"><span class="benefit-icon benefit-chat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-8 7.5 9 9 0 0 1-4-.9L4 20l1.2-3.4A7.2 7.2 0 0 1 4 12c0-4.1 3.6-7.5 8-7.5s8 3.1 8 7zM8 12h.01M12 12h.01M16 12h.01"/></svg></span><span><strong>Diskusi dengan CS</strong><small>Minta bantuan pilih laptop</small></span></div>
        </section>

        <section class="catalog-section" id="produk">
            <div class="catalog-heading">
                <div><p class="section-kicker">PILIH SESUAI KEBUTUHAN</p><h2>Temukan laptopmu<span>.</span></h2><p class="catalog-description">Lihat harga dan spesifikasi, lalu tanyakan detailnya langsung ke CS.<small class="catalog-data-note">Harga dan stok ini data simulasi, sesuaikan dengan inventaris toko.</small></p></div>
                <span class="catalog-total"><strong>{{ number_format($products->count()) }}</strong> LAPTOP</span>
            </div>

            <div class="catalog-tools">
                <div class="category-tabs" role="group" aria-label="Filter kategori laptop">
                    <button class="category-tab is-selected" type="button" data-category-filter="all">Semua</button>
                    @foreach ($categories as $category)
                        <button class="category-tab" type="button" data-category-filter="{{ $category }}">{{ $category }}</button>
                    @endforeach
                </div>
                <span class="result-count" data-result-count>Menampilkan 0 produk</span>
            </div>

            <div class="product-grid" id="spesifikasi" data-product-grid>
                @foreach ($products as $product)
                    @php
                        $productImage = match ($product->brand) {
                            'Apple' => 'photo-1517336714731-489689fd1ca8',
                            'ASUS' => 'photo-1496181133206-80ce9b88a853',
                            'Lenovo' => 'photo-1498050108023-c5249f4df085',
                            'Acer' => 'photo-1504707748692-419802cf939d',
                            'HP' => 'photo-1588872657578-7efd1f1555ed',
                            'Dell' => 'photo-1593642632823-8f785ba67e45',
                            'MSI' => 'photo-1603302576837-37561b2e2302',
                            'Axioo' => 'photo-1525547719571-a2d4ac8945e2',
                            default => 'photo-1498050108023-c5249f4df085',
                        };
                    @endphp
                    <article class="product-card" data-product-card data-category="{{ $product->category }}" data-search-text="{{ mb_strtolower($product->name.' '.$product->brand.' '.$product->processor.' '.$product->ram.' '.$product->storage.' '.$product->graphics.' '.$product->category.' '.$product->sku) }}">
                        <div class="product-visual">
                            <img src="https://images.unsplash.com/{{ $productImage }}?auto=format&fit=crop&w=720&q=82" alt="{{ $product->name }}" loading="lazy" data-product-image>
                            <span class="product-category">{{ $product->category }}</span>
                            @if ($product->stock < 10)
                                <span class="stock-note">Stok terbatas</span>
                            @endif
                        </div>
                        <div class="product-info">
                            <p class="product-sku">{{ $product->brand }} <span>·</span> {{ $product->sku }}</p>
                            <h3>{{ $product->name }}</h3>
                            <p class="product-description">{{ $product->description }}</p>
                            <dl class="laptop-specs">
                                <div><dt>Prosesor</dt><dd>{{ $product->processor }}</dd></div>
                                <div><dt>RAM</dt><dd>{{ $product->ram }}</dd></div>
                                <div><dt>Penyimpanan</dt><dd>{{ $product->storage }}</dd></div>
                                <div><dt>Grafis</dt><dd>{{ $product->graphics }}</dd></div>
                                <div><dt>Layar</dt><dd>{{ $product->screen }}</dd></div>
                            </dl>
                            <div class="product-buy-row"><strong class="product-price">Rp{{ number_format((float) $product->price, 0, ',', '.') }}</strong><span class="product-stock">Stok {{ $product->stock }}</span></div>
                            <button class="product-detail-button" type="button" data-detail-open aria-label="Lihat detail {{ $product->name }}"><span>Lihat detail</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6"/></svg></button>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="catalog-more-wrap"><button class="catalog-more" type="button" data-load-more hidden>Muat produk lainnya <span aria-hidden="true">↓</span></button></div>
            <div class="empty-products" data-empty-products hidden><span>⌕</span><strong>Produk tidak ditemukan</strong><small>Coba kata kunci atau kategori lain.</small></div>
        </section>

        <section class="help-band" id="panduan" aria-labelledby="panduan-title">
            <div class="help-inner">
                <div class="help-lead">
                    <div class="help-brand-row">
                        <div class="help-mark" aria-hidden="true">m<span>.</span></div>
                        <p class="section-kicker">PANDUAN KONSULTASI LAPTOP</p>
                    </div>
                    <h2 id="panduan-title">Ceritakan kebutuhanmu.<br>Kami bantu cari laptopnya.</h2>
                    <p class="help-summary">Tidak perlu hafal istilah teknis prosesor atau kartu grafis. Sampaikan aktivitas utama, aplikasi yang sering dipakai, serta batas anggaranmu—CS Microdata akan menyaring katalog dan membandingkan pilihan terbaik secara objektif.</p>
                    <div class="help-highlights" aria-label="Ringkasan informasi katalog dan layanan">
                        <div class="help-highlight-item">
                            <strong>100 Pilihan Konfigurasi</strong>
                            <span>8 merek resmi (Apple, ASUS, Lenovo, Acer, HP, Dell, MSI, Axioo) untuk kuliah, bisnis, kreator, hingga gaming.</span>
                        </div>
                        <div class="help-highlight-item">
                            <strong>Garansi Resmi 12 Bulan</strong>
                            <span>Spesifikasi, stok tercatat, dan perbandingan model bisa ditanyakan gratis lewat ikon <b>Chat CS</b> di pojok kanan bawah.</span>
                        </div>
                    </div>
                </div>

                <div class="help-steps" aria-label="Cara CS membantu memilih laptop">
                    <article class="help-step-card">
                        <span class="help-step-num">01</span>
                        <div>
                            <h3>Sesuaikan Aktivitas &amp; Budget</h3>
                            <p>Sebutkan keperluan kuliah, kerja kantor, desain, editing, atau gaming beserta kisaran dana—CS mencarikan opsi terbaik tanpa melewati anggaran.</p>
                        </div>
                    </article>
                    <article class="help-step-card">
                        <span class="help-step-num">02</span>
                        <div>
                            <h3>Bandingkan Spesifikasi Nyata</h3>
                            <p>Minta perbandingan langsung antar model atau seri (prosesor, RAM, SSD, grafis, dan layar) lengkap dengan harga serta stok tercatat.</p>
                        </div>
                    </article>
                    <article class="help-step-card">
                        <span class="help-step-num">03</span>
                        <div>
                            <h3>Cek Detail SKU &amp; Garansi</h3>
                            <p>Pastikan konfigurasi RAM/SSD, ketersediaan unit, serta informasi garansi resmi 12 bulan sebelum menentukan laptop pilihanmu.</p>
                        </div>
                    </article>
                </div>
            </div>
            <span class="help-orbit orbit-one"></span><span class="help-orbit orbit-two"></span>
        </section>
    </main>

    <footer class="store-footer">
        <div class="footer-main">
            <div class="footer-identity">
                <a class="footer-brand" href="{{ route('dashboard') }}">microdata<span>.</span></a>
                <p class="footer-tagline">Katalog informasi laptop, spesifikasi resmi, perbandingan model, dan konsultasi CS.</p>
            </div>
            <nav class="footer-links" aria-label="Tautan bawah">
                <a href="#produk">Katalog Laptop</a>
                <a href="#panduan">Panduan Memilih</a>
                <a href="mailto:bantuan@microdata.test">bantuan@microdata.test</a>
            </nav>
            <p class="footer-copy">© {{ now()->format('Y') }} Microdata · Layanan Informasi Produk</p>
        </div>
    </footer>

    <button class="cs-launcher" type="button" aria-label="Buka Customer Service" aria-expanded="false" data-chat-open><span class="launcher-pulse"></span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H6a2 2 0 0 1-2-2zM20 13h-3v6h1a2 2 0 0 0 2-2zM17 19a5 5 0 0 1-5 3"/></svg><span>Chat CS</span></button>

    <div class="chat-backdrop" data-chat-backdrop hidden></div>
    <section class="chat-widget" data-chat-widget hidden aria-label="Percakapan dengan customer service">
        <header class="chat-header"><div class="cs-avatar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 0 1 16 0v2M4 13h3v6H6a2 2 0 0 1-2-2zM20 13h-3v6h1a2 2 0 0 0 2-2zM17 19a5 5 0 0 1-5 3"/></svg><i class="cs-online-dot"></i></div><div class="cs-identity"><strong>CS Microdata</strong><span><i data-model-indicator></i><span data-model-status>Mengecek asisten...</span></span></div><button class="chat-close" type="button" aria-label="Tutup percakapan" title="Tutup" data-chat-close><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></header>
        <div class="chat-context"><span>✳</span>Asisten lokal membantu berdasarkan katalog & panduan layanan.</div>
        <div class="chat-thread" data-chat-thread aria-live="polite" aria-relevant="additions text"><div class="welcome-message"><span class="welcome-spark">✳</span><strong>Hai, sedang cari laptop apa?</strong><p>Sebutkan kebutuhan, merek, spesifikasi, atau anggaran yang kamu inginkan.</p><div class="suggestion-list"><button type="button" data-suggestion="Saya mencari laptop untuk kuliah dan kerja harian.">Kuliah & kerja</button><button type="button" data-suggestion="Apa perbedaan MacBook Air M2 dan M3?">MacBook M2/M3</button><button type="button" data-suggestion="Apakah RAM laptop bisa di-upgrade?">Upgrade RAM</button><button type="button" data-suggestion="Berapa lama baterai laptop bisa bertahan?">Daya baterai</button><button type="button" data-suggestion="Laptop apa yang cocok untuk programming?">Programming</button></div></div></div>
        <form class="chat-composer" data-chat-form><label class="sr-only" for="chat-message">Tulis pertanyaan ke CS</label><textarea id="chat-message" rows="1" maxlength="2000" placeholder="Tulis pertanyaanmu di sini..." required data-chat-input></textarea><button class="chat-send" type="submit" title="Kirim pesan" aria-label="Kirim pesan" data-send-button><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 4 16 8-16 8 3-8zM7 12h13"/></svg></button></form>
        <p class="chat-footnote">Jawaban dibantu AI lokal · Data percakapan tersimpan</p>
    </section>

    <div class="detail-backdrop" data-detail-overlay hidden>
        <section class="detail-dialog" role="dialog" aria-modal="true" aria-labelledby="detail-title" tabindex="-1" data-detail-dialog>
            <header class="detail-header"><div><p class="section-kicker">DETAIL KATALOG</p><p class="detail-brand" data-detail-brand></p></div><button class="detail-close" type="button" aria-label="Tutup detail laptop" title="Tutup" data-detail-close><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></header>
            <div class="detail-body"><div class="detail-image-wrap"><img src="" alt="" data-detail-image></div><div class="detail-copy"><span class="detail-category" data-detail-category></span><h2 id="detail-title" data-detail-title></h2><p class="detail-description" data-detail-description></p><div class="detail-price-row"><strong data-detail-price></strong><span data-detail-stock></span></div><h3>Spesifikasi</h3><dl class="detail-specs" data-detail-specs></dl><p class="detail-data-note">Harga dan stok merupakan data simulasi katalog.</p></div></div>
            <footer class="detail-footer"><span>Ingin bertanya tentang model ini?</span><button type="button" data-detail-close>Kembali ke katalog</button></footer>
        </section>
    </div>

</div>
<script src="{{ asset('js/storefront.js') }}" defer></script>
</body>
</html>