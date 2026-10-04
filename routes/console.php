<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Borra de la base los tokens que ya expiraron (hace más de 24 horas).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
