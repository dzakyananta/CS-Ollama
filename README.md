# Microdata Laptop — Katalog Informatif & AI Customer Service

Aplikasi web katalog laptop informatif berbasis **Laravel 12**, **PostgreSQL**, dan **Ollama** yang dilengkapi asisten Customer Service (CS) interaktif. Pengunjung dapat menjelajahi spesifikasi dan harga laptop, memfilter kategori maupun kata kunci, melihat detail produk, serta berkonsultasi langsung dengan CS untuk mendapatkan rekomendasi, perbandingan model, cek stok, dan informasi layanan.

> Situs ini dirancang sebagai katalog informasi produk dan layanan konsultasi CS (tidak menyediakan keranjang belanja, checkout, atau transaksi pemesanan online).

---

## Fitur Utama

### 1. Katalog Laptop Interaktif
- **100 konfigurasi laptop** dari 8 merek (`Apple`, `ASUS`, `Lenovo`, `Acer`, `HP`, `Dell`, `MSI`, `Axioo`) dan 4 kategori (`Kerja & Kuliah`, `Bisnis`, `Gaming`, `Kreator & Gaming`).
- Filter kategori cepat, pencarian real-time berdasarkan nama/merek/prosesor/RAM/SSD/GPU/SKU, serta tampilan detail spesifikasi lengkap pada dialog produk.
- Bagian **Panduan Konsultasi Laptop** yang merangkum cara memanfaatkan layanan CS secara terstruktur.

### 2. Customer Service Cerdas (RAG + NLU + Ollama)
- **Pemahaman Bahasa Alami & Toleransi Typo**: Mendukung bahasa percakapan dan singkatan bahasa Indonesia (`brp`, `hrg`, `spek`, `procie`, `vga`, `ngoding`, `ngedit`, `rekomen`, `bandingin`, dll.) serta koreksi typo otomatis (mis. `macboook` → `macbook`, `upgarde` → `upgrade`).
- **Pencarian & Filter Terstruktur**: Mendukung rentang budget (`antara 7 sampai 9 juta`), batas budget (`di bawah 10 juta`, `minimal 15 juta`), pencarian berdasarkan RAM, SSD, GPU (`RTX 4050/4060`, `Iris Xe`, `Radeon`), CPU (`i5`, `i7`, `Ryzen 5/7`, `M2/M3`), layar (`144 Hz`, `Liquid Retina`, `QHD`), maupun kode SKU langsung (`LAP-004` / `lap 4`).
- **Konteks Percakapan Multi-Turn**:
  - Menanggapi follow-up terhadap produk yang sedang dibahas (mis. *"Kalau yang RAM 16 GB SSD 512 GB?"*, *"Stoknya masih ada berapa?"*).
  - Menanggapi follow-up dari daftar rekomendasi bernomor (mis. *"pilih 2"*, *"opsi kedua"*, *"bedanya nomor 1 dan 2 apa?"*, *"ada yang lebih murah?"*).
- **Perbandingan Model Side-by-Side**: Membandingkan dua seri/model beserta prosesor, RAM, SSD, grafis, layar, stok, dan harga tercatat.
- **Anti-Halusinasi & Fallback Andal**: Respons Ollama divalidasi terhadap data katalog dan FAQ di PostgreSQL (`isGroundedReply`). Jika Ollama tidak tersambung atau menghasilkan klaim di luar fakta katalog, CS otomatis menjawab menggunakan data resmi dari database.
- **Penanganan Pertanyaan Umum & Kebijakan Toko**: Menjawab sapaan, ucapan terima kasih, daftar merek/seri tersedia, pertanyaan seputar aplikasi/kebutuhan (kuliah, programming, desain, gaming), produk/merek yang belum tersedia di katalog, serta kebijakan layanan.

---

## Struktur Utama Project

```text
cs-laravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php          # Endpoint chat (/chat, /ollama/status, /conversations/{id}/messages)
│   │   └── DashboardController.php     # Halaman katalog utama (/)
│   ├── Models/                         # Product, Faq, Customer, Conversation, Message
│   └── Services/
│       ├── LaptopRagService.php        # RAG, normalisasi token/typo, ekstraksi filter & konteks
│       └── CustomerServiceResponder.php # Intent percakapan, multi-turn follow-up & fallback katalog
├── database/
│   ├── migrations/                     # Skema tabel customers, products, faqs, conversations, messages
│   └── seeders/
│       └── SupportDemoSeeder.php       # Seeder 100 laptop, 50 pelanggan, 100 FAQ/parafrasa, 5 chat contoh
├── public/
│   ├── css/storefront.css              # Styling halaman toko dan widget chat CS
│   └── js/storefront.js                # Interaksi katalog, filter, modal detail, dan widget chat
├── resources/views/
│   └── storefront.blade.php            # Tampilan utama katalog & panduan konsultasi
├── routes/
│   └── web.php                         # Definisi route web & API chat
├── tests/Feature/
│   ├── ChatTest.php                    # Pengujian RAG, multi-turn CS, rekomendasi & grounding
│   └── ExampleTest.php                 # Pengujian render halaman storefront
├── compose.yaml                        # Konfigurasi container PostgreSQL 17
└── .env.example                        # Template konfigurasi environment
```

---

## Kebutuhan Sistem

- **PHP 8.2+** dengan ekstensi `pdo_pgsql`, `pdo_sqlite` (untuk testing), `mbstring`, `curl`
- **Composer 2**
- **PostgreSQL 16/17+** (lokal, **Docker Desktop**, atau **Podman**)
- **Ollama** untuk menjalankan model LLM lokal

Model default yang digunakan adalah `qwen2.5:0.5b` (sekitar 397 MB) agar tetap ringan dijalankan pada perangkat dengan RAM 8 GB.

---

## Cara Menjalankan Project

### 1. Clone Repository & Masuk ke Folder Project
```powershell
git clone <URL_REPOSITORY_GITHUB>
cd cs-laravel
```

### 2. Install Dependensi PHP & Siapkan Environment
```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Pastikan konfigurasi database dan Ollama di `.env` sesuai dengan lingkungan Anda:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ruang_layanan
DB_USERNAME=postgres
DB_PASSWORD=postgres

OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:0.5b
OLLAMA_TIMEOUT=120
```

### 3. Siapkan & Jalankan Ollama
Pastikan service Ollama aktif, lalu unduh model default:
```powershell
ollama pull qwen2.5:0.5b
```
*(Jika Ollama belum berjalan di background, jalankan `ollama serve` di terminal terpisah.)*

### 4. Jalankan Database PostgreSQL
Pilih salah satu cara berikut:

- **Menggunakan Docker Desktop**:
  ```powershell
  docker compose up -d db
  ```
- **Menggunakan Podman**:
  ```powershell
  podman machine start
  podman run -d --name ruang-layanan-postgres `
    -e POSTGRES_DB=ruang_layanan `
    -e POSTGRES_USER=postgres `
    -e POSTGRES_PASSWORD=postgres `
    -p 5432:5432 `
    -v ruang_layanan_data:/var/lib/postgresql/data `
    docker.io/library/postgres:16-alpine
  ```
- **Menggunakan PostgreSQL Lokal**: buat database bernama `ruang_layanan` dan sesuaikan `DB_*` di file `.env`.

### 5. Jalankan Migrasi & Seeder
```powershell
php artisan migrate --seed
```
> **Catatan:** Data harga, stok, pelanggan, dan FAQ dari `SupportDemoSeeder` merupakan data simulasi. Sesuaikan dengan data inventaris toko sebenarnya sebelum dipublikasikan ke produksi.

### 6. Jalankan Server Aplikasi
```powershell
php artisan serve
```

Buka browser dan akses:
```text
http://127.0.0.1:8000
```
Klik tombol **Chat CS** di pojok kanan bawah untuk mulai berinteraksi dengan Customer Service.

---

## Menjalankan Pengujian

Pengujian otomatis menggunakan SQLite in-memory dan respons HTTP tiruan (`Http::fake`) sehingga tidak mengubah database utama maupun membutuhkan Ollama aktif:

```powershell
php artisan test
```
