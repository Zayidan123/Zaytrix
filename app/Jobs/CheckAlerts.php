<?php
namespace App\Jobs;

use App\Services\AlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $userId = null
    ) {}

    public function handle(AlertService $alertService): void
    {
        if ($this->userId) {
            $alertService->checkAlerts($this->userId);
        } else {
            $alertService->checkAllUsers();
        }
    }
}
