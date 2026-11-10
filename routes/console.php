<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('market:poll')->everyMinute();
Schedule::command('whale:poll')->everyTwoMinutes();

// Fase 5: Check alerts every minute
Schedule::job(new App\Jobs\CheckAlerts())->everyMinute();
