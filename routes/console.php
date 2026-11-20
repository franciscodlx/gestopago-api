<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('gestopago:sync-products')
    ->dailyAt('04:00')
    ->timezone(config('gestopago.transaction.timezone'))
    ->withoutOverlapping()
    ->onOneServer();
