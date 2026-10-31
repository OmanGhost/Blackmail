<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_statistics_use_real_totals_and_count_a_session_only_once(): void
    {
        foreach ([['consultation','account','completed'],['support','blackmail','new'],[null,'blackmail','closed']] as $i=>[$service,$category,$status]) {
            DB::table('consultation_requests')->insert(['public_id'=>'case-'.$i,'tracking_hash'=>hash('sha256','case-'.$i),
                'service_type'=>$service,'category'=>$category,'status'=>$status,'preferred_contact_method'=>'email',
                'contact_details_encrypted'=>'private-contact','description_encrypted'=>'private-description',
                'consented_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }
        $response=$this->get('/')->assertOk()->assertSee('إحصائيات المنصة')->assertDontSee('private-contact');
        $stats=$response->viewData('statistics');
        $this->assertSame(3,$stats['total']);
        $this->assertSame(1,$stats['consultations']);
        $this->assertSame(1,$stats['support']);
        $this->assertSame(1,$stats['completed']);
        $this->assertSame(1,$stats['legacy']);
        $this->assertSame('blackmail',$stats['top_category']);
        $this->get('/');
        $this->get('/help');
        $this->assertSame(1,(int) DB::table('platform_visit_totals')->value('visitor_sessions'));
        $this->withSession(['locale'=>'en'])->get('/')->assertOk()->assertSee('Platform statistics');
    }

    public function test_known_bots_do_not_increment_visitors_and_new_forms_identify_the_service(): void
    {
        $this->withHeader('User-Agent','ExampleBot')->get('/')->assertOk();
        $this->assertSame(0,(int) DB::table('platform_visit_totals')->value('visitor_sessions'));
        $this->get('/stop-blackmail')->assertSee('name="service_type" value="support"',false);
        $this->get('/consultation')->assertSee('name="service_type" value="consultation"',false);
    }
}
