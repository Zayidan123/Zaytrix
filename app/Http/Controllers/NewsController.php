<?php
namespace App\Http\Controllers;

use App\Services\NewsService;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function __construct(
        private NewsService $newsService
    ) {}

    public function index(): JsonResponse
    {
        $news = $this->newsService->getLatest(50);
        return response()->json($news);
    }

    public function highImpact(): JsonResponse
    {
        $news = $this->newsService->getHighImpact(20);
        return response()->json($news);
    }

    public function refresh(): JsonResponse
    {
        $count = $this->newsService->refresh();
        return response()->json(['success' => true, 'stored' => $count]);
    }
}
