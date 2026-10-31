<?php
namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_cards_open_detail_pages_for_all_seeded_categories(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $home=$this->get('/')->assertOk()->assertSee('urgent-attention');
        foreach (Category::all() as $category) {
            $home->assertSee(route('categories.show',$category));
            $this->get(route('categories.show',$category))->assertOk()->assertSee($category->name_ar)->assertSee('طلب مساعدة عاجلة');
            $this->assertNotEmpty($category->content_ar);
            $this->assertNotEmpty($category->content_en);
        }
        $this->withSession(['locale'=>'en'])->get('/categories/account-hacking')->assertOk()->assertSee('What is account compromise?');
    }

    public function test_inactive_categories_are_not_public_and_guide_markup_is_sanitized(): void
    {
        $category=Category::create(['name_ar'=>'نوع تجريبي','name_en'=>'Example type','slug'=>'example-type','is_active'=>false,'content_ar'=>'## Test']);
        $this->get('/categories/example-type')->assertNotFound();
        $category->update(['is_active'=>true,'content_ar'=>'## Editable heading' . "\n\n" . '<script>window.bad=true</script>' . "\n\n" . '[link](javascript:alert(1))']);
        $this->get('/categories/example-type')->assertOk()->assertSee('Editable heading')->assertDontSee('<script>window.bad',false)->assertDontSee('href="javascript:',false);
    }
}
