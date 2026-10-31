<?php
namespace Tests\Feature;

use App\Models\ConsultationRequest;
use App\Models\EvidenceRequest;
use App\Models\EvidenceSubmission;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SecureEvidenceTest extends TestCase
{
    use DatabaseMigrations;

    private function prepare(string $kind='image'): EvidenceRequest
    {
        Storage::fake('secure_evidence');
        $this->post('/consultation',['category'=>'blackmail','preferred_contact_method'=>'email','contact_details'=>'person@example.test','description'=>'A sufficiently detailed description of this incident.','consent'=>'1'])->assertRedirect();
        return ConsultationRequest::firstOrFail()->evidenceRequests()->create(['kind'=>$kind,'instructions'=>'Send a redacted screenshot only.','expires_at'=>now()->addDay()]);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('screenshot.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aUioAAAAASUVORK5CYII='));
    }

    public function test_file_and_metadata_are_encrypted_and_can_be_withdrawn(): void
    {
        $item=$this->prepare();
        $file=$this->image();
        $original=file_get_contents($file->getRealPath());
        $this->post('/consultation/evidence/'.$item->id,['file'=>$file,'secure_body'=>'Additional context','consent'=>'1','no_secrets'=>'1'])->assertRedirect('/consultation/track');
        $submission=$item->submissions()->firstOrFail();
        $cipher=Storage::disk('secure_evidence')->get($submission->file_path);
        $this->assertNotSame($original,$cipher);
        $this->assertSame($original,Crypt::decryptString($cipher));
        $this->assertStringNotContainsString('Additional context',$submission->getRawOriginal('body'));
        $this->assertStringNotContainsString('screenshot.png',$submission->getRawOriginal('original_name'));
        $path=$submission->file_path;
        $this->post('/consultation/evidence/submissions/'.$submission->id.'/withdraw')->assertRedirect();
        Storage::disk('secure_evidence')->assertMissing($path);
        $this->assertNull($submission->fresh()->body);
        $this->assertNotNull($submission->fresh()->withdrawn_at);
    }

    public function test_upload_requires_case_access_and_consent_and_rejects_unsafe_files(): void
    {
        $item=$this->prepare();
        $this->post('/consultation/evidence/'.$item->id,['file'=>$this->image()])->assertSessionHasErrors(['consent','no_secrets']);
        $this->post('/consultation/evidence/'.$item->id,['file'=>UploadedFile::fake()->createWithContent('evil.html','<script>alert(1)</script>'),'consent'=>'1','no_secrets'=>'1'])->assertSessionHasErrors('file');
        $this->assertSame(0,EvidenceSubmission::count());
        $this->post('/consultation/forget');
        $this->post('/consultation/evidence/'.$item->id,['file'=>$this->image(),'consent'=>'1','no_secrets'=>'1'])->assertNotFound();
    }

    public function test_expired_or_closed_requests_reject_submissions(): void
    {
        $item=$this->prepare('account_info');
        $item->update(['expires_at'=>now()->subMinute()]);
        $this->post('/consultation/evidence/'.$item->id,['secure_body'=>'Account URL and public username','consent'=>'1','no_secrets'=>'1'])->assertSessionHasErrors('evidence');
        $item->update(['expires_at'=>now()->addDay()]);
        $item->consultationRequest->update(['status'=>'closed']);
        $this->post('/consultation/evidence/'.$item->id,['secure_body'=>'Account URL and public username','consent'=>'1','no_secrets'=>'1'])->assertSessionHasErrors('evidence');
    }

    public function test_only_authorized_staff_can_decrypt_and_access_is_logged(): void
    {
        $item=$this->prepare();
        $this->post('/consultation/evidence/'.$item->id,['file'=>$this->image(),'consent'=>'1','no_secrets'=>'1']);
        $submission=$item->submissions()->firstOrFail();
        $this->withoutMiddleware(\Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled::class);
        $support=User::factory()->create(['is_active'=>true]);
        $support->roles()->attach(Role::create(['name'=>'support-agent','label_ar'=>'دعم']));
        $this->actingAs($support,'admin')->get($this->panelPath('/secure-evidence/').$submission->id)->assertForbidden();
        $admin=User::factory()->create(['is_active'=>true]);
        $admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin,'admin')->get($this->panelPath('/secure-evidence/').$submission->id)->assertOk();
        $this->get($this->panelPath('/secure-evidence/').$submission->id.'/download')->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->assertSame(2,DB::table('evidence_access_logs')->where('user_id',$admin->id)->count());
    }

    public function test_expired_evidence_is_purged_and_case_deletion_removes_files(): void
    {
        $item=$this->prepare();
        $this->post('/consultation/evidence/'.$item->id,['file'=>$this->image(),'consent'=>'1','no_secrets'=>'1']);
        $submission=$item->submissions()->firstOrFail();
        $path=$submission->file_path;
        $submission->update(['created_at'=>now()->subDays(31)]);
        $this->assertFalse($submission->fresh()->isAvailable());
        $this->artisan('consultations:purge-evidence')->assertSuccessful();
        Storage::disk('secure_evidence')->assertMissing($path);
        $this->post('/consultation/evidence/'.$item->id,['file'=>$this->image(),'consent'=>'1','no_secrets'=>'1']);
        $path=$item->submissions()->whereNull('withdrawn_at')->firstOrFail()->file_path;
        $item->consultationRequest->delete();
        Storage::disk('secure_evidence')->assertMissing($path);
        $this->assertSame(0,EvidenceSubmission::count());
    }

    public function test_staff_can_request_evidence_and_user_sees_the_secure_form(): void
    {
        $item=$this->prepare();
        $admin=User::factory()->create(['is_active'=>true]);
        $admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\ConsultationRequests\Pages\ViewConsultationRequest::class,['record'=>$item->consultation_request_id])
            ->callAction('request_evidence',['kind'=>'account_info','instructions'=>'Please provide the public account URL only.'])->assertHasNoActionErrors();
        $this->assertSame(2,EvidenceRequest::count());
        $this->get('/consultation/track')->assertOk()->assertDontSee('Please provide the public account URL only.');
        $this->get('/consultation/track?panel=files')->assertOk()->assertSee('Please provide the public account URL only.')->assertSee('name="secure_body"',false);
    }

    public function test_production_rejects_unencrypted_http_submission(): void
    {
        $item=$this->prepare('account_info');
        $this->app->detectEnvironment(fn ()=>'production');
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
        try {
            $this->post('/consultation/evidence/'.$item->id,['secure_body'=>'Public username only','consent'=>'1','no_secrets'=>'1'])->assertSessionHasErrors('evidence');
            $this->assertSame(0,EvidenceSubmission::count());
        } finally {
            $this->app->detectEnvironment(fn ()=>'testing');
        }
    }
}
