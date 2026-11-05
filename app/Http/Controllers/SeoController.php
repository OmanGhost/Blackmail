<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Country;
use App\Models\Event;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(['home', 'help', 'blackmail.help', 'blog', 'countries', 'events', 'protection', 'tools', 'consultation'])
            ->map(fn ($route) => ['loc' => route($route), 'lastmod' => null]);

        Category::where('is_active', true)->orderBy('sort_order')->get(['slug', 'updated_at'])
            ->each(fn ($category) => $urls->push(['loc' => route('categories.show', $category), 'lastmod' => $category->updated_at]));
        Article::published()->whereHas('translations')->latest('published_at')->get(['id', 'slug', 'updated_at', 'published_at'])
            ->each(fn ($article) => $urls->push(['loc' => route('articles.show', $article), 'lastmod' => $article->updated_at ?? $article->published_at]));
        Country::verified()->orderBy('name_ar')->get(['slug', 'updated_at', 'last_verified_at'])
            ->each(fn ($country) => $urls->push(['loc' => route('countries.show', $country), 'lastmod' => $country->updated_at ?? $country->last_verified_at]));
        Event::public()->latest('starts_at')->get(['slug', 'updated_at'])
            ->each(fn ($event) => $urls->push(['loc' => route('events.show', $event), 'lastmod' => $event->updated_at]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'.($url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '')."</url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        // Private pages are kept out of search results with a noindex header instead of Disallow,
        // because a blocked page can still be listed if search engines can't read its noindex.
        $lines = ['User-agent: *', 'Allow: /', '', 'Sitemap: '.route('sitemap')];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
