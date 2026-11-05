<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function article(string $slug, string $status = 'published', array $translation = []): Article
    {
        $category = Category::firstOrCreate(['slug' => 'seo-category'], ['name_ar' => 'تصنيف', 'name_en' => 'Category', 'is_active' => true]);
        $article = Article::create(['slug' => $slug, 'category_id' => $category->id, 'author_id' => User::factory()->create(['name' => 'Seo Author'])->id,
            'post_type' => 'news', 'status' => $status, 'published_at' => now()->subDay()]);
        $article->translations()->create($translation + ['locale' => 'ar', 'title' => 'عنوان '.$slug, 'summary' => 'ملخص المقال', 'content' => '<p>نص</p>']);

        return $article;
    }

    private function jsonLd(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);

        return array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
    }

    public function test_sitemap_lists_only_public_content(): void
    {
        $this->article('public-post');
        $this->article('draft-post', 'draft');
        Country::create(['code' => 'OM', 'name_ar' => 'عُمان', 'name_en' => 'Oman', 'slug' => 'oman', 'verification_status' => 'verified', 'is_published' => true]);
        Country::create(['code' => 'QA', 'name_ar' => 'قطر', 'name_en' => 'Qatar', 'slug' => 'qatar', 'verification_status' => 'unverified', 'is_published' => false]);
        Event::create(['slug' => 'public-event', 'title_ar' => 'فعالية', 'summary_ar' => 'ملخص', 'description_ar' => 'تفاصيل', 'status' => 'published', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
        Event::create(['slug' => 'draft-event', 'title_ar' => 'مسودة', 'summary_ar' => 'ملخص', 'description_ar' => 'تفاصيل', 'status' => 'draft', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $urls = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        foreach ([route('home'), route('blog'), route('categories.show', 'seo-category'), url('/blog/public-post'), url('/countries/oman'), url('/events/public-event')] as $expected) {
            $this->assertContains($expected, $urls);
        }
        foreach (['draft-post', 'qatar', 'draft-event', 'login', 'account', 'consultation/track'] as $hidden) {
            $this->assertEmpty(array_filter($urls, fn ($url) => str_contains($url, $hidden)), $hidden.' must not be in the sitemap');
        }
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false)->assertDontSee('Disallow: /', false);
    }

    public function test_pages_have_their_own_description_and_share_tags(): void
    {
        $home = $this->get('/')->assertOk()
            ->assertSee('<meta name="description" content="'.e(__('seo.home')).'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false);
        $types = array_column($this->jsonLd($home->getContent()), '@type');
        $this->assertSame(['Organization', 'WebSite'], $types);

        $this->get('/countries')->assertOk()->assertSee(e(__('seo.countries')), false);
        $this->get('/blog?page=2')->assertOk()->assertSee('<link rel="canonical" href="'.url('/blog').'?page=2">', false);
    }

    public function test_article_uses_admin_seo_fields_and_structured_data(): void
    {
        $this->article('seo-post', translation: ['seo_title' => 'عنوان مخصص للبحث', 'seo_description' => 'وصف مخصص </script><b>للبحث</b>']);

        $response = $this->get('/blog/seo-post')->assertOk()
            ->assertSee('<title>عنوان مخصص للبحث | '.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="وصف مخصص للبحث">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertDontSee('</script><b>', false);

        [$article, $breadcrumbs] = $this->jsonLd($response->getContent());
        $this->assertSame('NewsArticle', $article['@type']);
        $this->assertSame('Seo Author', $article['author']['name']);
        $this->assertSame(url('/blog/seo-post'), $article['mainEntityOfPage']);
        $this->assertSame('BreadcrumbList', $breadcrumbs['@type']);
        $this->assertCount(3, $breadcrumbs['itemListElement']);
    }

    public function test_event_structured_data_reflects_status_and_attendance(): void
    {
        Event::create(['slug' => 'cancelled-online', 'title_ar' => 'ندوة', 'summary_ar' => 'ملخص الندوة', 'description_ar' => 'تفاصيل', 'status' => 'cancelled',
            'attendance_mode' => 'online', 'registration_url' => 'https://example.test/join', 'speaker_ar' => 'متحدث',
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

        $event = $this->jsonLd($this->get('/events/cancelled-online')->assertOk()->assertDontSee('example.test/join')->getContent())[0];
        $this->assertSame('Event', $event['@type']);
        $this->assertSame('https://schema.org/EventCancelled', $event['eventStatus']);
        $this->assertSame('https://schema.org/OnlineEventAttendanceMode', $event['eventAttendanceMode']);
        $this->assertSame('VirtualLocation', $event['location']['@type']);
        $this->assertSame('متحدث', $event['performer']['name']);
    }

    public function test_home_hero_image_is_compressed_and_described(): void
    {
        $this->seed();
        $this->get('/')->assertOk()
            ->assertSee('حتى لا تواجه الابتزاز الإلكتروني وحدك')
            ->assertSee('images/hero-support.webp', false)
            ->assertDontSee('images/hero-support.png', false)
            ->assertDontSee('<img src="'.asset('images/hero-support.webp').'" width="1535" height="1024" alt=""', false);
        $this->assertFileExists(public_path('images/hero-support.webp'));
    }

    public function test_admin_labels_are_not_given_an_english_plural_s(): void
    {
        app()->setLocale('ar');
        $resources = glob(app_path('Filament/Resources/*/*Resource.php'));
        $this->assertNotEmpty($resources);
        foreach ($resources as $file) {
            $class = 'App\\Filament\\Resources\\'.basename(dirname($file)).'\\'.basename($file, '.php');
            $plural = $class::getPluralModelLabel();
            $this->assertDoesNotMatchRegularExpression('/\p{Arabic}s$/u', $plural, $class.' plural label: '.$plural);
        }
    }

    public function test_private_and_thin_pages_are_kept_out_of_search_results(): void
    {
        $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/register')->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/blog?q=test')->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/consultation/track')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/'.config('platform.admin_path').'/login')->assertHeader('X-Robots-Tag', 'noindex, follow');
        $this->get('/blog')->assertHeaderMissing('X-Robots-Tag');
        $this->get('/consultation')->assertHeaderMissing('X-Robots-Tag');
    }
}
