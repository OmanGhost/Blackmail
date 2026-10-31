<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryContactNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_forms_only_expose_published_verified_country_and_authority_contacts(): void
    {
        $country = Country::create(['code'=>'OM', 'slug'=>'oman', 'name_ar'=>'عمان', 'name_en'=>'Oman',
            'is_published'=>true, 'verification_status'=>'verified', 'emergency_information_ar'=>'إرشادات موثقة',
            'emergency_information_en'=>'Verified emergency guidance', 'last_verified_at'=>'2026-10-31']);
        $authority = ReportingAuthority::create(['country_id'=>$country->id, 'name_ar'=>'جهة موثقة',
            'name_en'=>'Verified authority', 'phone'=>'+968 12345678', 'official_url'=>'https://example.test/report',
            'is_published'=>true, 'verification_status'=>'verified']);
        foreach (['unverified', 'expired', 'in_review'] as $status) {
            ReportingAuthority::create(['country_id'=>$country->id, 'name_ar'=>'Hidden '.$status,
                'phone'=>'hidden-phone-'.$status, 'is_published'=>true, 'verification_status'=>$status]);
        }
        ReportingAuthority::create(['country_id'=>$country->id, 'name_ar'=>'Hidden draft',
            'phone'=>'hidden-draft-phone', 'is_published'=>false, 'verification_status'=>'verified']);
        foreach (['/consultation', '/stop-blackmail', '/help'] as $path) {
            $response = $this->get($path)->assertOk()->assertSee('إرشادات موثقة')->assertSee($authority->official_url)
                ->assertSee('tel:+96812345678', false);
            $this->assertMatchesRegularExpression('/data-country-notice="'.$country->id.'"\s+hidden/', $response->getContent());
            foreach (['unverified','expired','in_review'] as $status) $response->assertDontSee('hidden-phone-'.$status);
            $response->assertDontSee('hidden-draft-phone');
        }
        $selected = $this->withSession(['locale'=>'en', '_old_input'=>['country_id'=>$country->id]])->get('/consultation')
            ->assertOk()->assertSee('Verified reporting contacts')->assertSee('Verified authority')
            ->assertSee('Verified emergency guidance');
        $this->assertDoesNotMatchRegularExpression('/data-country-notice="'.$country->id.'"\s+hidden/', $selected->getContent());

        $country->update(['is_published'=>false]);
        $this->get('/consultation')->assertOk()->assertDontSee('Verified emergency guidance')->assertDontSee($authority->official_url);
        $country->update(['is_published'=>true, 'verification_status'=>'expired']);
        $this->get('/consultation')->assertOk()->assertDontSee('Verified emergency guidance')->assertDontSee($authority->official_url);
    }
}
