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
        $articles = Article::published()->whereHas('translations')->with(['translations', 'category', 'author'])
            ->when(in_array($request->query('type'), ['article','news','video']), fn ($q) => $q->where('post_type',$request->query('type')))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('translations', fn ($t) => $t->where('title', 'like', '%'.$request->string('q').'%')))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString();

        return view('content.encyclopedia', compact('articles'));
    }

    public function article(Article $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->isPast(), 404);
        $translation = $article->translations()->where('locale', app()->getLocale())->first() ?? $article->translations()->where('locale', 'ar')->first() ?? $article->translations()->firstOrFail();

        return view('content.article', compact('article', 'translation'));
    }

    public function countries(\Illuminate\Http\Request $request): View
    {
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 60),
            'status' => in_array($request->query('status'), ['verified', 'pending'], true) ? $request->query('status') : 'all',
            'sort' => in_array($request->query('sort'), ['name', 'status', 'verified_at', 'authorities'], true) ? $request->query('sort') : 'name',
            'dir' => $request->query('dir') === 'desc' ? 'desc' : 'asc',
            'view' => $request->query('view') === 'cards' ? 'cards' : 'table',
            'per_page' => in_array((int) $request->query('per_page'), [12, 24, 48], true) ? (int) $request->query('per_page') : 12,
        ];
        $verified = fn ($query) => $query->where('verification_status', 'verified')->where('is_published', true);

        $query = Country::query()->withCount(['reportingAuthorities as authorities_count' => $verified]);
        if ($filters['q'] !== '') {
            $term = '%'.addcslashes($filters['q'], '\\%_').'%';
            $query->where(fn ($q) => $q->where('name_ar', 'like', $term)->orWhere('name_en', 'like', $term)->orWhere('code', 'like', $term));
        }
        if ($filters['status'] === 'verified') {
            $query->verified();
        } elseif ($filters['status'] === 'pending') {
            $query->where(fn ($q) => $q->where('verification_status', '!=', 'verified')->orWhere('is_published', false));
        }

        $nameColumn = app()->getLocale() === 'en' ? 'name_en' : 'name_ar';
        $isVerified = "CASE WHEN verification_status = 'verified' AND is_published = 1 THEN 0 ELSE 1 END";
        match ($filters['sort']) {
            // Unverified countries have no public verification date, so they always sort last.
            'verified_at' => $query->orderByRaw($isVerified)->orderBy('last_verified_at', $filters['dir']),
            'status' => $query->orderByRaw($isVerified.' '.$filters['dir']),
            'authorities' => $query->orderBy('authorities_count', $filters['dir']),
            default => null,
        };
        $query->orderBy($nameColumn, $filters['sort'] === 'name' ? $filters['dir'] : 'asc');

        return view('content.countries', [
            'countries' => $query->paginate($filters['per_page'])->withQueryString(),
            'filters' => $filters,
            'totals' => ['all' => Country::count(), 'verified' => Country::verified()->count()],
        ]);
    }

    public function country(Country $country): View
    {
        abort_unless($country->is_published && $country->verification_status === 'verified', 404);

        return view('content.country', compact('country'));
    }
}
