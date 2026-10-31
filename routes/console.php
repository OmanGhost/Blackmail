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

Artisan::command('consultations:purge-recovery', function () {
    $count = \Illuminate\Support\Facades\DB::table('request_recovery_tokens')->where('expires_at','<=',now())->delete();
    $this->info("Removed {$count} expired recovery tokens.");
})->purpose('Remove expired request access recovery tokens');
Schedule::command('consultations:purge-recovery')->hourly();

Artisan::command('consultations:purge-evidence', function () {
    $count=0;
    \App\Models\EvidenceSubmission::whereNull('withdrawn_at')->where(function ($query) {
        $query->where('created_at','<=',now()->subDays(30))->orWhereHas('evidenceRequest.consultationRequest',fn ($case)=>$case->where('retain_until','<=',now()));
    })->chunkById(50,function ($submissions) use (&$count) {
        foreach ($submissions as $submission) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($submission): void {
                $locked=\App\Models\EvidenceSubmission::lockForUpdate()->findOrFail($submission->id);
                $path=$locked->file_path;
                if ($path) \Illuminate\Support\Facades\Storage::disk('secure_evidence')->delete($path);
                $locked->update(['body'=>null,'original_name'=>null,'file_path'=>null,'ciphertext_sha256'=>null,'withdrawn_at'=>now()]);
            });
            $count++;
        }
    });
    $this->info("Removed expired content from {$count} secure submissions.");
})->purpose('Delete expired encrypted evidence files and text');
Schedule::command('consultations:purge-evidence')->daily();
