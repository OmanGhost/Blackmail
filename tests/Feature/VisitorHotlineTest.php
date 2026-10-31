<?php
namespace Tests\Feature;

use App\Models\Country;
use App\Models\ReportingAuthority;
use App\Services\VisitorHotline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorHotlineTest extends TestCase
{
    use RefreshDatabase;

    private function oman(bool $verifiedAuthority = true): ReportingAuthority
    {
        $country = Country::create(['code'=>'OM','name_ar'=>'عُمان','name_en'=>'Oman','slug'=>'oman','verification_status'=>'verified','is_published'=>true]);
        return ReportingAuthority::create(['country_id'=>$country->id,'name_ar'=>'الجرائم الاقتصادية','phone'=>'80077444',
            'verification_status'=>$verifiedAuthority ? 'verified' : 'in_review','is_published'=>$verifiedAuthority]);
    }

    public function test_unknown_visitors_see_the_platform_number_and_untrusted_headers_are_ignored(): void
    {
        $this->oman();
        config(['platform.geo.header'=>null]);
        $this->withHeader('CF-IPCountry','OM')->get('/')->assertOk()
            ->assertSee('data-hotline="platform"',false)->assertSee('+968-78444499')->assertSee('href="tel:+96878444499"',false)
            ->assertDontSee('80077444');
    }

    public function test_detected_country_shows_its_verified_authority_number(): void
    {
        $this->oman();
        config(['platform.geo.header'=>'CF-IPCountry']);
        $this->withHeader('CF-IPCountry','om')->get('/')->assertOk()
            ->assertSee('data-hotline="authority"',false)->assertSee('80077444')->assertSee('href="tel:80077444"',false)
            ->assertSee('للإبلاغ في عُمان');
        $this->withHeader('CF-IPCountry','SA')->get('/countries')->assertSee('+968-78444499');
    }

    public function test_unverified_authorities_fall_back_to_the_local_platform_number(): void
    {
        $this->oman(false);
        config(['platform.geo.header'=>'CF-IPCountry']);
        $this->withHeader('CF-IPCountry','OM')->get('/')->assertSee('data-hotline="platform"',false)
            ->assertSee('<b dir="ltr">78444499</b>',false)->assertDontSee('80077444');
    }

    public function test_visitor_choice_overrides_detection_and_cache_follows_changes(): void
    {
        $authority = $this->oman();
        $this->withMiddleware(\App\Http\Middleware\VerifyHuman::class);
        $this->from('/countries')->post('/visitor-country',['country'=>'OM'])->assertRedirect('/countries');
        $this->get('/')->assertSee('80077444')->assertSee('<option value="OM" selected',false);

        $authority->update(['phone'=>'9999']);
        $this->get('/')->assertSee('href="tel:9999"',false);

        $this->post('/visitor-country',['country'=>'ZZ'])->assertSessionHasErrors('country');
        $this->post('/visitor-country',['country'=>'auto'])->assertRedirect();
        $this->get('/')->assertSee('data-hotline="platform"',false);
        $this->assertNull(session(VisitorHotline::SESSION_CHOICE));
    }

    public function test_local_database_lookup_when_installed(): void
    {
        if (! is_file(config('platform.geo.database'))) {
            $this->markTestSkipped('Run php artisan geoip:update to install the country database.');
        }
        $this->oman();
        $this->withServerVariables(['REMOTE_ADDR'=>'5.36.0.1'])->get('/')->assertSee('80077444');
        $service = app(VisitorHotline::class);
        $this->assertNull($service->lookup('127.0.0.1'));
        $this->assertNull($service->lookup('not-an-ip'));
    }
}
