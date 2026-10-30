<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ConsultationRequest;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('consultations:purge-technical', function () {
    $count = ConsultationRequest::whereNotNull('technical_metadata')
        ->where(function ($query) {
            $query->where('created_at', '<=', now()->subDays(90))
                ->orWhere('retain_until', '<=', now());
        })->update(['technical_metadata' => null]);
    $this->info("Cleared technical metadata for {$count} requests.");
})->purpose('Remove expired consultation technical metadata');

Schedule::command('consultations:purge-technical')->daily();
