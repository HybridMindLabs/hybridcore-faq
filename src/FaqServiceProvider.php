<?php

namespace Hybridcore\Faq;

use App\Services\Extensions\Registries\ExtensionRegistry;
use App\Support\Slots;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class FaqServiceProvider extends ServiceProvider
{
    public function boot(ExtensionRegistry $registry): void
    {
        // ── Permissions ──────────────────────────────────────────
        $registry->permissions()->register('faq.view', 'View FAQ', 'faq');
        $registry->permissions()->register('faq.manage', 'Manage FAQ', 'faq');

        // ── Admin navigation ─────────────────────────────────────
        $registry->navigation()->register(
            label: 'faq::messages.nav',
            route: 'admin.faq.index',
            icon: 'CircleHelp',
            section: 'navigation.sections.content',
            permission: 'faq.view',
            sort: 67,
        );

        // ── Public (site header) navigation ──────────────────────
        $registry->publicNavigation()->register(
            label: 'faq::messages.nav',
            route: 'faq.index',
            icon: 'CircleHelp',
            sort: 67,
        );

        // ── Global search ─────────────────────────────────────────
        $registry->search()->register(
            key: 'faq',
            label: 'faq::messages.nav',
            icon: 'CircleHelp',
            resolver: fn (string $term, int $limit) => FaqEntry::query()
                ->where('is_published', true)
                ->where(fn ($q) => $q->where('question', 'like', '%'.$term.'%')
                    ->orWhere('answer', 'like', '%'.$term.'%'))
                ->orderBy('sort')
                ->limit($limit)
                ->get()
                ->map(fn (FaqEntry $e) => [
                    'title' => $e->question,
                    'url' => route('faq.index').'#faq-'.$e->id,
                    'meta' => $e->category,
                ])->all(),
        );

        // ── Homepage widget: most helpful answers ─────────────────
        $registry->slots()->register(
            Slots::HOME_MIDDLE_BOTTOM,
            'HybridcoreFaqWidget',
            fn () => $this->safeWidgetData(),
            priority: 60,
        );
    }

    /** Top published entries by helpfulness, resilient to the table not existing yet. */
    private function safeWidgetData(): array
    {
        try {
            if (! Schema::hasTable('faq_entries')) {
                return ['entries' => []];
            }

            return [
                // helpful_count > 0 — an unvoted entry isn't "popular", it's
                // arbitrary. An empty widget (hidden client-side) is more
                // honest than mislabeling one.
                'entries' => FaqEntry::where('is_published', true)
                    ->where('helpful_count', '>', 0)
                    ->orderByDesc('helpful_count')
                    ->orderBy('sort')
                    ->limit(4)
                    ->get(['id', 'category', 'question', 'helpful_count'])
                    ->map(fn (FaqEntry $e) => [
                        'id' => $e->id,
                        'category' => $e->category ?: trans('faq::messages.uncategorized'),
                        'question' => $e->question,
                        'helpful_count' => $e->helpful_count,
                    ])
                    ->all(),
            ];
        } catch (\Throwable) {
            return ['entries' => []];
        }
    }
}
