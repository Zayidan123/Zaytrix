<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('market:poll')->everyMinute();
