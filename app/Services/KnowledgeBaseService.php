<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    private const CHUNK_SIZE = 500;
    private const CHUNK_OVERLAP = 50;
    private const BM25_K1 = 1.2;
    private const BM25_B = 0.75;

    public function indexDocument(\App\Models\KnowledgeDocument $doc): int
    {
        $path = storage_path('app/public/' . $doc->file_path);
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $text = $this->extractText($path, $doc->mime_type);
        if (empty($text)) {
            throw new \Exception("No text extracted from document");
        }

        $chunks = $this->chunkText($text);
        $count = count($chunks);

        DB::transaction(function () use ($doc, $chunks, $count) {
            \App\Models\KnowledgeChunk::where('document_id', $doc->id)->delete();
            foreach ($chunks as $i => $chunk) {
                $tokens = $this->tokenize($chunk);
                \App\Models\KnowledgeChunk::create([
                    'document_id' => $doc->id,
                    'chunk_index' => $i,
                    'content' => $chunk,
                    'bm25_tokens' => $tokens,
                    'token_count' => count($tokens),
                ]);
            }
            $doc->update(['chunk_count' => $count, 'indexed_at' => now()]);
        });

        return $count;
    }

    public function search(string $query, int $limit = 5, ?int $userId = null): array
    {
        $queryTokens = $this->tokenize($query);
        if (empty($queryTokens)) return [];

        $query = \App\Models\KnowledgeChunk::query()
            ->join('knowledge_documents', 'knowledge_chunks.document_id', '=', 'knowledge_documents.id')
            ->when($userId, fn($q) => $q->where('knowledge_documents.user_id', $userId))
            ->select('knowledge_chunks.*', 'knowledge_documents.title as doc_title');

        $chunks = $query->get()->map(function ($chunk) use ($queryTokens) {
            $score = $this->bm25Score($queryTokens, $chunk->bm25_tokens ?? [], $chunk->token_count);
            $chunk->bm25_score = $score;
            return $chunk;
        })->sortByDesc('bm25_score')->take($limit)->values()->toArray();

        return $chunks;
    }

    public function buildComparisonContext(array $documents, string $question): string
    {
        $context = "Anda diminta membandingkan {$documents->count()} dokumen berikut:\n\n";
        foreach ($documents as $doc) {
            $chunks = \App\Models\KnowledgeChunk::where('document_id', $doc->id)
                ->orderBy('chunk_index')
                ->limit(20)
                ->get();
            $content = $chunks->pluck('content')->implode("\n\n");
            $context .= "=== DOKUMEN: {$doc->title} ===\n{$content}\n\n";
        }
        $context .= "PERTANYAAN: {$question}\n\nBerikan perbandingan terstruktur dengan poin-poin kunci, persamaan, perbedaan, dan kesimpulan.";
        return $context;
    }

    private function extractText(string $path, string $mime): string
    {
        if (str_contains($mime, 'pdf')) {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($path);
            return $pdf->getText();
        }
        return file_get_contents($path) ?: '';
    }

    public function chunkText(string $text): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        $words = explode(' ', $text);
        $chunks = [];
        $i = 0;
        while ($i < count($words)) {
            $chunkWords = array_slice($words, $i, self::CHUNK_SIZE);
            $chunks[] = implode(' ', $chunkWords);
            $i += self::CHUNK_SIZE - self::CHUNK_OVERLAP;
        }
        return $chunks;
    }

    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $tokens = array_filter(array_unique(explode(' ', $text)), fn($t) => mb_strlen($t) > 2);
        return array_values($tokens);
    }

    private function bm25Score(array $queryTokens, array $docTokens, int $docLen): float
    {
        if (empty($docTokens)) return 0.0;
        $avgdl = \App\Models\KnowledgeChunk::avg('token_count') ?? self::CHUNK_SIZE;
        $score = 0.0;
        $N = \App\Models\KnowledgeChunk::count() ?: 1;

        foreach ($queryTokens as $term) {
            $df = \App\Models\KnowledgeChunk::whereJsonContains('bm25_tokens', $term)->count();
            $idf = log((($N - $df + 0.5) / ($df + 0.5)) + 1);
            $tf = array_count_values($docTokens)[$term] ?? 0;
            $score += $idf * ($tf * (self::BM25_K1 + 1)) / ($tf + self::BM25_K1 * (1 - self::BM25_B + self::BM25_B * ($docLen / $avgdl)));
        }
        return $score;
    }
}
