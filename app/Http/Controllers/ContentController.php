<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function category(Category $category): View
    {
        abort_unless($category->is_active,404);
        $guides=require database_path('data/category-guides.php');
        return view('content.category',[
            'category'=>$category,
            'articles'=>$category->articles()->published()->with('translations')->latest('published_at')->paginate(6),
            'related'=>Category::where('is_active',true)->whereKeyNot($category->id)->orderBy('sort_order')->get(),
            'sources'=>$guides[$category->slug]['sources'] ?? [],
        ]);
    }
    public function encyclopedia(Request $request): View
    {
        $articles = Article::published()->with(['translations', 'category'])->when($request->filled('q'), fn ($q) => $q->whereHas('translations', fn ($t) => $t->where('title', 'like', '%'.$request->string('q').'%')))->paginate(9)->withQueryString();

        return view('content.encyclopedia', compact('articles'));
    }

    public function article(Article $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->isPast(), 404);
        $translation = $article->translations()->where('locale', app()->getLocale())->first() ?? $article->translations()->where('locale', 'ar')->firstOrFail();

        return view('content.article', compact('article', 'translation'));
    }

    public function countries(): View
    {
        return view('content.countries', ['countries' => Country::orderBy('name_ar')->get()]);
    }

    public function country(Country $country): View
    {
        abort_unless($country->is_published && $country->verification_status === 'verified', 404);

        return view('content.country', compact('country'));
    }
}
