<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_describes_an_installable_app(): void
    {
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('./', $manifest['start_url']);
        $this->assertSame('./', $manifest['scope']);
        $purposes = array_column($manifest['icons'], 'purpose');
        $this->assertContains('maskable', $purposes);
        $this->assertContains('any', $purposes);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path($icon['src']));
            [$width, $height] = getimagesize(public_path($icon['src']));
            $this->assertSame($icon['sizes'], $width.'x'.$height);
        }
        foreach ($manifest['shortcuts'] as $shortcut) {
            $this->get('/'.$shortcut['url'])->assertOk();
        }
    }

    public function test_pages_link_the_manifest_and_offer_installation(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('name="apple-mobile-web-app-capable" content="yes"', false)
            ->assertSee('data-pwa-install hidden', false)
            ->assertSee(__('site.install_app'));
    }

    public function test_offline_page_gives_the_platform_hotline_and_is_not_indexed(): void
    {
        $this->get('/offline')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('tel:'.preg_replace('/[^\d+]/', '', config('platform.hotline.local')), false)
            ->assertSee(config('platform.hotline.international'))
            ->assertDontSee('/build/', false);
    }

    public function test_service_worker_never_stores_pages_on_the_device(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertStringContainsString('fetch(request).catch(() => caches.match(OFFLINE_URL))', $worker);
        $this->assertStringContainsString("if (request.method !== 'GET') return;", $worker);
        $this->assertStringNotContainsString('storage/', $worker);
        $this->assertStringContainsString("const VERSION = '".trim(file_get_contents(base_path('VERSION')))."'", $worker);
    }
}
