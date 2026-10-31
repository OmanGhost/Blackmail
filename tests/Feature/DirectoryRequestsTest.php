<?php
namespace Tests\Feature;
use App\Models\Country;
use App\Models\DirectoryRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use Tests\TestCase;

class DirectoryRequestsTest extends TestCase
{
    use RefreshDatabase;
    private function country(): Country { return Country::create(['code'=>'OM','slug'=>'oman','name_ar'=>'عمان','name_en'=>'Oman','verification_status'=>'verified','is_published'=>true]); }
    private function payload(): array { return ['kind'=>'addition','country_name'=>'Example country','contact_name'=>'Official contact','organization'=>'Example authority','job_title'=>'Directory coordinator','email'=>'contact@example.test','official_url'=>'https://example.test/report','details'=>'Proposed official reporting contacts and supporting information.','consent'=>'1']; }

    public function test_directory_links_and_localized_forms_are_available(): void
    {
        $country=$this->country();
        $url=route('directory.requests.create',['kind'=>'correction','country'=>$country->id]);
        $this->get('/countries')->assertOk()->assertSee('طلب إضافة دولة')->assertSee($url);
        $this->get('/countries/oman')->assertOk()->assertSee($url);
        $this->get($url)->assertOk()->assertSee('name="country_id"',false)->assertSee('name="human_token"',false);
        $this->withSession(['locale'=>'en'])->get('/directory-request')->assertOk()->assertSee('Official role');
    }

    public function test_submissions_are_encrypted_and_never_change_the_directory(): void
    {
        $country=$this->country();
        $this->post('/directory-request',$this->payload()+['status'=>'completed'])->assertRedirect();
        $record=DirectoryRequest::firstOrFail();
        $this->assertSame('pending',$record->status);
        $this->assertSame('contact@example.test',$record->email);
        $this->assertNotSame('contact@example.test',DB::table('directory_requests')->value('email'));
        $data=$this->payload();
        $data['kind']='correction'; $data['country_id']=$country->id;
        $this->post('/directory-request',$data)->assertRedirect();
        $this->assertSame('عمان',$country->fresh()->name_ar);
        $this->assertDatabaseCount('countries',1);
        $this->assertDatabaseCount('directory_requests',2);
    }

    public function test_required_sources_consent_and_authority_fields_are_validated(): void
    {
        $this->post('/directory-request',['kind'=>'addition','official_url'=>'javascript:alert(1)'])->assertSessionHasErrors(['contact_name','organization','job_title','official_url','consent']);
        $data=$this->payload();$data['kind']='correction';
        $this->post('/directory-request',$data)->assertSessionHasErrors('country_id');
        $this->assertDatabaseCount('directory_requests',0);
    }

    public function test_only_admin_can_review_and_review_does_not_automatically_publish(): void
    {
        $country=$this->country();
        $this->post('/directory-request',$this->payload());
        $record=DirectoryRequest::firstOrFail();
        $user=User::factory()->create();
        $this->actingAs($user);
        $this->assertFalse(\App\Filament\Resources\DirectoryRequests\DirectoryRequestResource::canAccess());
        $user->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->assertTrue(\App\Filament\Resources\DirectoryRequests\DirectoryRequestResource::canAccess());
        \Livewire\Livewire::test(\App\Filament\Resources\DirectoryRequests\Pages\ManageDirectoryRequests::class)
            ->callTableAction('review',$record,['status'=>'in_review','review_notes'=>'Checking source'])
            ->assertHasNoActionErrors();
        $this->assertSame('in_review',$record->fresh()->status);
        $this->assertSame('Checking source',$record->fresh()->review_notes);
        $this->assertSame('عمان',$country->fresh()->name_ar);
    }
}
