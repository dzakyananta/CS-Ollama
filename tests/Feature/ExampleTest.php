<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(\Database\Seeders\SupportDemoSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Laptop yang pas')
            ->assertSee('Lihat detail')
            ->assertSee('data-chat-open', false)
            ->assertDontSee('data-add-cart', false)
            ->assertDontSee('Keranjang');

        $this->assertSame(1, substr_count($response->getContent(), 'data-chat-open'));
    }
}
