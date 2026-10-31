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
        $counts = \App\Models\ConsultationRequest::query()->selectRaw("COUNT(*) as total, SUM(CASE WHEN service_type = 'consultation' THEN 1 ELSE 0 END) as consultations, SUM(CASE WHEN service_type = 'support' THEN 1 ELSE 0 END) as support, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN service_type IS NULL THEN 1 ELSE 0 END) as legacy")->first();
        $topCategory = \App\Models\ConsultationRequest::query()->select('category')->selectRaw('COUNT(*) as aggregate')
            ->groupBy('category')->orderByDesc('aggregate')->orderBy('category')->first();
        return view('home', [
            'statistics' => ['total'=>(int)$counts->total, 'consultations'=>(int)$counts->consultations,
                'support'=>(int)$counts->support, 'completed'=>(int)$counts->completed, 'legacy'=>(int)$counts->legacy,
                'top_category'=>$topCategory?->category, 'visits'=>\Illuminate\Support\Facades\DB::table('platform_visit_totals')->where('id',1)->first()],
            'homepage' => HomepageSection::query()->where('is_enabled', true)->orderBy('sort_order')->get()->keyBy('key'),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'articles' => Article::query()->published()->with(['translations', 'category'])->latest('published_at')->limit(3)->get(),
            'countries' => Country::query()->verified()->orderBy('name_ar')->get(),
            'faqs' => Faq::query()->where('is_published', true)->orderBy('sort_order')->limit(6)->get(),
        ]);
    }
}
