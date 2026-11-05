<?php

namespace App\Support;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds schema.org structured data (JSON-LD) so search engines understand each page.
 */
class Seo
{
    public static function defaultImage(): string
    {
        return asset('images/og-default.jpg');
    }

    public static function publicImage(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('icon-512.png'),
            'description' => __('seo.default'),
            'contactPoint' => [[
                '@type' => 'ContactPoint',
                'telephone' => config('platform.hotline.international'),
                'contactType' => 'customer support',
                'availableLanguage' => ['Arabic', 'English'],
            ]],
        ];
    }

    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('app.name'),
            'url' => url('/'),
            'inLanguage' => ['ar', 'en'],
            'publisher' => ['@id' => url('/').'#organization'],
        ];
    }

    public static function article(Article $article, ArticleTranslation $translation): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $article->post_type === 'news' ? 'NewsArticle' : 'Article',
            'headline' => Str::limit($translation->seo_title ?: $translation->title, 110, ''),
            'description' => static::text($translation->seo_description ?: $translation->summary),
            'image' => [static::publicImage($article->featured_image) ?? static::defaultImage()],
            'datePublished' => $article->published_at?->toAtomString(),
            'dateModified' => ($article->updated_at ?? $article->published_at)?->toAtomString(),
            'author' => $article->author
                ? ['@type' => 'Person', 'name' => $article->author->name]
                : ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
            'publisher' => ['@type' => 'Organization', 'name' => config('app.name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('icon-512.png')]],
            'mainEntityOfPage' => route('articles.show', $article),
            'inLanguage' => $translation->locale,
        ];
    }

    public static function event(Event $event): array
    {
        $modes = ['onsite' => 'Offline', 'online' => 'Online', 'hybrid' => 'Mixed'];
        $mode = $event->attendance_mode;
        $venue = $event->localized('venue');
        $locations = [];
        if (in_array($mode, ['online', 'hybrid'], true)) {
            // The event page decides when registration is offered, so never expose the registration link here.
            $locations[] = ['@type' => 'VirtualLocation', 'url' => route('events.show', $event)];
        }
        if (in_array($mode, ['onsite', 'hybrid'], true) && $venue) {
            $locations[] = array_filter(['@type' => 'Place', 'name' => $venue, 'address' => $venue, 'url' => $event->location_url]);
        }
        $speaker = $event->localized('speaker');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->localized('title'),
            'description' => static::text($event->localized('summary')),
            'startDate' => $event->starts_at?->toAtomString(),
            'endDate' => $event->ends_at?->toAtomString(),
            'eventStatus' => 'https://schema.org/'.($event->status === 'cancelled' ? 'EventCancelled' : 'EventScheduled'),
            'eventAttendanceMode' => 'https://schema.org/'.($modes[$mode] ?? 'Offline').'EventAttendanceMode',
            'location' => count($locations) === 1 ? $locations[0] : ($locations ?: null),
            'image' => [static::publicImage($event->cover_image) ?? static::defaultImage()],
            'url' => route('events.show', $event),
            'performer' => $speaker ? ['@type' => 'Person', 'name' => $speaker] : null,
            'organizer' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => url('/')],
        ]);
    }

    /** @param array<int, array{0: string, 1: string}> $items [name, url] pairs from the home page down. */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($item, $i) => [
                '@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1],
            ], $items, array_keys($items)),
        ];
    }

    /** Plain, single-line text for meta descriptions. */
    public static function text(?string $value, int $limit = 160): string
    {
        $plain = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        return Str::limit($plain, $limit, '…');
    }
}
