<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeDocument;
use App\Services\KnowledgeBaseService;

class AiIndexCommand extends Command
{
    protected $signature = 'ai:index {--document_id= : ID of specific document to index}';
    protected $description = 'Index documents into the AI knowledge base (BM25)';

    public function handle(KnowledgeBaseService $kb): int
    {
        $docId = $this->option('document_id');
        $query = KnowledgeDocument::query();
        if ($docId) {
            $query->where('id', $docId);
        }
        $docs = $query->get();
        if ($docs->isEmpty()) {
            $this->warn('No documents to index.');
            return self::SUCCESS;
        }
        $this->info("Indexing {$docs->count()} document(s)...");
        $bar = $this->output->createProgressBar($docs->count());
        foreach ($docs as $doc) {
            try {
                $count = $kb->indexDocument($doc);
                $this->newLine();
                $this->info("  ✓ {$doc->title} → {$count} chunks");
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("  ✗ {$doc->title}: {$e->getMessage()}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Indexing complete.');
        return self::SUCCESS;
    }
}
