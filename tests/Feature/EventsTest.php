<?php
namespace Tests\Feature;
use App\Models\{Event,User,Role};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class EventsTest extends TestCase
{
    use RefreshDatabase;
    private function event(string $slug,array $attributes=[]): Event
    {
        return Event::create($attributes+['slug'=>$slug,'title_ar'=>$slug,'title_en'=>'English '.$slug,'summary_ar'=>'ملخص','description_ar'=>'تفاصيل الفعالية','status'=>'published','event_type'=>'workshop','attendance_mode'=>'online','starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHours(2),'registration_url'=>'https://example.test/register','agenda'=>[['time'=>'10:00','title_ar'=>'مقدمة','title_en'=>'Introduction']]]);
    }
    public function test_upcoming_schedule_excludes_drafts_and_past_and_orders_by_time(): void
    {
        $this->event('later-event',['starts_at'=>now()->addDays(3),'ends_at'=>now()->addDays(3)->addHour()]);
        $first=$this->event('first-event');
        $this->event('draft-event',['status'=>'draft']);
        $this->event('past-event',['starts_at'=>now()->subDays(2),'ends_at'=>now()->subDay()]);
        $this->get('/events')->assertOk()->assertSeeInOrder(['first-event','later-event'])->assertDontSee('draft-event')->assertDontSee('past-event');
        $this->get('/events?period=past')->assertOk()->assertSee('past-event')->assertDontSee('first-event');
        $this->get('/events?type=lecture')->assertOk()->assertDontSee('first-event');
        $this->get(route('events.show',$first))->assertOk()->assertSee('مقدمة')->assertSee('https://example.test/register');
        $this->get('/events/draft-event')->assertNotFound();
        $this->withSession(['locale'=>'en'])->get('/events/first-event')->assertOk()->assertSee('English first-event')->assertSee('Introduction');
    }
    public function test_cancelled_and_ended_events_do_not_offer_registration(): void
    {
        $this->event('cancelled-event',['status'=>'cancelled']);
        $this->event('ended-event',['starts_at'=>now()->subDays(2),'ends_at'=>now()->subDay()]);
        $this->get('/events/cancelled-event')->assertOk()->assertSee('أُلغيت هذه الفعالية')->assertDontSee('https://example.test/register');
        $this->get('/events/ended-event')->assertOk()->assertSee('انتهت هذه الفعالية')->assertDontSee('https://example.test/register');
    }
    public function test_admin_can_create_edit_and_delete_with_timezone_and_date_validation(): void
    {
        $admin=User::factory()->create();$admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $component=\Livewire\Livewire::test(\App\Filament\Resources\Events\Pages\ManageEvents::class);
        $data=['slug'=>'new-workshop','title_ar'=>'ورشة جديدة','summary_ar'=>'ملخص الورشة','description_ar'=>'تفاصيل الورشة','status'=>'published','event_type'=>'workshop','attendance_mode'=>'onsite','starts_at'=>'2026-11-02 12:00:00','ends_at'=>'2026-11-02 11:00:00'];
        $component->callAction('create',$data)->assertHasFormErrors(['ends_at']);
        $component->call('unmountAction');
        $data['ends_at']='2026-11-02 14:00:00';
        $component->callAction('create',$data)->assertHasNoActionErrors();
        $event=Event::where('slug','new-workshop')->firstOrFail();
        $this->assertSame('2026-11-02 08:00:00',$event->starts_at->format('Y-m-d H:i:s'));
        $component->callTableAction('edit',$event,['status'=>'cancelled'])->assertHasNoActionErrors();
        $this->assertSame('cancelled',$event->fresh()->status);
        $component->callTableAction('delete',$event)->assertHasNoActionErrors();
        $this->assertModelMissing($event);
        $visitor=User::factory()->create();$this->actingAs($visitor);
        $this->assertFalse(\App\Filament\Resources\Events\EventResource::canAccess());
    }
}
