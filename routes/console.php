<?php

use App\Models\Voucher;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::call(function () {
    $now = now();

    Voucher::where('activation_date', '<=', $now)
        ->where('is_active', false)
        ->update(['is_active' => true]);

    Voucher::where('expiry_date', '<=', $now)
        ->where('is_active', true)
        ->update(['is_active' => false]);
})->everyMinute();
