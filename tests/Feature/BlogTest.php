<?php
namespace Tests\Feature;
use App\Models\{Article,Category,Role,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BlogTest extends TestCase
{
    use RefreshDatabase;
    private function createPost(string $slug,string $type,string $date,string $status='published'): Article
    {
        $category=Category::firstOrCreate(['slug'=>'test-category'],['name_ar'=>'تصنيف','name_en'=>'Category','is_active'=>true]);
        $author=User::first()??User::factory()->create();
        $article=Article::create(['slug'=>$slug,'category_id'=>$category->id,'author_id'=>$author->id,'post_type'=>$type,'status'=>$status,'published_at'=>$date,'video_url'=>$type==='video'?'https://example.test/video':null]);
        $article->translations()->create(['locale'=>'ar','title'=>$slug,'summary'=>'Post summary','content'=>'<p>Safe post body</p><script>bad()</script>']);
        return $article;
    }
    public function test_blog_lists_latest_published_posts_and_filters_types_and_search(): void
    {
        $this->createPost('older-article','article',now()->subDays(2));
        $video=$this->createPost('newest-video','video',now()->subDay());
        $this->createPost('draft-news','news',now()->subHour(),'draft');
        $this->createPost('future-news','news',now()->addDay());
        $this->get('/blog')->assertOk()->assertSeeInOrder(['newest-video','older-article'])->assertDontSee('draft-news')->assertDontSee('future-news');
        $this->get('/blog?type=video')->assertOk()->assertSee('newest-video')->assertDontSee('older-article');
        $this->get('/blog?q=older')->assertOk()->assertSee('older-article')->assertDontSee('newest-video');
        $this->get('/blog/newest-video')->assertOk()->assertSee($video->video_url)->assertDontSee('<script>bad()',false);
        $this->get('/blog/future-news')->assertNotFound();
        $this->get('/blog/draft-news')->assertNotFound();
        $this->get('/encyclopedia')->assertRedirect(route('blog'));
        $this->get('/encyclopedia/newest-video')->assertRedirect(route('articles.show',$video));
    }
    public function test_admin_can_open_integrated_translation_editor(): void
    {
        $post=$this->createPost('editable-post','article',now()->subDay());
        $admin=User::first();$admin->roles()->attach(Role::create(['name'=>'super-admin','label_ar'=>'مدير']));
        $this->actingAs($admin);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $editor=\Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\EditArticle::class,['record'=>$post->id])->assertOk()->assertSee('المحتوى والترجمات');
        $translations=$editor->get('data.translations');
        $key=array_key_first($translations);
        $translations[$key]['title']='Updated blog title';
        $editor->fillForm(['post_type'=>'video','video_url'=>'https://example.test/new-video','translations'=>$translations])->call('save')->assertHasNoFormErrors();
        $this->assertSame('video',$post->fresh()->post_type);
        $this->assertSame('Updated blog title',$post->translations()->first()->title);
        $editor->fillForm(['published_at'=>'2026-10-31 12:00:00'])->call('save')->assertHasNoFormErrors();
        $this->assertSame('2026-10-31 08:00:00',$post->fresh()->published_at->format('Y-m-d H:i:s'));
    }
}
