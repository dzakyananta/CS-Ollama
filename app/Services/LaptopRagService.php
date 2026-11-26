<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LaptopRagService
{
    private const STOP_WORDS = [
        'ada', 'aku', 'apa', 'apakah', 'atau', 'bagaimana', 'bantu', 'berapa', 'bisa',
        'buat', 'dan', 'dari', 'di', 'dong', 'dengan', 'harga', 'ini', 'itu', 'kah',
        'ke', 'kok', 'laptop', 'mana', 'mau', 'oleh', 'pada', 'produk', 'saja', 'saya',
        'sebuah', 'untuk', 'yang', 'ya', 'yg',
        'kak', 'min', 'mas', 'mba', 'mbak', 'gan', 'bro', 'sis', 'permisi', 'tolong',
        'mohon', 'kira', 'misalnya', 'kalau', 'kalo', 'misal', 'sih', 'nih', 'tuh',
        'deh', 'yah', 'kan', 'pun', 'sama', 'soal', 'tentang', 'mengenai', 'seputar',
        'info', 'tanya', 'coba', 'minta', 'lagi', 'aja', 'dgn', 'utk', 'nya',
    ];

    private const ALIASES = [
        'laptp' => 'laptop',
        'lapop' => 'laptop',
        'leptop' => 'laptop',
        'macbok' => 'macbook',
        'mackbook' => 'macbook',
        'maccbook' => 'macbook',
        'batre' => 'baterai',
        'batrai' => 'baterai',
        'bateray' => 'baterai',
        'battrey' => 'baterai',
        'baterainya' => 'baterai',
        'upgarde' => 'upgrade',
        'upgrde' => 'upgrade',
        'upgrage' => 'upgrade',
        'spesifkasi' => 'spesifikasi',
        'spesifiasi' => 'spesifikasi',
        'speks' => 'spesifikasi',
        'spek' => 'spesifikasi',
        'speknya' => 'spesifikasi',
        'spec' => 'spesifikasi',
        'specs' => 'spesifikasi',
        'procesor' => 'prosesor',
        'proseser' => 'prosesor',
        'procie' => 'prosesor',
        'proci' => 'prosesor',
        'proc' => 'prosesor',
        'cpu' => 'prosesor',
        'vga' => 'grafis',
        'graphic' => 'grafis',
        'screen' => 'layar',
        'display' => 'layar',
        'layarnya' => 'layar',
        'gamming' => 'gaming',
        'gameing' => 'gaming',
        'ngegame' => 'gaming',
        'gamer' => 'gaming',
        'garnty' => 'garansi',
        'garansii' => 'garansi',
        'garansinya' => 'garansi',
        'bergaransi' => 'garansi',
        'diupgrade' => 'upgrade',
        'mengupgrade' => 'upgrade',
        'rekomendasikan' => 'rekomendasi',
        'dibandingkan' => 'bandingkan',
        'membandingkan' => 'bandingkan',
        'spesifikasinya' => 'spesifikasi',
        'prosesornya' => 'prosesor',
        'grafisnya' => 'grafis',
        'kapasitasnya' => 'kapasitas',
        'ukurannya' => 'ukuran',
        'penyimapanan' => 'penyimpanan',
        'penyimpnan' => 'penyimpanan',
        'murahh' => 'murah',
        'murmer' => 'murah',
        'terjangkau' => 'murah',
        'termura' => 'termurah',
        'brp' => 'berapa',
        'brapa' => 'berapa',
        'brpa' => 'berapa',
        'hrg' => 'harga',
        'hrga' => 'harga',
        'harganya' => 'harga',
        'kulia' => 'kuliah',
        'krj' => 'kerja',
        'kantoran' => 'kantor',
        'ngoding' => 'programming',
        'koding' => 'programming',
        'coding' => 'programming',
        'programmer' => 'programming',
        'developer' => 'programming',
        'ngedit' => 'editing',
        'design' => 'desain',
        'creator' => 'kreator',
        'rekomen' => 'rekomendasi',
        'rekom' => 'rekomendasi',
        'rekomenin' => 'rekomendasi',
        'saranin' => 'saran',
        'cariin' => 'carikan',
        'pilihin' => 'pilihkan',
        'bandingin' => 'bandingkan',
        'komparasi' => 'bandingkan',
        'stoknya' => 'stok',
        'ready' => 'stok',
        'avail' => 'tersedia',
        'ori' => 'original',
        'asli' => 'original',
        'touch' => 'touchscreen',
        'win' => 'windows',
        'beratnya' => 'berat',
        'bobot' => 'berat',
    ];

    private const BRANDS = ['Apple', 'ASUS', 'Lenovo', 'Acer', 'HP', 'Dell', 'MSI', 'Axioo'];

    private const UNAVAILABLE_KEYWORDS = [
        'merek' => ['samsung', 'xiaomi', 'huawei', 'zyrex', 'advan', 'razer', 'alienware', 'toshiba', 'microsoft', 'surface', 'gigabyte', 'infinix', 'tecno', 'realme'],
        'seri/model' => ['macbook pro', 'rog', 'zenbook', 'legion', 'thinkpad', 'yoga', 'loq', 'predator', 'nitro', 'swift', 'pavilion', 'omen', 'envy', 'spectre', 'xps', 'latitude', 'vostro', 'katana', 'cyborg', 'stealt', 'stealth', 'tuf f15'],
        'spesifikasi' => ['rtx 3050', 'rtx 3060', 'rtx 3070', 'rtx 4070', 'rtx 4080', 'rtx 4090', 'gtx', 'core i3', 'core i9', 'ryzen 3', 'ryzen 9', 'apple m1', 'apple m4', 'chip m1', 'chip m4', '64 gb', '128 gb', '4 gb'],
    ];

    public function retrieve(string $query, array $history = []): array
    {
        $faqs = Faq::where('is_active', true)->get(['question', 'answer', 'keywords']);
        $products = Product::where('is_active', true)->get([
            'id', 'sku', 'name', 'brand', 'category', 'processor', 'ram', 'storage', 'graphics', 'screen', 'price', 'stock', 'description',
        ]);

        $vocabulary = $this->buildVocabulary($faqs, $products);
        $corrections = [];
        $terms = collect($this->tokens($query))->map(function (string $term) use ($vocabulary, &$corrections) {
            $normalized = $this->correctToken($term, $vocabulary);

            if ($normalized !== $term) {
                $corrections[$term] = $normalized;
            }

            return $normalized;
        })->unique()->values();

        $normalizedQuery = $terms->implode(' ');
        $lowerQuery = Str::lower($query);

        $faqMatches = $this->matchFaqs($faqs, $terms, $lowerQuery);
        $productMatches = $this->matchProducts($products, $terms);
        $filters = $this->extractFilters($query, $normalizedQuery, $terms);

        $isLowestPriceRequest = Str::contains($lowerQuery, ['termurah', 'paling murah', 'harga terendah', 'paling terjangkau']);
        $isHighestPriceRequest = Str::contains($lowerQuery, ['termahal', 'paling mahal', 'harga tertinggi']);
        $isComparisonRequest = $this->isComparisonQuery($lowerQuery);
        $isExplicitRecommendationPhrase = Str::contains($lowerQuery, [
            'rekomendasi', 'rekomendasikan', 'rekomen', 'rekomenin', 'saran', 'saranin',
            'pilihkan', 'pilihin', 'carikan', 'cariin', 'cari laptop', 'pilihan laptop',
            'sebaiknya yang mana', 'mending mana', 'bagusnya yang mana',
        ]);

        $hasFilterDiscovery = $filters['budget_max'] !== null
            || $filters['budget_min'] !== null
            || (! empty($filters['categories']) && ! $this->hasSpecificModel($productMatches, $terms, $lowerQuery))
            || (! empty($filters['brand']) && ! $this->hasSpecificModel($productMatches, $terms, $lowerQuery) && Str::contains($lowerQuery, ['apa saja', 'apa aja', 'ada apa', 'daftar', 'list', 'pilihan', 'tersedia', 'punya', 'jual', 'rekomendasi', 'budget', 'murah', 'mahal']))
            || (($filters['ram'] !== null || $filters['ram_min'] !== null || $filters['storage'] !== null || $filters['gpu'] !== null || $filters['cpu'] !== null || $filters['screen'] !== null)
                && ! $this->hasSpecificModel($productMatches, $terms, $lowerQuery));

        $isRecommendationRequest = $isExplicitRecommendationPhrase
            || $filters['budget_max'] !== null
            || $filters['budget_min'] !== null
            || ($hasFilterDiscovery && ! $isLowestPriceRequest && ! $isHighestPriceRequest && ! $isComparisonRequest);

        $filteredPool = $this->applyStructuredFilters($products, $filters);
        $activePool = $filteredPool->isNotEmpty() ? $filteredPool : $products;

        if ($isLowestPriceRequest || $isHighestPriceRequest) {
            if ($this->hasSpecificModel($productMatches, $terms, $lowerQuery)) {
                $matchedSeries = $this->seriesKey($productMatches->first()['product']);
                $seriesPool = $activePool->filter(fn (Product $product) => $this->seriesKey($product) === $matchedSeries)->values();
                if ($seriesPool->isNotEmpty()) {
                    $activePool = $seriesPool;
                    $filteredPool = $seriesPool;
                }
            }

            $productMatches = $activePool->sortBy(fn (Product $product) => $isHighestPriceRequest ? -(float) $product->price : (float) $product->price)
                ->map(fn (Product $product) => ['product' => $product, 'score' => 10])
                ->values();
        } elseif ($filters['sku'] !== null) {
            $skuMatch = $products->firstWhere('sku', $filters['sku']);
            if ($skuMatch) {
                $productMatches = collect([['product' => $skuMatch, 'score' => 100]]);
            }
        } elseif ($productMatches->isEmpty()) {
            $productMatches = $activePool->sortBy('price')
                ->take(5)
                ->map(fn (Product $product) => ['product' => $product, 'score' => 0])
                ->values();
        }

        $comparisonProducts = $isComparisonRequest
            ? $this->extractComparisonProducts($products, $query, $terms, $history)
            : collect();

        $recommendations = $isRecommendationRequest
            ? $this->recommendations($products, $productMatches, $normalizedQuery, $filters)
            : [
                'items' => [],
                'budget' => null,
                'budget_min' => null,
                'over_budget' => false,
                'category' => null,
                'filters' => $filters,
                'exact_filter_empty' => false,
            ];

        $contextProducts = $this->buildContextProducts($productMatches, $comparisonProducts);
        $faqContext = $faqMatches->take(5)->map(fn (array $match) => "Q: {$match['faq']->question}\nA: {$match['faq']->answer}");
        $productContext = $contextProducts->map(function (Product $product) {
            return "{$product->name} | SKU {$product->sku} | merek {$product->brand} | kategori {$product->category} | prosesor {$product->processor} | RAM {$product->ram} | SSD {$product->storage} | grafis {$product->graphics} | layar {$product->screen} | harga Rp".number_format((float) $product->price, 0, ',', '.')." | stok {$product->stock} | {$product->description}";
        });
        $correctionContext = empty($corrections)
            ? ''
            : "\nIstilah yang dinormalisasi: ".collect($corrections)->map(fn (string $value, string $key) => "{$key} menjadi {$value}")->implode(', ').'.';

        $bestFaq = $faqMatches->first();
        $bestProduct = $productMatches->first();
        $hasSpecificModel = $this->hasSpecificModel($productMatches, $terms, $lowerQuery) || $filters['sku'] !== null;
        $asksProductFact = Str::contains($lowerQuery, [
            'harga', 'price', 'stok', 'stock', 'sku', 'prosesor', 'processor', 'ram', 'ssd',
            'gpu', 'grafis', 'spesifikasi', 'spek', 'layar', 'detail', 'berapa',
        ]);
        $isUnrecordedSpecFaq = $bestFaq && Str::contains(Str::lower($bestFaq['faq']->question), [
            'baterai', 'upgrade', 'diganti', 'ditambah', 'windows', 'berat', 'touchscreen',
        ]);

        $faqAnswer = null;
        if ($bestFaq && $bestFaq['confidence'] >= 0.68 && ! $isComparisonRequest && ! $isLowestPriceRequest && ! $isHighestPriceRequest) {
            if ($isUnrecordedSpecFaq || ! ($hasSpecificModel && $asksProductFact)) {
                $faqAnswer = $bestFaq['faq']->answer;
            }
        }

        $primaryProductPrice = $hasSpecificModel && $asksProductFact && ! $isComparisonRequest && $bestProduct
            ? (float) $bestProduct['product']->price
            : null;

        return [
            'context' => "RAG FAQ TERKAIT:\n".($faqContext->isEmpty() ? 'Tidak ada FAQ yang cocok.' : $faqContext->implode("\n\n"))
                ."\n\nKATALOG LAPTOP DARI DATABASE:\n".($productContext->isEmpty() ? 'Belum ada laptop di katalog.' : $productContext->implode("\n"))
                ."\n\nPERTANYAAN TERNORMALISASI: ".($normalizedQuery ?: 'pertanyaan umum').$correctionContext,
            'faq_answer' => $faqAnswer,
            'best_faq' => $bestFaq,
            'corrected_terms' => $corrections,
            'primary_product_price' => $primaryProductPrice,
            'primary_product' => $bestProduct['product'] ?? null,
            'has_specific_model' => $hasSpecificModel,
            'asks_product_fact' => $asksProductFact,
            'comparison_request' => $isComparisonRequest,
            'comparison_products' => $comparisonProducts->values()->all(),
            'is_lowest_price' => $isLowestPriceRequest,
            'is_highest_price' => $isHighestPriceRequest,
            'filtered_products' => $filteredPool->values()->all(),
            'all_products' => $products,
            'filters' => $filters,
            'terms' => $terms->all(),
            'normalized_query' => $normalizedQuery,
            'recommendations' => $recommendations,
            'recommendation_budget' => $filters['budget_max'],
            'unavailable_request' => $this->detectUnavailableRequest($lowerQuery),
        ];
    }

    public function seriesKey(Product $product): string
    {
        return preg_replace('/\s+\d+\s*GB\s*\/\s*\d+\s*(?:GB|TB)\s*SSD$/i', '', $product->name) ?? $product->name;
    }

    public function extractFilters(string $query, string $normalizedQuery, Collection $terms): array
    {
        $lowerQuery = Str::lower($query);
        $budgets = $this->budgetsFromQuery($query);

        $sku = null;
        if (preg_match('/\blap[-\s]?0*(\d{1,3})\b/i', $query, $skuMatch)) {
            $sku = sprintf('LAP-%03d', (int) $skuMatch[1]);
        }

        $brand = null;
        if (Str::contains($normalizedQuery, 'macbook') || Str::contains($lowerQuery, 'apple')) {
            $brand = 'Apple';
        } else {
            foreach (self::BRANDS as $candidateBrand) {
                $pattern = '/\b'.preg_quote(Str::lower($candidateBrand), '/').'\b/u';
                if (preg_match($pattern, $normalizedQuery) || preg_match($pattern, $lowerQuery)) {
                    if ($candidateBrand === 'HP' && preg_match('/\b(?:di|ke|dari|pakai)\s+hp\b/u', $lowerQuery) && ! Str::contains($lowerQuery, ['victus', 'laptop hp', 'merek hp', 'brand hp'])) {
                        continue;
                    }
                    $brand = $candidateBrand;
                    break;
                }
            }
        }

        $categories = match (true) {
            Str::contains($normalizedQuery, ['gaming', 'game', 'rtx']) || Str::contains($lowerQuery, ['144 hz', '144hz', '165 hz', '165hz']) => ['Gaming', 'Kreator & Gaming'],
            Str::contains($normalizedQuery, ['desain', 'editing', 'kreator', 'render', 'video', 'blender', 'premiere', 'photoshop', 'autocad', 'illustrator']) => ['Kreator & Gaming', 'Gaming'],
            Str::contains($normalizedQuery, ['bisnis']) => ['Bisnis'],
            Str::contains($normalizedQuery, ['kuliah', 'mahasiswa', 'kampus', 'kerja', 'kantor', 'office', 'skripsi', 'zoom', 'excel']) => ['Kerja & Kuliah', 'Bisnis'],
            default => [],
        };

        $ram = null;
        $ramMin = null;
        if (preg_match('/\bram\s*(?:minimal|min|>=|paling\s+kecil)\s*(8|16|24|32)\s*(?:gb)?\b/i', $lowerQuery, $ramMinMatch)) {
            $ramMin = (int) $ramMinMatch[1];
        } elseif (preg_match('/\b(?:ram\s*)?(8|16|24|32)\s*gb(?:\s*ram)?\b/i', $lowerQuery, $ramMatch)) {
            $ram = $ramMatch[1].' GB';
        }

        $storage = null;
        if (preg_match('/\b(256|512)\s*gb(?:\s*ssd)?\b/i', $lowerQuery, $ssdGbMatch)) {
            $storage = $ssdGbMatch[1].' GB SSD';
        } elseif (preg_match('/\b(1|2)\s*tb(?:\s*ssd)?\b/i', $lowerQuery, $ssdTbMatch)) {
            $storage = $ssdTbMatch[1].' TB SSD';
        }

        $gpu = null;
        foreach (['rtx 4060', 'rtx 4050', 'rtx', 'iris xe', 'radeon', 'nvidia', 'diskrit'] as $gpuCandidate) {
            if (Str::contains($lowerQuery, $gpuCandidate)) {
                $gpu = $gpuCandidate;
                break;
            }
        }

        $cpu = null;
        foreach (['ryzen 7', 'ryzen 5', 'ryzen', 'core i7', 'core i5', 'i7', 'i5', 'm3', 'm2', 'intel', 'amd'] as $cpuCandidate) {
            if (preg_match('/\b'.preg_quote($cpuCandidate, '/').'\b/i', $lowerQuery)) {
                $cpu = $cpuCandidate;
                break;
            }
        }

        $screen = null;
        foreach (['13.6', '15.6', '144 hz', '144hz', '165 hz', '165hz', 'liquid retina', 'qhd', '2.2k', '16 inci', '14 inci'] as $screenCandidate) {
            if (Str::contains($lowerQuery, str_replace(' ', '', $screenCandidate)) || Str::contains($lowerQuery, $screenCandidate)) {
                $screen = $screenCandidate;
                break;
            }
        }

        return [
            'sku' => $sku,
            'brand' => $brand,
            'categories' => $categories,
            'ram' => $ram,
            'ram_min' => $ramMin,
            'storage' => $storage,
            'gpu' => $gpu,
            'cpu' => $cpu,
            'screen' => $screen,
            'budget_min' => $budgets['min'],
            'budget_max' => $budgets['max'],
        ];
    }

    public function applyStructuredFilters(Collection $products, array $filters, bool $includeBudget = true): Collection
    {
        $filtered = $products;

        if (! empty($filters['sku'])) {
            return $filtered->where('sku', $filters['sku'])->values();
        }

        if (! empty($filters['brand'])) {
            $filtered = $filtered->where('brand', $filters['brand']);
        }

        if (! empty($filters['categories'])) {
            $filtered = $filtered->whereIn('category', $filters['categories']);
        }

        if (! empty($filters['ram'])) {
            $filtered = $filtered->where('ram', $filters['ram']);
        } elseif (! empty($filters['ram_min'])) {
            $filtered = $filtered->filter(fn (Product $product) => $this->ramNumeric($product) >= $filters['ram_min']);
        }

        if (! empty($filters['storage'])) {
            $filtered = $filtered->where('storage', $filters['storage']);
        }

        if (! empty($filters['gpu'])) {
            $gpuNeedle = $filters['gpu'] === 'diskrit' ? ['rtx', 'nvidia'] : [$filters['gpu']];
            $filtered = $filtered->filter(fn (Product $product) => Str::contains(Str::lower($product->graphics), $gpuNeedle));
        }

        if (! empty($filters['cpu'])) {
            $filtered = $filtered->filter(fn (Product $product) => Str::contains(Str::lower($product->processor), $filters['cpu']));
        }

        if (! empty($filters['screen'])) {
            $needle = str_replace('hz', ' hz', str_replace('  ', ' ', $filters['screen']));
            $filtered = $filtered->filter(fn (Product $product) => Str::contains(Str::lower($product->screen), [$needle, $filters['screen']]));
        }

        if ($includeBudget && $filters['budget_min'] !== null) {
            $filtered = $filtered->filter(fn (Product $product) => (float) $product->price >= $filters['budget_min']);
        }

        if ($includeBudget && $filters['budget_max'] !== null) {
            $filtered = $filtered->filter(fn (Product $product) => (float) $product->price <= $filters['budget_max']);
        }

        return $filtered->values();
    }

    public function tokens(string $text): array
    {
        $text = Str::lower(preg_replace('/\bmac\s+book\b/iu', 'macbook', $text) ?? $text);
        $text = preg_replace('/\b(\d+)\s*(gb|tb|hz|ghz|mhz)\b/iu', '$1 $2', $text) ?? $text;
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];

        return collect($tokens)
            ->map(fn (string $token) => self::ALIASES[$token] ?? $token)
            ->filter(fn (string $token) => (mb_strlen($token) >= 2 || ctype_digit($token)) && ! in_array($token, self::STOP_WORDS, true))
            ->values()
            ->all();
    }

    private function matchFaqs(Collection $faqs, Collection $terms, string $lowerQuery): Collection
    {
        return $faqs->map(function (Faq $faq) use ($terms, $lowerQuery) {
            $questionTokens = collect($this->tokens($faq->question))->unique()->values();
            $keywordTokens = collect($this->tokens($faq->keywords ?? ''))->unique()->values();
            $answerTokens = collect($this->tokens($faq->answer))->unique()->values();

            $qHits = $terms->intersect($questionTokens)->count();
            $kHits = $terms->intersect($keywordTokens)->count();
            $aHits = $terms->intersect($answerTokens)->count();

            $score = $qHits * 4 + $kHits * 3 + $aHits;

            $queryPrecision = $terms->isEmpty() ? 0 : min(1, $score / ($terms->count() * 4));
            $faqRecall = $questionTokens->isEmpty()
                ? 0
                : min(1, ($qHits * 4 + $kHits * 2) / ($questionTokens->count() * 4));

            $keywordHitBonus = 0;
            foreach (explode(',', Str::lower($faq->keywords ?? '')) as $rawKeyword) {
                $keyword = trim($rawKeyword);
                if ($keyword !== '' && mb_strlen($keyword) >= 3 && Str::contains($lowerQuery, $keyword)) {
                    $keywordHitBonus += 0.12;
                }
            }

            $confidence = $score === 0
                ? 0
                : min(1, max($queryPrecision, ($faqRecall * 0.85) + min(0.25, $keywordHitBonus)));

            return [
                'faq' => $faq,
                'score' => $score,
                'confidence' => $confidence,
            ];
        })->filter(fn (array $match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    private function matchProducts(Collection $products, Collection $terms): Collection
    {
        return $products->map(function (Product $product) use ($terms) {
            $nameTokens = $this->tokens($product->name.' '.$product->brand.' '.$product->sku);
            $detailTokens = $this->tokens(implode(' ', [
                $product->category, $product->processor, $product->ram, $product->storage,
                $product->graphics, $product->screen, $product->description,
            ]));
            $score = $terms->intersect($nameTokens)->count() * 5
                + $terms->intersect($detailTokens)->count() * 2;

            return ['product' => $product, 'score' => $score];
        })->filter(fn (array $match) => $match['score'] > 0)
            ->sort(function (array $first, array $second) {
                return ($second['score'] <=> $first['score'])
                    ?: ((float) $first['product']->price <=> (float) $second['product']->price);
            })
            ->values();
    }

    private function hasSpecificModel(Collection $productMatches, Collection $terms, string $lowerQuery = ''): bool
    {
        if ($lowerQuery !== '' && $this->detectUnavailableRequest($lowerQuery) !== null) {
            foreach (self::UNAVAILABLE_KEYWORDS['seri/model'] as $unavailableModel) {
                if (preg_match('/\b'.preg_quote($unavailableModel, '/').'\b/iu', $lowerQuery)) {
                    return false;
                }
            }
        }

        $bestProduct = $productMatches->first();
        if (! $bestProduct || $bestProduct['score'] === 0) {
            return false;
        }

        $modelTerms = collect($this->tokens($bestProduct['product']->name.' '.$bestProduct['product']->brand))
            ->intersect($terms)
            ->reject(fn (string $term) => preg_match('/^\d+$/', $term) || in_array($term, ['gb', 'tb', 'ram', 'ssd'], true));

        if ($modelTerms->count() >= 2) {
            return true;
        }

        return $modelTerms->contains(fn (string $term) => in_array($term, [
            'vivobook', 'tuf', 'ideapad', 'aspire', 'victus', 'inspiron', 'pongo', 'macbook',
        ], true));
    }

    private function isComparisonQuery(string $lowerQuery): bool
    {
        return Str::contains($lowerQuery, ['beda', 'perbedaan', 'banding', 'versus', ' vs ', 'selisih'])
            || preg_match('/\bmending\b.*\b(?:atau|sama|vs)\b/iu', $lowerQuery) === 1;
    }

    private function extractComparisonProducts(Collection $products, string $query, Collection $terms, array $history): Collection
    {
        $seriesList = $products->groupBy(fn (Product $product) => $this->seriesKey($product));
        $matchedBySeries = [];
        $lowerQuery = Str::lower($query);

        $specificVariants = $products->filter(function (Product $product) use ($lowerQuery) {
            $ram = Str::lower($product->ram);
            $storage = Str::lower(str_replace(' SSD', '', $product->storage));
            return Str::contains($lowerQuery, [$ram, str_replace(' ', '', $ram)])
                && Str::contains($lowerQuery, [$storage, str_replace(' ', '', $storage)]);
        });

        foreach ($seriesList as $seriesName => $variants) {
            $seriesTokens = collect($this->tokens($seriesName))
                ->reject(fn (string $token) => in_array($token, ['13', '14', '15', '16', '6', 'inci', 'inch'], true))
                ->unique()
                ->values();

            $overlap = $terms->intersect($seriesTokens);
            $distinctiveHits = $overlap->reject(fn (string $token) => in_array(Str::lower($token), ['apple', 'asus', 'lenovo', 'acer', 'hp', 'dell', 'msi', 'axioo', 'air', 'gaming', 'thin'], true));

            if ($overlap->count() >= 2 || $distinctiveHits->isNotEmpty()) {
                $chosen = $variants->sortBy('price')->first();
                $matchedBySeries[$seriesName] = [
                    'product' => $chosen,
                    'score' => $overlap->count() * 10 + $distinctiveHits->count() * 15,
                ];
            }
        }

        if (count($matchedBySeries) === 1 && preg_match('/\b(8|16|24|32)\s*gb\b.*\b(8|16|24|32)\s*gb\b/i', $lowerQuery, $ramCompare)) {
            $seriesName = array_key_first($matchedBySeries);
            $variants = $seriesList->get($seriesName);
            $first = $variants->firstWhere('ram', $ramCompare[1].' GB');
            $second = $variants->firstWhere('ram', $ramCompare[2].' GB');
            if ($first && $second && $first->id !== $second->id) {
                return collect([$first, $second]);
            }
        }

        if (count($matchedBySeries) === 1 && $history !== []) {
            $previousSeries = $this->lastDiscussedProductFromHistory($products, $history);
            if ($previousSeries && $this->seriesKey($previousSeries) !== array_key_first($matchedBySeries)) {
                $matchedBySeries[$this->seriesKey($previousSeries)] = [
                    'product' => $previousSeries,
                    'score' => 5,
                ];
            }
        }

        $result = collect($matchedBySeries)
            ->sortByDesc('score')
            ->pluck('product')
            ->take(3)
            ->values();

        if ($result->count() >= 2 && $specificVariants->isNotEmpty()) {
            return $result->map(function (Product $product) use ($specificVariants) {
                $series = $this->seriesKey($product);
                return $specificVariants->first(fn (Product $v) => $this->seriesKey($v) === $series) ?? $product;
            });
        }

        return $result;
    }

    public function lastDiscussedProductFromHistory(Collection $products, array $history): ?Product
    {
        foreach (array_reverse($history) as $item) {
            $content = $item['content'] ?? '';
            if (preg_match('/\[(LAP-\d{3})\]/', $content, $skuMatch)) {
                $found = $products->firstWhere('sku', $skuMatch[1]);
                if ($found) {
                    return $found;
                }
            }

            foreach ($products->sortByDesc(fn (Product $p) => strlen($p->name)) as $product) {
                if (Str::contains($content, $product->name)) {
                    return $product;
                }
            }

            foreach ($products->groupBy(fn (Product $p) => $this->seriesKey($p)) as $seriesName => $variants) {
                if (Str::contains(Str::lower($content), Str::lower($seriesName))) {
                    return $variants->sortBy('price')->first();
                }
            }
        }

        return null;
    }

    private function buildContextProducts(Collection $productMatches, Collection $comparisonProducts): Collection
    {
        if ($comparisonProducts->isNotEmpty()) {
            return $comparisonProducts;
        }

        $selected = collect();
        $seenSeries = [];

        foreach ($productMatches as $match) {
            $product = $match['product'];
            $series = $this->seriesKey($product);

            if (! isset($seenSeries[$series])) {
                $selected->push($product);
                $seenSeries[$series] = true;
            }

            if ($selected->count() >= 5) {
                return $selected;
            }
        }

        foreach ($productMatches as $match) {
            $product = $match['product'];
            if (! $selected->contains('id', $product->id)) {
                $selected->push($product);
            }
            if ($selected->count() >= 5) {
                break;
            }
        }

        return $selected;
    }

    private function recommendations(Collection $products, Collection $productMatches, string $normalizedQuery, array $filters): array
    {
        $candidates = $this->applyStructuredFilters($products, $filters, includeBudget: false);
        $exactFilterEmpty = false;

        if ($candidates->isEmpty()) {
            $exactFilterEmpty = true;
            $relaxedFilters = $filters;
            $relaxedFilters['ram'] = null;
            $relaxedFilters['storage'] = null;
            $relaxedFilters['screen'] = null;
            $candidates = $this->applyStructuredFilters($products, $relaxedFilters, includeBudget: false);

            if ($candidates->isEmpty()) {
                $candidates = $products;
            }
        }

        $budgetMax = $filters['budget_max'];
        $budgetMin = $filters['budget_min'];

        $withinBudget = $candidates->filter(function (Product $product) use ($budgetMax, $budgetMin) {
            $price = (float) $product->price;
            $okMin = $budgetMin === null || $price >= $budgetMin;
            $okMax = $budgetMax === null || $price <= $budgetMax;

            return $okMin && $okMax;
        });

        $hasBudget = $budgetMax !== null || $budgetMin !== null;
        $overBudget = $hasBudget && $withinBudget->isEmpty();

        if ($withinBudget->isNotEmpty() && $hasBudget) {
            $sorted = $budgetMax !== null
                ? $withinBudget->sortByDesc(fn (Product $product) => (float) $product->price)->values()
                : $withinBudget->sortBy(fn (Product $product) => (float) $product->price)->values();
        } elseif ($hasBudget) {
            $sorted = $candidates->sortBy(fn (Product $product) => (float) $product->price)->values();
        } else {
            $scores = $productMatches->keyBy(fn (array $match) => $match['product']->id);
            $sorted = $candidates->sort(function (Product $first, Product $second) use ($scores) {
                return (($scores->get($second->id)['score'] ?? 0) <=> ($scores->get($first->id)['score'] ?? 0))
                    ?: ((float) $first->price <=> (float) $second->price);
            })->values();
        }

        $items = $this->pickDiverseRecommendations($sorted, 3);

        return [
            'items' => $items,
            'budget' => $budgetMax,
            'budget_min' => $budgetMin,
            'over_budget' => $overBudget,
            'category' => $filters['categories'][0] ?? null,
            'filters' => $filters,
            'exact_filter_empty' => $exactFilterEmpty,
        ];
    }

    private function pickDiverseRecommendations(Collection $sorted, int $limit): array
    {
        $picked = collect();
        $seenSeries = [];

        foreach ($sorted as $product) {
            $series = $this->seriesKey($product);
            if (! isset($seenSeries[$series])) {
                $picked->push($product);
                $seenSeries[$series] = true;
            }
            if ($picked->count() >= $limit) {
                return $picked->all();
            }
        }

        foreach ($sorted as $product) {
            if (! $picked->contains('id', $product->id)) {
                $picked->push($product);
            }
            if ($picked->count() >= $limit) {
                break;
            }
        }

        return $picked->all();
    }

    private function budgetsFromQuery(string $query): array
    {
        if (preg_match('/(?:antara|dari)?\s*(?:rp\s*)?(\d+(?:[.,]\d+)?)\s*(?:-|s\/d|sd|sampai|hingga|ke)\s*(?:rp\s*)?(\d+(?:[.,]\d+)?)\s*(jutaan|juta-an|juta|jt-an|jt|j|ribuan|ribu|rb)\b/iu', $query, $rangeMatch)) {
            $multiplier = in_array(Str::lower($rangeMatch[3]), ['ribu', 'ribuan', 'rb'], true) ? 1000 : 1000000;
            $first = (int) round((float) str_replace(',', '.', $rangeMatch[1]) * $multiplier);
            $second = (int) round((float) str_replace(',', '.', $rangeMatch[2]) * $multiplier);

            return [
                'min' => min($first, $second),
                'max' => max($first, $second),
            ];
        }

        if (preg_match('/(?:minimal|min|di\s*atas|lebih\s*dari|mulai(?:\s*dari)?)\s*(?:rp\s*)?(\d+(?:[.,]\d+)?)\s*(jutaan|juta-an|juta|jt-an|jt|j|ribuan|ribu|rb)\b/iu', $query, $minMatch)) {
            $multiplier = in_array(Str::lower($minMatch[2]), ['ribu', 'ribuan', 'rb'], true) ? 1000 : 1000000;

            return [
                'min' => (int) round((float) str_replace(',', '.', $minMatch[1]) * $multiplier),
                'max' => null,
            ];
        }

        if (preg_match('/(?:rp\s*)?(\d+(?:[.,]\d+)?)\s*(jutaan|juta-an|juta|jt-an|jt|j|ribuan|ribu|rb)\b/iu', $query, $match)) {
            $amount = (float) str_replace(',', '.', $match[1]);
            $multiplier = in_array(Str::lower($match[2]), ['ribu', 'ribuan', 'rb'], true) ? 1000 : 1000000;

            return [
                'min' => null,
                'max' => (int) round($amount * $multiplier),
            ];
        }

        if (preg_match('/(?:rp\s*)?(\d{1,3}(?:\.\d{3}){2,}|\d{7,9})\b/iu', $query, $fullRpMatch)) {
            $value = (int) str_replace('.', '', $fullRpMatch[1]);
            if ($value >= 1000000) {
                return [
                    'min' => null,
                    'max' => $value,
                ];
            }
        }

        return ['min' => null, 'max' => null];
    }

    private function detectUnavailableRequest(string $lowerQuery): ?array
    {
        foreach (self::UNAVAILABLE_KEYWORDS as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/iu', $lowerQuery)) {
                    return ['type' => $type, 'term' => $keyword];
                }
            }
        }

        return null;
    }

    private function ramNumeric(Product $product): int
    {
        return preg_match('/(\d+)/', $product->ram, $m) ? (int) $m[1] : 0;
    }

    private function buildVocabulary(Collection $faqs, Collection $products): array
    {
        $vocabulary = [];

        foreach ($faqs as $faq) {
            foreach ($this->tokens($faq->question.' '.$faq->keywords.' '.$faq->answer) as $token) {
                $vocabulary[$token] = ($vocabulary[$token] ?? 0) + 1;
            }
        }

        foreach ($products as $product) {
            $searchable = implode(' ', [
                $product->name, $product->brand, $product->sku, $product->category,
                $product->processor, $product->ram, $product->storage, $product->graphics,
                $product->screen, $product->description,
            ]);

            foreach ($this->tokens($searchable) as $token) {
                $vocabulary[$token] = ($vocabulary[$token] ?? 0) + 1;
            }
        }

        return $vocabulary;
    }

    private function correctToken(string $token, array $vocabulary): string
    {
        $token = self::ALIASES[$token] ?? $token;

        if (isset($vocabulary[$token]) || mb_strlen($token) < 4) {
            return $token;
        }

        $maxDistance = mb_strlen($token) >= 8 ? 2 : 1;
        $bestMatch = null;
        $bestDistance = $maxDistance + 1;
        $bestFrequency = 0;

        foreach ($vocabulary as $candidate => $frequency) {
            if (abs(strlen($token) - strlen($candidate)) > $maxDistance) {
                continue;
            }

            $distance = levenshtein($token, $candidate);
            if ($distance < $bestDistance || ($distance === $bestDistance && $frequency > $bestFrequency)) {
                $bestMatch = $candidate;
                $bestDistance = $distance;
                $bestFrequency = $frequency;
            }
        }

        return $bestMatch !== null && $bestDistance <= $maxDistance ? $bestMatch : $token;
    }
}
