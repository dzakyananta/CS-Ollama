<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerServiceResponder
{
    public function tryRecommendationFollowUp(string $message, ?Message $previousAssistantMessage, LaptopRagService $rag): ?array
    {
        if (! $previousAssistantMessage || $previousAssistantMessage->source !== 'recommendations') {
            return null;
        }

        preg_match_all('/^([1-3])\.\s+\[(LAP-\d{3})\]/m', $previousAssistantMessage->body, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            return null;
        }

        $options = [];
        foreach ($matches as $match) {
            $product = Product::where('sku', $match[2])->where('is_active', true)->first();
            if ($product) {
                $options[(int) $match[1]] = $product;
            }
        }

        if ($options === []) {
            return null;
        }

        $lower = Str::lower(trim($message));
        $normalizedNumbers = str_replace(
            ['pertama', 'kesatu', 'ke-1', 'kedua', 'ke-2', 'ketiga', 'ke-3'],
            ['1', '1', '1', '2', '2', '3', '3'],
            $lower
        );

        if (preg_match('/^\s*(?:(?:saya\s+|aku\s+|mau\s+|ambil\s+)?(?:pilih|ambil|mau|lihat|cek)?\s*)?(?:(?:yang\s+)?(?:nomor|no\.?|opsi)\s*)?([1-3])(?:\s*(?:ya|dong|aja|deh|saja))?[.!]?\s*$/iu', $normalizedNumbers, $selection)) {
            $chosen = $options[(int) $selection[1]] ?? null;
            if ($chosen) {
                return ['message' => $this->selectedProductReply($chosen), 'source' => 'catalog'];
            }
        }

        if (preg_match_all('/\b([1-3])\b/', $normalizedNumbers, $compareNums) && count(array_unique($compareNums[1])) >= 2 && Str::contains($lower, ['beda', 'banding', 'mending', 'atau', 'dan', 'sama', 'vs'])) {
            $unique = array_values(array_unique($compareNums[1]));
            $first = $options[(int) $unique[0]] ?? null;
            $second = $options[(int) $unique[1]] ?? null;
            if ($first && $second) {
                return ['message' => $this->comparisonReply(collect([$first, $second])), 'source' => 'catalog'];
            }
        }

        if (preg_match('/\b([1-3])\b/', $normalizedNumbers, $optionNum) && Str::contains($lower, ['spek', 'spesifikasi', 'detail', 'harga', 'stok', 'layar', 'prosesor', 'ram', 'ssd', 'grafis', 'gpu', 'opsi', 'nomor', 'no'])) {
            $target = $options[(int) $optionNum[1]] ?? null;
            if ($target) {
                return ['message' => $this->productFactReply($target, $message), 'source' => 'catalog'];
            }
        }

        if (Str::contains($lower, ['lebih murah', 'turun harga', 'kebesaran', 'kemahalan', 'alternatif lain', 'pilihan lain'])) {
            $minShownPrice = collect($options)->min(fn (Product $p) => (float) $p->price);
            $cheaper = Product::where('is_active', true)
                ->where('price', '<', $minShownPrice)
                ->orderByDesc('price')
                ->get();

            if ($cheaper->isEmpty()) {
                return [
                    'message' => 'Pilihan di atas sudah termasuk yang paling terjangkau di katalog kami (mulai Rp'.number_format((float) $minShownPrice, 0, ',', '.').'). Apakah kamu ingin cek spesifikasi detail salah satu nomor di atas?',
                    'source' => 'catalog',
                ];
            }

            $picked = $cheaper->unique(fn (Product $p) => $rag->seriesKey($p))->take(3)->values()->all();

            return [
                'message' => $this->recommendationReply([
                    'items' => $picked,
                    'budget' => (int) ($minShownPrice - 1),
                    'over_budget' => false,
                    'category' => null,
                ]),
                'source' => 'recommendations',
            ];
        }

        return null;
    }

    public function tryDirectReply(string $message, array $retrieval, array $history, LaptopRagService $rag): ?array
    {
        $lower = Str::lower(trim($message));

        if ($policyReply = $this->tryStorePolicyIntent($lower, $retrieval)) {
            return $policyReply;
        }

        if ($retrieval['unavailable_request'] !== null && ! $retrieval['has_specific_model']) {
            return $this->unavailableReply($retrieval['unavailable_request'], $retrieval['all_products'], $rag);
        }

        if ($retrieval['filters']['sku'] !== null) {
            $sku = $retrieval['filters']['sku'];
            $product = $retrieval['all_products']->firstWhere('sku', $sku);
            if ($product) {
                return ['message' => $this->productFactReply($product, $message), 'source' => 'catalog'];
            }

            return [
                'message' => "Maaf, SKU {$sku} tidak ditemukan pada katalog aktif kami. Kamu bisa menyebut nama model, merek, atau SKU lain (LAP-001 sampai LAP-100).",
                'source' => 'catalog',
            ];
        }

        if ($this->isCatalogOverviewQuery($lower) && ! $retrieval['has_specific_model']) {
            return $this->catalogOverviewReply($lower, $retrieval['all_products'], $rag);
        }

        if ($retrieval['comparison_request']) {
            $compared = collect($retrieval['comparison_products']);
            if ($compared->count() >= 2) {
                return ['message' => $this->comparisonReply($compared), 'source' => 'catalog'];
            }
            if ($retrieval['faq_answer']) {
                return ['message' => $retrieval['faq_answer'], 'source' => 'faq'];
            }
        }

        if ($retrieval['is_lowest_price'] || $retrieval['is_highest_price']) {
            return $this->extremePriceReply($retrieval);
        }

        if ($followUp = $this->tryProductFollowUp($message, $retrieval, $history, $rag)) {
            return $followUp;
        }

        if (! empty($retrieval['recommendations']['items'])) {
            return ['message' => $this->recommendationReply($retrieval['recommendations']), 'source' => 'recommendations'];
        }

        if ($retrieval['faq_answer']) {
            return ['message' => $retrieval['faq_answer'], 'source' => 'faq'];
        }

        if ($useCase = $this->tryUseCaseGuidance($lower, $retrieval['all_products'], $rag)) {
            return $useCase;
        }

        if ($greeting = $this->tryPureGreetingOrThanks($lower, $retrieval)) {
            return $greeting;
        }

        return null;
    }

    public function recommendationReply(array $recommendations): string
    {
        $items = $recommendations['items'];
        $budget = $recommendations['budget'];
        $budgetMin = $recommendations['budget_min'] ?? null;
        $category = $recommendations['category'];
        $firstPrice = number_format((float) $items[0]->price, 0, ',', '.');

        if ($recommendations['over_budget'] && $budget !== null) {
            $intro = 'Aku belum menemukan laptop dalam batas Rp'.number_format((float) $budget, 0, ',', '.');
            if ($category) {
                $intro .= " untuk kategori {$category}";
            }
            $intro .= '. Ini opsi terdekat yang tersedia:';
        } elseif ($budgetMin !== null && $budget !== null) {
            $intro = 'Boleh. Untuk kisaran budget Rp'.number_format((float) $budgetMin, 0, ',', '.').' - Rp'.number_format((float) $budget, 0, ',', '.').', ini pilihan yang paling sesuai:';
        } elseif ($budget !== null) {
            $intro = 'Boleh. Untuk budget sekitar Rp'.number_format((float) $budget, 0, ',', '.').', ini pilihan yang paling mendekati:';
        } elseif ($budgetMin !== null) {
            $intro = 'Boleh. Untuk budget mulai Rp'.number_format((float) $budgetMin, 0, ',', '.').', ini pilihan yang tersedia:';
        } else {
            $intro = 'Boleh. Ini '.count($items).' pilihan yang paling sesuai dengan kebutuhanmu:';
        }

        if (! empty($recommendations['exact_filter_empty'])) {
            $intro = 'Kombinasi filter persisnya belum ada di katalog, ini opsi terdekat yang paling sesuai:';
        }

        $lines = [$intro];
        foreach ($items as $index => $product) {
            $number = $index + 1;
            $price = number_format((float) $product->price, 0, ',', '.');
            $lines[] = "{$number}. [{$product->sku}] {$product->name} — Rp{$price} | {$product->category} | {$product->processor} | RAM {$product->ram} | SSD {$product->storage}";
        }

        $reason = $recommendations['over_budget']
            ? 'Yang paling terjangkau adalah opsi 1, dengan harga Rp'.$firstPrice.'.'
            : ($budget !== null
                ? 'Yang paling saya rekomendasikan adalah opsi 1 karena paling dekat dengan budget tanpa melewatinya.'
                : 'Yang paling saya rekomendasikan adalah opsi 1 karena paling sesuai dengan kebutuhan yang kamu sebutkan.');

        $lines[] = $reason;
        $optionNumbers = match (count($items)) {
            1 => '1',
            2 => '1 atau 2',
            default => '1, 2, atau 3',
        };
        $lines[] = "Balas {$optionNumbers} untuk melihat detail pilihanmu.";

        return implode("\n", $lines);
    }

    public function selectedProductReply(Product $product): string
    {
        return "Siap, kamu memilih {$product->name} [{$product->sku}]. Harganya Rp".number_format((float) $product->price, 0, ',', '.')." dengan stok {$product->stock} unit. Spesifikasi utamanya {$product->processor}, RAM {$product->ram}, SSD {$product->storage}, {$product->graphics}, dan layar {$product->screen}. Ada detail lain yang ingin kamu cek?";
    }

    public function productFactReply(Product $product, string $query): string
    {
        $lower = Str::lower($query);
        $price = 'Rp'.number_format((float) $product->price, 0, ',', '.');

        if (Str::contains($lower, ['stok', 'stock', 'ready', 'tersedia']) && ! Str::contains($lower, ['harga', 'spek', 'spesifikasi', 'prosesor'])) {
            return "Untuk {$product->name} [{$product->sku}], stok yang tercatat saat ini {$product->stock} unit dengan harga Rp".number_format((float) $product->price, 0, ',', '.').'. Mau cek konfigurasi RAM/SSD lainnya?';
        }

        return "Untuk {$product->name} [{$product->sku}], harga yang tercatat {$price} dengan stok {$product->stock} unit. Spesifikasi utamanya: prosesor {$product->processor}, RAM {$product->ram}, SSD {$product->storage}, grafis {$product->graphics}, layar {$product->screen}. Ada kebutuhan atau anggaran tertentu yang ingin dicocokkan?";
    }

    public function catalogReplyFromContext(string $context, string $query, ?Product $primaryProduct = null): ?string
    {
        if ($primaryProduct) {
            return $this->productFactReply($primaryProduct, $query);
        }

        $line = collect(explode("\n", $context))->first(fn (string $line) => Str::contains($line, ' | merek '));

        if (! $line) {
            return null;
        }

        $fields = explode(' | ', $line);
        $priceField = collect($fields)->first(fn (string $f) => str_starts_with($f, 'harga ')) ?? '';
        $stockField = collect($fields)->first(fn (string $f) => str_starts_with($f, 'stok ')) ?? '';
        $specFields = collect($fields)->filter(fn (string $f) => Str::startsWith($f, ['prosesor ', 'RAM ', 'SSD ', 'grafis ', 'layar ']))->implode(', ');

        $price = str_replace('harga ', '', $priceField);
        $stock = str_replace('stok ', '', $stockField);

        return 'Untuk '.$fields[0].', harga yang tercatat '.$price.' dengan stok '.$stock.' unit. Spesifikasi utamanya: '.$specFields.'. Ada kebutuhan atau anggaran tertentu yang ingin dicocokkan?';
    }

    private function tryPureGreetingOrThanks(string $lower, array $retrieval): ?array
    {
        $hasCatalogIntent = $retrieval['has_specific_model']
            || $retrieval['filters']['brand'] !== null
            || $retrieval['filters']['budget_max'] !== null
            || $retrieval['filters']['budget_min'] !== null
            || ! empty($retrieval['filters']['categories'])
            || $retrieval['filters']['ram'] !== null
            || $retrieval['filters']['storage'] !== null
            || $retrieval['filters']['gpu'] !== null
            || $retrieval['filters']['cpu'] !== null;

        if ($hasCatalogIntent) {
            return null;
        }

        if (preg_match('/^(?:terima\s*kasih|makasih|mksh|thx|tengkyu|trims|tq|oke+\s*(?:siap|makasih|thanks?)?|ok\s*(?:siap|makasih)?|sip+\s*(?:makasih)?|mantap|sudah\s+jelas|cukup|bye|dadah|sampai\s+jumpa)(?:\s+(?:kak|min|admin|cs|mas|mba|mbak|bro|gan))?[.!?\s]*$/iu', $lower)) {
            return [
                'message' => 'Sama-sama! Senang bisa membantu. Kalau nanti ada pertanyaan lagi soal harga, spesifikasi, stok, atau rekomendasi laptop, silakan chat lagi kapan saja ya.',
                'source' => 'faq',
            ];
        }

        if (preg_match('/^(?:halo+|hai+|hi+|hello+|selamat\s+(?:pagi|siang|sore|malam)|pagi|siang|sore|malam|assalamu\s*\'?alaikum|permisi|tes|test|p)(?:\s+(?:kak|min|admin|cs|mas|mba|mbak|bro|gan))?[.!?\s]*$/iu', $lower)) {
            return [
                'message' => 'Halo! Selamat datang di CS Microdata. Ada yang bisa saya bantu hari ini? Kamu bisa tanya harga, stok, spesifikasi laptop, bandingkan model, atau minta rekomendasi sesuai budget dan kebutuhanmu.',
                'source' => 'faq',
            ];
        }

        if (Str::contains($lower, ['bisa bantu apa', 'kamu bisa apa', 'bisa apa aja', 'bisa apa saja', 'siapa kamu', 'cara pakai', 'fitur cs'])) {
            return [
                'message' => 'Saya asisten CS Microdata. Saya bisa membantu cek harga, stok, dan spesifikasi lengkap laptop di katalog, merekomendasikan laptop sesuai budget atau kebutuhan (kuliah, kerja, bisnis, desain, gaming), membandingkan dua model, serta menjawab pertanyaan garansi dan panduan layanan.',
                'source' => 'faq',
            ];
        }

        return null;
    }

    private function tryStorePolicyIntent(string $lower, array $retrieval): ?array
    {
        if (Str::contains($lower, ['checkout', 'keranjang', 'cara beli', 'cara pesan', 'beli online', 'order', 'pemesanan', 'ongkir', 'pengiriman', 'kirim ke', 'ekspedisi', 'cod', 'pembayaran', 'bayar pakai', 'cicilan', 'kredit', 'transfer', 'rekening'])
            && ! Str::contains($lower, ['rekomendasi', 'banding'])) {
            return [
                'message' => 'Situs Microdata saat ini berfungsi sebagai katalog informasi laptop (belum menyediakan keranjang, checkout, pembayaran online, atau pengiriman langsung). Namun saya siap membantu cek harga tercatat, stok, spesifikasi, dan rekomendasi laptop. Untuk tindak lanjut pembelian atau info transaksi, silakan hubungi bantuan@microdata.test.',
                'source' => 'faq',
            ];
        }

        if (Str::contains($lower, ['promo', 'diskon', 'potongan harga', 'cashback', 'nego', 'voucher', 'kupon'])) {
            return [
                'message' => 'Harga yang tampil di katalog merupakan harga tercatat saat ini dan belum ada informasi promo atau diskon khusus di sistem. Jika kamu memiliki batas anggaran tertentu, sebutkan saja agar saya carikan pilihan yang paling mendekati.',
                'source' => 'faq',
            ];
        }

        if (Str::contains($lower, ['alamat toko', 'lokasi toko', 'tokonya dimana', 'tokonya di mana', 'jam buka', 'buka jam', 'cs manusia', 'hubungi admin', 'nomor wa', 'whatsapp'])) {
            return [
                'message' => 'Untuk informasi alamat toko, jam operasional, atau kontak tim layanan lebih lanjut, silakan hubungi bantuan@microdata.test. Di chat ini, saya tetap bisa langsung membantu cek harga, stok, spesifikasi, dan rekomendasi laptop dari katalog.',
                'source' => 'faq',
            ];
        }

        if (Str::contains($lower, ['bekas', 'second', 'seken', 'refurbished', 'kondisi baru', 'baru atau bekas']) && ! $retrieval['comparison_request']) {
            return [
                'message' => 'Laptop pada katalog tercatat dengan garansi resmi 12 bulan. Untuk kepastian kondisi unit atau kelengkapan paket pada model tertentu, sebutkan nama atau SKU laptopnya atau hubungi bantuan@microdata.test.',
                'source' => 'faq',
            ];
        }

        return null;
    }

    private function tryProductFollowUp(string $message, array $retrieval, array $history, LaptopRagService $rag): ?array
    {
        $lower = Str::lower($message);
        $hasOwnIntent = $retrieval['has_specific_model']
            || $retrieval['filters']['brand'] !== null
            || $retrieval['filters']['budget_max'] !== null
            || $retrieval['filters']['budget_min'] !== null
            || ! empty($retrieval['filters']['categories'])
            || Str::contains($lower, ['rekomendasi', 'rekomendasikan', 'rekomen', 'cari laptop', 'carikan', 'pilihkan', 'daftar', 'apa saja', 'apa aja']);

        if ($hasOwnIntent || $history === []) {
            return null;
        }

        $lastProduct = $rag->lastDiscussedProductFromHistory($retrieval['all_products'], $history);
        if (! $lastProduct) {
            return null;
        }

        $filters = $retrieval['filters'];

        if ($filters['ram'] !== null || $filters['storage'] !== null) {
            $series = $rag->seriesKey($lastProduct);
            $sameSeries = $retrieval['all_products']->filter(fn (Product $p) => $rag->seriesKey($p) === $series);
            $match = $sameSeries->first(function (Product $p) use ($filters) {
                $okRam = $filters['ram'] === null || $p->ram === $filters['ram'];
                $okStorage = $filters['storage'] === null || $p->storage === $filters['storage'];

                return $okRam && $okStorage;
            });

            if ($match) {
                return ['message' => $this->productFactReply($match, $message), 'source' => 'catalog'];
            }

            $availableConfigs = $sameSeries->map(fn (Product $p) => "{$p->ram} / {$p->storage} (Rp".number_format((float) $p->price, 0, ',', '.').')')->implode(', ');

            return [
                'message' => "Untuk seri {$series}, konfigurasi yang diminta belum tersedia. Pilihan yang ada di katalog: {$availableConfigs}.",
                'source' => 'catalog',
            ];
        }

        if (Str::contains($lower, ['stok', 'stock', 'ready', 'tersedia', 'harga', 'price', 'prosesor', 'processor', 'ram', 'ssd', 'grafis', 'gpu', 'layar', 'screen', 'detail', 'spek', 'spesifikasi', 'garansi', 'sku'])) {
            return ['message' => $this->productFactReply($lastProduct, $message), 'source' => 'catalog'];
        }

        return null;
    }

    private function tryUseCaseGuidance(string $lower, Collection $products, LaptopRagService $rag): ?array
    {
        $useCases = [
            'gaming_apps' => ['valorant', 'gta', 'genshin', 'cyberpunk', 'dota', 'cs2', 'fifa', 'roblox', 'game berat'],
            'creative_apps' => ['premiere', 'after effects', 'after effect', 'blender', 'autocad', 'photoshop', 'illustrator', 'figma', 'davinci', 'capcut', 'video editing'],
            'coding_apps' => ['python', 'javascript', 'react', 'laravel', 'android studio', 'docker', 'vs code', 'vscode', 'flutter', 'java'],
            'office_apps' => ['word', 'excel', 'powerpoint', 'ppt', 'zoom', 'google meet', 'skripsi', 'akuntansi', 'admin'],
        ];

        $matchedType = null;
        foreach ($useCases as $type => $keywords) {
            if (Str::contains($lower, $keywords)) {
                $matchedType = $type;
                break;
            }
        }

        if ($matchedType === null) {
            return null;
        }

        $filtered = match ($matchedType) {
            'gaming_apps' => $products->whereIn('category', ['Gaming', 'Kreator & Gaming']),
            'creative_apps' => $products->filter(fn (Product $p) => in_array($p->category, ['Kreator & Gaming', 'Gaming'], true) || Str::contains($p->ram, ['16 GB', '32 GB'])),
            'coding_apps' => $products->filter(fn (Product $p) => Str::contains($p->ram, ['16 GB', '24 GB', '32 GB'])),
            'office_apps' => $products->whereIn('category', ['Kerja & Kuliah', 'Bisnis']),
        };

        $picks = $filtered->sortBy('price')->unique(fn (Product $p) => $rag->seriesKey($p))->take(3)->values()->all();

        $summary = match ($matchedType) {
            'gaming_apps' => 'Untuk game modern, pertimbangkan laptop dengan GPU diskrit (NVIDIA GeForce RTX 4050/4060) dan layar 144 Hz - 165 Hz. Data FPS per game tidak dicatat di katalog, namun ini opsi kategori Gaming yang tersedia:',
            'creative_apps' => 'Untuk desain dan editing, sebaiknya pilih RAM minimal 16 GB serta layar tajam atau GPU diskrit. Ini beberapa pilihan yang relevan dari katalog:',
            'coding_apps' => 'Untuk programming dan menjalankan IDE atau tools berat, RAM minimal 16 GB sangat membantu kelancaran multitasking. Ini beberapa pilihan RAM 16 GB yang tersedia:',
            'office_apps' => 'Untuk Office, Zoom, dan tugas harian/skripsi, laptop kategori Kerja & Kuliah dengan SSD sudah memadai. Ini pilihan yang tersedia:',
        };

        $lines = [$summary];
        foreach ($picks as $index => $product) {
            $num = $index + 1;
            $price = number_format((float) $product->price, 0, ',', '.');
            $lines[] = "{$num}. [{$product->sku}] {$product->name} — Rp{$price} | {$product->processor} | {$product->graphics}";
        }
        $lines[] = 'Balas 1, 2, atau 3 untuk cek detail, atau sebutkan budgetmu.';

        return ['message' => implode("\n", $lines), 'source' => 'recommendations'];
    }

    private function unavailableReply(array $unavailable, Collection $products, LaptopRagService $rag): array
    {
        $term = Str::title($unavailable['term']);
        $brands = $products->pluck('brand')->unique()->implode(', ');

        if ($unavailable['type'] === 'merek') {
            return [
                'message' => "Maaf, merek {$term} belum tersedia di katalog Microdata saat ini. Merek yang tersedia adalah {$brands}. Mau saya carikan alternatif terbaik sesuai kebutuhan dan budgetmu?",
                'source' => 'catalog',
            ];
        }

        if ($unavailable['type'] === 'seri/model') {
            $seriesList = $products->map(fn (Product $p) => $rag->seriesKey($p))->unique()->take(5)->implode(', ');

            return [
                'message' => "Maaf, seri {$term} tidak tersedia di katalog kami saat ini. Beberapa seri yang tersedia antara lain {$seriesList}. Kamu bisa sebutkan kebutuhan atau budget agar saya bantu carikan alternatif terdekat.",
                'source' => 'catalog',
            ];
        }

        return [
            'message' => "Maaf, spesifikasi {$term} tidak tercatat pada katalog Microdata saat ini (pilihan GPU tersedia hingga RTX 4060, prosesor Intel Core i5/i7, AMD Ryzen 5/7, serta Apple M2/M3, dengan RAM 8 GB hingga 32 GB). Mau saya rekomendasikan opsi terdekat yang tersedia?",
            'source' => 'catalog',
        ];
    }

    private function isCatalogOverviewQuery(string $lower): bool
    {
        return Str::contains($lower, [
            'merek apa saja', 'merek apa aja', 'brand apa saja', 'brand apa aja', 'jual merek apa',
            'kategori apa saja', 'kategori apa aja', 'seri apa saja', 'seri laptop',
            'ada laptop apa saja', 'ada laptop apa aja', 'jual laptop apa saja', 'daftar laptop', 'list laptop',
        ]);
    }

    private function catalogOverviewReply(string $lower, Collection $products, LaptopRagService $rag): array
    {
        $byBrand = $products->groupBy('brand')->map(function (Collection $items, string $brand) {
            $minPrice = number_format((float) $items->min('price'), 0, ',', '.');

            return "{$brand} (mulai Rp{$minPrice})";
        })->implode(', ');

        if (Str::contains($lower, ['merek', 'brand'])) {
            return [
                'message' => "Di katalog Microdata tersedia 8 merek laptop: {$byBrand}. Sebutkan merek atau budget yang kamu incar untuk melihat pilihannya!",
                'source' => 'catalog',
            ];
        }

        $series = $products->groupBy(fn (Product $p) => $rag->seriesKey($p))
            ->map(fn (Collection $items, string $name) => $name.' (mulai Rp'.number_format((float) $items->min('price'), 0, ',', '.').')')
            ->values()
            ->implode('; ');

        return [
            'message' => "Katalog kami mencakup kategori Kerja & Kuliah, Bisnis, Gaming, serta Kreator & Gaming dengan 10 seri laptop: {$series}. Kamu lagi cari untuk kebutuhan apa atau budget berapa?",
            'source' => 'catalog',
        ];
    }

    private function comparisonReply(Collection $products): string
    {
        $lines = ['Berikut perbandingan berdasarkan data katalog:'];

        foreach ($products as $index => $product) {
            $num = $index + 1;
            $price = number_format((float) $product->price, 0, ',', '.');
            $lines[] = "{$num}. {$product->name} [{$product->sku}] — Rp{$price} | Stok {$product->stock} | {$product->processor} | RAM {$product->ram} | SSD {$product->storage} | {$product->graphics} | {$product->screen}";
        }

        $cheapest = $products->sortBy('price')->first();
        $lines[] = "Dari segi harga tercatat, {$cheapest->name} lebih terjangkau (Rp".number_format((float) $cheapest->price, 0, ',', '.').'). Ingin fokus membandingkan performa grafis, layar, atau harga?';

        return implode("\n", $lines);
    }

    private function extremePriceReply(array $retrieval): array
    {
        $pool = collect($retrieval['filtered_products']);
        if ($pool->isEmpty()) {
            $pool = $retrieval['all_products'];
        }

        $sorted = $retrieval['is_highest_price']
            ? $pool->sortByDesc(fn (Product $p) => (float) $p->price)->values()
            : $pool->sortBy(fn (Product $p) => (float) $p->price)->values();

        $top = $sorted->first();
        if (! $top) {
            return ['message' => 'Maaf, belum ada produk aktif yang cocok di katalog saat ini.', 'source' => 'catalog'];
        }

        $label = $retrieval['is_highest_price'] ? 'tertinggi' : 'paling terjangkau';
        $price = number_format((float) $top->price, 0, ',', '.');

        return [
            'message' => "Pilihan dengan harga {$label} yang sesuai adalah {$top->name} [{$top->sku}] seharga Rp{$price} (stok {$top->stock} unit). Spesifikasinya: {$top->processor}, RAM {$top->ram}, SSD {$top->storage}, {$top->graphics}, layar {$top->screen}. Mau cek alternatif lain?",
            'source' => 'catalog',
        ];
    }
}
