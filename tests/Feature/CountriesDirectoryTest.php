<?php
namespace Tests\Feature;

use App\Models\Country;
use App\Models\ReportingAuthority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountriesDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function country(string $code, string $ar, string $en, bool $verified = false, ?string $date = null): Country
    {
        return Country::create(['code'=>$code,'name_ar'=>$ar,'name_en'=>$en,'slug'=>strtolower($en),
            'verification_status'=>$verified ? 'verified' : 'unverified','is_published'=>$verified,'last_verified_at'=>$date]);
    }

    public function test_directory_is_paginated_and_keeps_filters_in_page_links(): void
    {
        foreach (range(1, 30) as $i) {
            $this->country(chr(65 + intdiv($i, 26)).chr(65 + $i % 26), 'دولة '.str_pad($i, 2, '0', STR_PAD_LEFT), 'Country'.$i);
        }
        $response = $this->get('/countries')->assertOk()->assertSee('directory-table', false)->assertSee('عرض 1–12 من 30');
        $this->assertCount(12, $response->viewData('countries')->items());
        $this->get('/countries?per_page=24&page=2')->assertOk()->assertSee('عرض 25–30 من 30');
        $this->get('/countries?status=pending&per_page=24')->assertOk()->assertSee('status=pending', false);
    }

    public function test_search_status_filter_sorting_and_card_view(): void
    {
        $oman = $this->country('OM', 'عُمان', 'Oman', true, '2026-10-01');
        $this->country('QA', 'قطر', 'Qatar', true, '2026-09-01');
        $this->country('EG', 'مصر', 'Egypt');
        ReportingAuthority::create(['country_id'=>$oman->id,'name_ar'=>'جهة','verification_status'=>'verified','is_published'=>true]);
        ReportingAuthority::create(['country_id'=>$oman->id,'name_ar'=>'جهة غير منشورة','verification_status'=>'unverified','is_published'=>false]);

        $names = fn ($response) => collect($response->viewData('countries')->items())->pluck('code')->all();
        $this->assertSame(['OM'], $names($this->get('/countries?q=عُمان')));
        $this->assertSame(['QA'], $names($this->get('/countries?q=qat')));
        $this->assertSame(['EG'], $names($this->get('/countries?q=eg')));
        $this->assertSame(['OM','QA'], $names($this->get('/countries?status=verified')));
        $this->assertSame(['EG'], $names($this->get('/countries?status=pending')));
        $this->assertSame(['OM','QA','EG'], $names($this->get('/countries?sort=verified_at&dir=desc')));
        $this->assertSame(['QA','OM','EG'], $names($this->get('/countries?sort=verified_at&dir=asc')));
        $response = $this->get('/countries?sort=authorities&dir=desc');
        $this->assertSame('OM', $names($response)[0]);
        $this->assertSame(1, $response->viewData('countries')->items()[0]->authorities_count);
        $this->get('/countries?q=%25')->assertOk()->assertSee('لا توجد دول مطابقة');
        $this->get('/countries?view=cards')->assertOk()->assertSee('country-card', false)->assertDontSee('directory-table"', false)->assertSee(route('countries.show', $oman));
        $this->get('/countries')->assertDontSee(route('countries.show', 'egypt'));
    }

    public function test_invalid_options_fall_back_to_safe_defaults(): void
    {
        $this->country('OM', 'عُمان', 'Oman', true);
        $filters = $this->get('/countries?sort=id;drop&dir=sideways&status=x&per_page=1000&view=x')->assertOk()->viewData('filters');
        $this->assertSame(['q'=>'','status'=>'all','sort'=>'name','dir'=>'asc','view'=>'table','per_page'=>12], $filters);
    }
}
