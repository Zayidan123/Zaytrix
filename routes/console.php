<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('market:poll')->everyMinute();
Schedule::command('whale:poll')->everyTwoMinutes();
