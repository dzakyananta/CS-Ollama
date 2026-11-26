<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Services\CustomerServiceResponder;
use App\Services\LaptopRagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function status(): JsonResponse
    {
        try {
            $response = Http::timeout(2)->get(rtrim(config('services.ollama.url'), '/').'/api/tags');
            $models = collect($response->json('models', []))->pluck('name');
            $model = config('services.ollama.model');

            return response()->json([
                'available' => $response->successful() && $models->contains(fn (string $name) => $name === $model || str_starts_with($name, $model.':')),
                'model' => $model,
            ]);
        } catch (\Throwable) {
            return response()->json(['available' => false, 'model' => config('services.ollama.model')]);
        }
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        return response()->json([
            'conversation_id' => $conversation->id,
            'messages' => $conversation->messages()->oldest()->get(['id', 'sender', 'body', 'source', 'created_at']),
        ]);
    }

    public function store(Request $request, LaptopRagService $rag, CustomerServiceResponder $responder): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer', Rule::exists('conversations', 'id')],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:180'],
        ]);

        if (! empty($data['conversation_id'])) {
            $conversation = Conversation::findOrFail($data['conversation_id']);
        } else {
            $email = $data['customer_email'] ?? 'visitor+'.Str::uuid().'@example.test';
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['name' => $data['customer_name'] ?? 'Pelanggan Web', 'segment' => 'Pelanggan web'],
            );
            $conversation = $customer->conversations()->create([
                'subject' => Str::limit($data['message'], 72),
                'status' => 'open',
                'channel' => 'web',
            ]);
        }

        $lastAssistantMessage = $conversation->messages()
            ->where('sender', 'assistant')
            ->latest('id')
            ->first();

        $priorHistory = $conversation->messages()
            ->latest('id')
            ->limit(8)
            ->get()
            ->reverse()
            ->map(fn (Message $message) => [
                'role' => $message->sender === 'customer' ? 'user' : 'assistant',
                'content' => $message->body,
            ])
            ->values()
            ->all();

        $followUpReply = $responder->tryRecommendationFollowUp($data['message'], $lastAssistantMessage, $rag);
        $conversation->messages()->create(['sender' => 'customer', 'body' => $data['message']]);

        if ($followUpReply !== null) {
            $reply = $followUpReply;
        } else {
            $retrieval = $rag->retrieve($data['message'], $priorHistory);
            $directReply = $responder->tryDirectReply($data['message'], $retrieval, $priorHistory, $rag);

            if ($directReply !== null) {
                $reply = $directReply;
            } else {
                $history = array_merge($priorHistory, [[
                    'role' => 'user',
                    'content' => $data['message'],
                ]]);

                $reply = $this->askOllama(
                    $history,
                    $data['message'],
                    $retrieval,
                    $responder,
                );
            }
        }

        $assistantMessage = $conversation->messages()->create([
            'sender' => 'assistant',
            'body' => $reply['message'],
            'source' => $reply['source'],
        ]);
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'message' => [
                'id' => $assistantMessage->id,
                'sender' => $assistantMessage->sender,
                'body' => $assistantMessage->body,
                'created_at' => $assistantMessage->created_at,
            ],
            'source' => $reply['source'],
        ]);
    }

    private function askOllama(array $history, string $query, array $retrieval, CustomerServiceResponder $responder): array
    {
        $context = $retrieval['context'];
        $primaryProductPrice = $retrieval['primary_product_price'];
        $comparisonRequest = $retrieval['comparison_request'];
        $primaryProduct = $retrieval['has_specific_model'] ? ($retrieval['primary_product'] ?? null) : null;

        try {
            $response = Http::timeout(config('services.ollama.timeout'))
                ->post(rtrim(config('services.ollama.url'), '/').'/api/chat', [
                    'model' => config('services.ollama.model'),
                    'stream' => false,
                    'options' => ['temperature' => 0.1, 'num_predict' => 140],
                    'messages' => array_merge([
                        [
                            'role' => 'system',
                            'content' => "Kamu CS toko laptop Microdata yang ramah dan informatif. Balas seperti percakapan sehari-hari dalam bahasa Indonesia: jawab pertanyaan langsung tanpa pembuka kaku seperti \"Menurut katalog\". Maksimal 3 kalimat dan 45 kata, tanpa markdown atau daftar. Gunakan hanya fakta pada katalog/FAQ berikut; jangan mengarang performa, kompatibilitas, stok, harga, atau spesifikasi. Jika data belum tersedia, sampaikan jujur lalu tawarkan untuk mengecek model tertentu. Harga dalam rupiah.\n\n".$context,
                        ],
                    ], $history),
                ]);

            if ($response->successful() && is_string($response->json('message.content'))) {
                $message = trim($response->json('message.content'));

                if ($this->isGroundedReply($message, $context, $primaryProductPrice, $comparisonRequest)) {
                    return ['message' => $message, 'source' => 'ollama'];
                }

                if ($catalogReply = $responder->catalogReplyFromContext($context, $query, $primaryProduct)) {
                    return ['message' => $catalogReply, 'source' => 'catalog'];
                }
            }
        } catch (\Throwable) {
        }

        if ($primaryProduct && ($catalogReply = $responder->catalogReplyFromContext($context, $query, $primaryProduct))) {
            return ['message' => $catalogReply, 'source' => 'catalog'];
        }

        return [
            'message' => 'Maaf, asisten AI sedang tidak tersambung saat ini. Kamu tetap bisa bertanya soal harga, stok, spesifikasi model tertentu, perbandingan laptop, atau minta rekomendasi sesuai budgetmu, atau hubungi bantuan@microdata.test.',
            'source' => 'offline',
        ];
    }

    private function isGroundedReply(string $message, string $context, ?float $primaryProductPrice, bool $comparisonRequest): bool
    {
        if ($message === '' || str_word_count($message) > 45) {
            return false;
        }

        preg_match_all('/\b\d+(?:[.,]\d+)?\s*(?:GB|TB|Hz|GHz|MHz|inci)\b/iu', $message, $claims);
        $normalizedContext = str_replace(',', '.', Str::lower($context));

        foreach ($claims[0] as $claim) {
            $normalizedClaim = str_replace(',', '.', Str::lower(preg_replace('/\s+/', ' ', $claim)));
            if (! Str::contains($normalizedContext, $normalizedClaim)) {
                return false;
            }
        }

        preg_match_all('/\bRp\s*([\d.,]+)/iu', $message, $priceClaims);
        preg_match_all('/\bharga\s+Rp\s*([\d.,]+)/iu', $context, $contextPrices);
        $knownPrices = collect($contextPrices[1] ?? [])->map(fn (string $price) => preg_replace('/\D/', '', $price));

        foreach ($priceClaims[1] ?? [] as $priceClaim) {
            $normalizedPrice = preg_replace('/\D/', '', $priceClaim);
            $expectedPrice = $primaryProductPrice === null
                ? null
                : preg_replace('/\D/', '', number_format($primaryProductPrice, 0, ',', '.'));

            if ($expectedPrice !== null && ! $comparisonRequest && $normalizedPrice !== $expectedPrice) {
                return false;
            }

            if (! $knownPrices->contains($normalizedPrice)) {
                return false;
            }
        }

        foreach (['cocok untuk', 'ideal untuk', 'sangat cocok', 'performa yang baik', 'performa tinggi', 'memungkinkan Anda', 'dapat menjalankan', 'bisa menjalankan'] as $claim) {
            if (Str::contains(Str::lower($message), Str::lower($claim))) {
                return false;
            }
        }

        return true;
    }
}
