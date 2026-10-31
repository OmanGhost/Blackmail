<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Country;
use App\Models\Faq;
use App\Models\HomepageSection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'homepage' => HomepageSection::query()->where('is_enabled', true)->orderBy('sort_order')->get()->keyBy('key'),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'articles' => Article::query()->published()->with(['translations', 'category'])->latest('published_at')->limit(3)->get(),
            'countries' => Country::query()->verified()->orderBy('name_ar')->get(),
            'faqs' => Faq::query()->where('is_published', true)->orderBy('sort_order')->limit(6)->get(),
        ]);
    }
}
