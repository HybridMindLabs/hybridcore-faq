<?php

namespace Hybridcore\Faq\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\MarkdownRenderer;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(MarkdownRenderer $markdown): Response
    {
        $published = FaqEntry::where('is_published', true)
            ->orderBy('category')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $render = function (FaqEntry $e) use ($markdown) {
            $html = $markdown->toHtml($e->answer);

            return [
                'id' => $e->id,
                'category' => $e->category ?: trans('faq::messages.uncategorized'),
                'question' => $e->question,
                'answer_html' => $html,
                'answer_text' => trim(preg_replace('/\s+/', ' ', strip_tags($html))),
            ];
        };

        $rendered = $published->map($render);

        $groups = $rendered->groupBy('category')
            ->map(fn ($entries, $category) => [
                'category' => $category,
                'slug' => Str::slug($category) ?: 'general',
                'entries' => $entries->values(),
            ])
            ->values();

        $popular = $published->sortByDesc('helpful_count')
            ->take(3)
            ->filter(fn (FaqEntry $e) => $e->helpful_count > 0)
            ->map($render)
            ->values();

        return Inertia::render('Extensions/hybridcore/faq/Web/Index', [
            'groups' => $groups,
            'popular' => $popular,
            'metaDescription' => $this->metaDescription($published),
        ]);
    }

    public function feedback(Request $request, FaqEntry $entry): RedirectResponse
    {
        abort_unless($entry->is_published, 404);

        $data = $request->validate(['helpful' => ['required', 'boolean']]);

        $entry->increment($data['helpful'] ? 'helpful_count' : 'not_helpful_count');

        return back();
    }

    /** @param  Collection<int, FaqEntry>  $entries */
    private function metaDescription($entries): string
    {
        $count = $entries->count();
        $categories = $entries->pluck('category')->filter()->unique()->take(4)->implode(', ');

        return $categories !== ''
            ? trans('faq::messages.meta_description_categorized', ['count' => $count, 'categories' => $categories])
            : trans('faq::messages.meta_description', ['count' => $count]);
    }
}
