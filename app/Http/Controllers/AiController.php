<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\AiRouterService;
use App\Services\KnowledgeBaseService;

class AiController extends Controller
{
    public function __construct(
        protected AiRouterService $ai,
        protected KnowledgeBaseService $kb
    ) {}

    public function chat(): \Inertia\Response
    {
        return inertia('Ai/Chat', [
            'recentChats' => [],
        ]);
    }

    public function streamChat(Request $request): Response
    {
        $request->validate(['message' => 'required|string|max:2000']);
        return response()->stream(function () use ($request) {
            $this->ai->streamChat($request->input("message"), function (string $chunk) {
                echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function signalForm(): \Inertia\Response
    {
        return inertia('Ai/Signal', [
            'onchainSummary' => app(\App\Services\OnChainService::class)->summary(),
        ]);
    }

    public function generateSignal(Request $request): \Inertia\Response
    {
        $request->validate([
            'symbol' => 'required|string|max:10',
            'side' => 'required|in:long,short',
            'risk_pct' => 'required|numeric|min:0.1|max:10',
        ]);

        $prompt = "Generate a trading signal for {$request->symbol} ({$request->side}) with risk {$request->risk_pct}%. "
                . "Consider current on-chain data and technical indicators. "
                . "Return JSON with: entry, sl, tp, rrr, confidence, reasoning.";

        $result = $this->ai->chat($prompt);

        return inertia('Ai/Signal', [
            'signal' => $result,
            'onchainSummary' => app(\App\Services\OnChainService::class)->summary(),
        ]);
    }

    public function compareForm(): \Inertia\Response
    {
        return inertia('Ai/Compare', [
            'documents' => \App\Models\KnowledgeDocument::where('user_id', auth()->id())->latest()->get(),
        ]);
    }

    public function comparePdfs(Request $request): \Inertia\Response
    {
        $request->validate([
            'doc_ids' => 'required|array|min:2|max:5',
            'doc_ids.*' => 'exists:knowledge_documents,id',
            'question' => 'required|string|max:1000',
        ]);

        $docs = \App\Models\KnowledgeDocument::whereIn('id', $request->doc_ids)->get();
        $context = $this->kb->buildComparisonContext($docs, $request->question);

        $result = $this->ai->chat($context);

        return inertia('Ai/Compare', [
            'documents' => \App\Models\KnowledgeDocument::where('user_id', auth()->id())->latest()->get(),
            'comparison' => $result,
        ]);
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = $request->file('file')->store('documents', 'public');
        $doc = \App\Models\KnowledgeDocument::create([
            'user_id' => auth()->id(),
            'title' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $request->file('file')->getMimeType(),
        ]);

        $this->kb->indexDocument($doc);

        return back()->with('success', 'Document uploaded and indexed.');
    }
}
