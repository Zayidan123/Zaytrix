<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeChunk;
use App\Services\AiRouterService;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AiChatTest extends TestCase
{
    public function test_returns_mocked_ai_response_for_chat(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => 'Test AI response about Bitcoin']]],
            ], 200),
        ]);

        $service = app(AiRouterService::class);
        $response = $service->chat('Harga Bitcoin hari ini?');

        $this->assertStringContainsString('Test AI response', $response['content']);
    }

    public function test_indexes_document_and_searches_via_bm25(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kb = app(KnowledgeBaseService::class);

        $doc = KnowledgeDocument::create([
            'user_id' => $user->id,
            'title' => 'Bitcoin Whitepaper',
            'source' => 'test',
            'content' => 'Bitcoin is a peer-to-peer electronic cash system.',
            'file_path' => 'test.md',
            'mime_type' => 'text/markdown',
        ]);

        $kb->indexDocument($doc);

        $results = $kb->search('bitcoin cash', 5);
        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results[0]['document_id']);
    }

    public function test_extracts_text_from_document_and_chunks_correctly(): void
    {
        $kb = app(KnowledgeBaseService::class);

        // Long text that will create multiple chunks with default 500 words
        $text = "Bitcoin is a peer-to-peer electronic cash system. " . str_repeat("It allows online payments to be sent directly from one party to another without going through a financial institution. ", 200);
        $chunks = $kb->chunkText($text);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertStringContainsString('Bitcoin', $chunks[0]);
    }
}
