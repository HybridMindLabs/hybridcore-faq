<?php

namespace Hybridcore\Faq\Tests\Feature;

use App\Services\Extensions\ExtensionAutoloader;
use App\Support\MarkdownRenderer;
use Hybridcore\Faq\FaqServiceProvider;
use Hybridcore\Faq\Http\Controllers\Web\FaqController;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), 'installed');
        $this->wireWebRoutes();
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    /**
     * Routes and translations normally register only for extensions the DB
     * marks enabled at boot — a fresh RefreshDatabase test has no such row,
     * so wire them by hand, same as TournamentServiceTest.
     */
    private function wireWebRoutes(): void
    {
        $base = base_path('extensions/hybridcore/faq');
        ExtensionAutoloader::register($base, ['namespace' => 'Hybridcore\\Faq']);
        $this->app->register(FaqServiceProvider::class);
        $this->app['translator']->addNamespace('faq', $base.'/resources/lang');

        Route::middleware('web')->group($base.'/routes/web.php');
        $this->app['router']->getRoutes()->refreshNameLookups();
    }

    public function test_index_groups_entries_by_category_and_renders_markdown(): void
    {
        FaqEntry::create(['category' => 'Accounts', 'question' => 'How do I reset my password?', 'answer' => 'Click **Forgot password** on the login page.', 'sort' => 1]);
        FaqEntry::create(['category' => 'Accounts', 'question' => 'How do I delete my account?', 'answer' => 'Go to account settings.', 'sort' => 2]);
        FaqEntry::create(['category' => null, 'question' => 'Is the server free?', 'answer' => 'Yes.', 'sort' => 1]);

        // Hit the controller directly rather than via the router: in this
        // test harness, routes are wired manually after the app has already
        // booted core's catch-all `/{slug}` page route, so a short top-level
        // path like `/faq` loses to it here even though real (DB-enabled)
        // extensions register before the catch-all in normal boot order —
        // see the "keep LAST" comment on that route in routes/web.php.
        request()->headers->set('X-Inertia', 'true');
        $response = app(FaqController::class)->index(app(MarkdownRenderer::class));
        $groups = $response->toResponse(request())->getData(true)['props']['groups'];

        $this->assertCount(2, $groups);
        $accounts = collect($groups)->firstWhere('category', 'Accounts');
        $this->assertCount(2, $accounts['entries']);
        $this->assertStringContainsString('<strong>Forgot password</strong>', $accounts['entries'][0]['answer_html']);

        $general = collect($groups)->firstWhere('category', 'General');
        $this->assertCount(1, $general['entries']);
    }

    public function test_unpublished_entries_are_excluded_from_the_public_page(): void
    {
        FaqEntry::create(['question' => 'Live question', 'answer' => 'A', 'is_published' => true]);
        FaqEntry::create(['question' => 'Draft question', 'answer' => 'A', 'is_published' => false]);

        request()->headers->set('X-Inertia', 'true');
        $response = app(FaqController::class)->index(app(MarkdownRenderer::class));
        $groups = $response->toResponse(request())->getData(true)['props']['groups'];

        $questions = collect($groups)->flatMap(fn ($g) => $g['entries'])->pluck('question');
        $this->assertTrue($questions->contains('Live question'));
        $this->assertFalse($questions->contains('Draft question'));
    }

    public function test_popular_only_includes_entries_with_at_least_one_helpful_vote(): void
    {
        // helpful_count isn't mass-assignable — force it directly.
        FaqEntry::create(['question' => 'Voted', 'answer' => 'A'])->forceFill(['helpful_count' => 5])->save();
        FaqEntry::create(['question' => 'Never voted', 'answer' => 'A']);

        request()->headers->set('X-Inertia', 'true');
        $response = app(FaqController::class)->index(app(MarkdownRenderer::class));
        $data = $response->toResponse(request())->getData(true)['props'];

        $this->assertCount(1, $data['popular']);
        $this->assertSame('Voted', $data['popular'][0]['question']);
        $this->assertStringContainsString('2', $data['metaDescription']); // entry count
    }

    public function test_feedback_on_an_unpublished_entry_is_not_found(): void
    {
        $entry = FaqEntry::create(['question' => 'Q', 'answer' => 'A', 'is_published' => false]);

        $this->post(route('faq.feedback', $entry->id), ['helpful' => true])->assertNotFound();
        $this->assertSame(0, $entry->fresh()->helpful_count);
    }

    public function test_feedback_increments_the_right_counter(): void
    {
        $entry = FaqEntry::create(['question' => 'Q', 'answer' => 'A']);

        $this->post(route('faq.feedback', $entry->id), ['helpful' => true]);
        $this->assertSame(1, $entry->fresh()->helpful_count);
        $this->assertSame(0, $entry->fresh()->not_helpful_count);

        $this->post(route('faq.feedback', $entry->id), ['helpful' => false]);
        $this->assertSame(1, $entry->fresh()->helpful_count);
        $this->assertSame(1, $entry->fresh()->not_helpful_count);
    }
}
