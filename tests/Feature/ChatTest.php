<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_uses_ollama_and_persists_the_conversation(): void
    {
        Product::create([
            'sku' => 'TEST-ASUS-01',
            'name' => 'ASUS Vivobook Go 14 16 GB / 512 GB SSD',
            'brand' => 'ASUS',
            'category' => 'Kerja & Kuliah',
            'processor' => 'AMD Ryzen 5 7520U',
            'ram' => '16 GB',
            'storage' => '512 GB SSD',
            'graphics' => 'AMD Radeon 610M',
            'screen' => '14 inci Full HD IPS',
            'price' => 8249000,
            'stock' => 8,
            'description' => 'Laptop uji untuk kuliah dan kerja.',
            'is_active' => true,
        ]);

        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response(['message' => ['content' => 'Laptop ASUS tersedia di katalog kami.']], 200),
        ]);

        $response = $this->postJson('/chat', [
            'message' => 'Berapa harga ASUS Vivobook Go 14 dan prosesornya?',
            'customer_name' => 'Dewi Penguji',
            'customer_email' => 'dewi@example.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'ollama')
            ->assertJsonPath('message.body', 'Laptop ASUS tersedia di katalog kami.');

        $this->assertSame(1, Customer::count());
        $this->assertSame(1, Conversation::count());
        $this->assertSame(2, Message::count());
        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'KATALOG LAPTOP DARI DATABASE:')
            && str_contains($request['messages'][0]['content'], 'ASUS Vivobook Go 14')
            && str_contains($request['messages'][0]['content'], 'AMD Ryzen 5 7520U')
            && str_contains($request['messages'][0]['content'], 'RAM 16 GB')
            && str_contains($request['messages'][0]['content'], 'Rp8.249.000'));
    }

    public function test_demo_seeder_creates_at_least_one_hundred_products(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);

        $this->assertDatabaseCount('products', 100);
        $this->assertDatabaseCount('customers', 50);
        $this->assertDatabaseCount('faqs', 100);
        $this->assertDatabaseHas('products', ['brand' => 'ASUS', 'category' => 'Kerja & Kuliah']);
        $this->assertDatabaseHas('products', ['brand' => 'Apple', 'name' => 'Apple MacBook Air M2 13.6-inch 8 GB / 256 GB SSD']);
        $this->assertSame(8, \App\Models\Product::query()->distinct()->count('brand'));
        $this->assertSame(100, \App\Models\Product::whereIn('category', ['Kerja & Kuliah', 'Gaming', 'Bisnis', 'Kreator & Gaming'])->count());
        $this->assertDatabaseHas('faqs', ['question' => 'Berapa lama baterai laptop bisa bertahan?']);
    }

    public function test_unverified_ollama_reply_falls_back_to_catalog_facts(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['content' => 'Laptop ini cocok untuk editing video dan sangat ideal untuk belajar. Dengan layar 14 inci, laptop ini mempunyai performa yang baik dan RAM 16 GB. Penyimpanan SSD ini dapat menjalankan aplikasi dengan nyaman. Saya merekomendasikannya untuk semua kebutuhan kerja dan hiburan di rumah.'],
            ], 200),
        ]);

        $response = $this->postJson('/chat', [
            'message' => 'Berapa harga Dell Inspiron 14 5430 dan spesifikasinya?',
            'customer_email' => 'catalog-check@example.test',
        ]);

        $response->assertOk()->assertJsonPath('source', 'catalog');
        $answer = $response->json('message.body');

        $this->assertStringContainsString('Dell Inspiron 14 5430', $answer);
        $this->assertStringContainsString('harga yang tercatat Rp13.999.000', $answer);
        $this->assertStringContainsString('stok 3', $answer);
        $this->assertStringContainsString('Intel Core i7-1360P', $answer);
        $this->assertStringNotContainsString('sangat ideal', $answer);
        $this->assertStringNotContainsString('Menurut katalog', $answer);
    }

    public function test_common_ram_question_uses_a_natural_saved_faq_answer(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);

        $response = $this->postJson('/chat', [
            'message' => 'Apakah RAM laptop bisa di-upgrade?',
            'customer_email' => 'ram-question@example.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('message.body', 'Informasi RAM yang bisa di-upgrade belum tersedia untuk semua model di katalog. Kirim nama atau SKU laptop agar CS bisa bantu cek detail modelnya.');

        Http::assertNothingSent();
    }

    public function test_faq_rag_corrects_a_common_typo(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);

        $response = $this->postJson('/chat', [
            'message' => 'Apakah RAM laptop bisa di-upgarde?',
            'customer_email' => 'ram-typo@example.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('message.body', 'Informasi RAM yang bisa di-upgrade belum tersedia untuk semua model di katalog. Kirim nama atau SKU laptop agar CS bisa bantu cek detail modelnya.');

        Http::assertNothingSent();
    }

    public function test_rag_corrects_a_typo_in_a_product_name_before_calling_ollama(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['content' => 'MacBook Air M2 13.6-inch tersedia dengan RAM 8 GB dan SSD 256 GB.'],
            ], 200),
        ]);

        $response = $this->postJson('/chat', [
            'message' => 'Berapa harga Macboook Air M2?',
            'customer_email' => 'macbook-typo@example.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'ollama')
            ->assertJsonPath('message.body', 'MacBook Air M2 13.6-inch tersedia dengan RAM 8 GB dan SSD 256 GB.');

        $requests = Http::recorded();
        $this->assertCount(1, $requests);
        $prompt = $requests->first()[0]['messages'][0]['content'];

        $this->assertStringContainsString('macboook menjadi macbook', $prompt);
        $this->assertStringContainsString('macbook air m2', $prompt);
        $this->assertStringContainsString('Apple MacBook Air M2 13.6-inch 8 GB / 256 GB SSD', $prompt);
        $this->assertStringContainsString('harga Rp13.999.000', $prompt);
    }

    public function test_rag_replaces_an_incorrect_model_price_with_the_database_price(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['content' => 'Harga MacBook Air M2 8 GB 256 GB adalah Rp15.999.000.'],
            ], 200),
        ]);

        $response = $this->postJson('/chat', [
            'message' => 'Berapa harga Macboook Air M2 8 GB 256 GB?',
            'customer_email' => 'macbook-price-check@example.test',
        ]);

        $response->assertOk()->assertJsonPath('source', 'catalog');
        $answer = $response->json('message.body');

        $this->assertStringContainsString('Apple MacBook Air M2 13.6-inch 8 GB / 256 GB SSD', $answer);
        $this->assertStringContainsString('Rp13.999.000', $answer);
        $this->assertStringNotContainsString('Rp15.999.000', $answer);
    }

    public function test_customer_can_select_a_numbered_laptop_recommendation(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake();

        $recommendationResponse = $this->postJson('/chat', [
            'message' => 'Saya ingin rekomendasi laptop untuk kuliah harga 8 juta',
            'customer_email' => 'recommendation@example.test',
        ]);

        $recommendationResponse->assertOk()->assertJsonPath('source', 'recommendations');
        $recommendationText = $recommendationResponse->json('message.body');
        $this->assertStringContainsString('1. [LAP-', $recommendationText);
        $this->assertStringContainsString('2. [LAP-', $recommendationText);
        $this->assertStringContainsString('3. [LAP-', $recommendationText);
        $this->assertStringContainsString('paling saya rekomendasikan', $recommendationText);

        preg_match('/^2\. \[(LAP-\d{3})\]/m', $recommendationText, $selectedOption);
        $this->assertNotEmpty($selectedOption[1] ?? null);
        $selectedProduct = Product::where('sku', $selectedOption[1])->firstOrFail();

        $selectionResponse = $this->postJson('/chat', [
            'message' => 'pilih 2',
            'conversation_id' => $recommendationResponse->json('conversation_id'),
        ]);

        $selectionResponse->assertOk()->assertJsonPath('source', 'catalog');
        $selectionResponse->assertJsonPath('message.body', fn (string $body) => str_contains($body, $selectedProduct->name)
            && str_contains($body, number_format((float) $selectedProduct->price, 0, ',', '.')));
        Http::assertNothingSent();
    }

    public function test_cs_handles_greetings_thanks_and_store_policy_naturally(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake();

        $greeting = $this->postJson('/chat', [
            'message' => 'Halo kak',
            'customer_email' => 'greeting@example.test',
        ]);

        $greeting->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'CS Microdata'));

        $conversationId = $greeting->json('conversation_id');

        $checkout = $this->postJson('/chat', [
            'message' => 'Bisa checkout dan bayar COD di sini?',
            'conversation_id' => $conversationId,
        ]);

        $checkout->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'katalog informasi')
                && str_contains($body, 'bantuan@microdata.test'));

        $thanks = $this->postJson('/chat', [
            'message' => 'Oke makasih kak',
            'conversation_id' => $conversationId,
        ]);

        $thanks->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'Sama-sama'));

        Http::assertNothingSent();
    }

    public function test_cs_answers_multi_turn_product_and_recommendation_follow_ups(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response([
                'message' => ['content' => 'ASUS TUF Gaming A15 FA507NV 8 GB / 256 GB SSD tersedia dengan harga Rp17.999.000.'],
            ], 200),
        ]);

        $first = $this->postJson('/chat', [
            'message' => 'Berapa harga ASUS TUF Gaming A15?',
            'customer_email' => 'followup@example.test',
        ]);
        $first->assertOk();
        $conversationId = $first->json('conversation_id');

        $variantSwitch = $this->postJson('/chat', [
            'message' => 'Kalau yang RAM 16 GB SSD 512 GB?',
            'conversation_id' => $conversationId,
        ]);

        $variantSwitch->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'ASUS TUF Gaming A15 FA507NV 16 GB / 512 GB SSD')
                && str_contains($body, 'Rp19.249.000'));

        $stockFollowUp = $this->postJson('/chat', [
            'message' => 'Stoknya masih ada berapa?',
            'conversation_id' => $conversationId,
        ]);

        $stockFollowUp->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'ASUS TUF Gaming A15 FA507NV 16 GB / 512 GB SSD')
                && str_contains($body, 'stok'));

        $recs = $this->postJson('/chat', [
            'message' => 'Rekomendasi laptop gaming budget 17 juta',
            'customer_email' => 'rec-followup@example.test',
        ]);
        $recs->assertOk()->assertJsonPath('source', 'recommendations');

        $compareTwo = $this->postJson('/chat', [
            'message' => 'Bedanya nomor 1 dan 2 apa?',
            'conversation_id' => $recs->json('conversation_id'),
        ]);

        $compareTwo->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'perbandingan')
                && str_contains($body, '1.')
                && str_contains($body, '2.'));
    }

    public function test_cs_filters_by_specs_budget_range_sku_and_compares_models(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);
        Http::fake();

        $rtxFilter = $this->postJson('/chat', [
            'message' => 'Ada laptop gaming dengan RTX 4060?',
            'customer_email' => 'filter-rtx@example.test',
        ]);

        $rtxFilter->assertOk()
            ->assertJsonPath('source', 'recommendations')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'ASUS TUF Gaming A15')
                && str_contains($body, 'Axioo Pongo 760'));

        $budgetRange = $this->postJson('/chat', [
            'message' => 'Cari laptop RAM 16 GB budget antara 7 sampai 9 juta',
            'customer_email' => 'budget-range@example.test',
        ]);

        $budgetRange->assertOk()
            ->assertJsonPath('source', 'recommendations')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, '16 GB')
                && str_contains($body, '1. [LAP-'));

        $skuLookup = $this->postJson('/chat', [
            'message' => 'Cek info lap 4 dong',
            'customer_email' => 'sku@example.test',
        ]);

        $skuLookup->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, '[LAP-004]')
                && str_contains($body, 'Apple MacBook Air M2'));

        $comparison = $this->postJson('/chat', [
            'message' => 'Bandingkan ASUS TUF Gaming A15 dan HP Victus 15',
            'customer_email' => 'compare@example.test',
        ]);

        $comparison->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'ASUS TUF Gaming A15')
                && str_contains($body, 'HP Victus 15'));

        Http::assertNothingSent();
    }

    public function test_cs_handles_unavailable_brands_lowest_price_by_series_and_offline_fallback(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);

        $unavailable = $this->postJson('/chat', [
            'message' => 'Apakah ada laptop merek Razer atau MacBook Pro?',
            'customer_email' => 'unavailable@example.test',
        ]);

        $unavailable->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'belum tersedia')
                && str_contains($body, 'Apple, ASUS, Lenovo'));

        $cheapestGaming = $this->postJson('/chat', [
            'message' => 'Laptop gaming termurah apa?',
            'customer_email' => 'cheapest-gaming@example.test',
        ]);

        $cheapestGaming->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'HP Victus 15-fa2700TX')
                && str_contains($body, 'Rp15.499.000'));

        Http::fake([
            '127.0.0.1:11434/api/chat' => Http::response('Service Unavailable', 503),
        ]);

        $offlineSpecificModel = $this->postJson('/chat', [
            'message' => 'Brp hrg Axioo Pongo 760?',
            'customer_email' => 'offline-model@example.test',
        ]);

        $offlineSpecificModel->assertOk()
            ->assertJsonPath('source', 'catalog')
            ->assertJsonPath('message.body', fn (string $body) => str_contains($body, 'Axioo Pongo 760')
                && str_contains($body, 'Rp18.499.000'));
    }
}