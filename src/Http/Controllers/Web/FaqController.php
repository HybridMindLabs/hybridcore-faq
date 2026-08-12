<?php

namespace Hybridcore\Faq\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\MarkdownRenderer;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(MarkdownRenderer $markdown): Response
    {
        $entries = FaqEntry::where('is_published', true)
            ->orderBy('category')
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (FaqEntry $e) => $e->category ?: trans('faq::messages.uncategorized'))
            ->map(fn ($group) => $group->map(fn (FaqEntry $e) => [
                'id' => $e->id,
                'question' => $e->question,
                'answer_html' => $markdown->toHtml($e->answer),
            ])->values())
            ->map(fn ($entries, $category) => ['category' => $category, 'entries' => $entries])
            ->values();

        return Inertia::render('Extensions/hybridcore/faq/Web/Index', [
            'groups' => $entries,
        ]);
    }

    public function feedback(Request $request, FaqEntry $entry): RedirectResponse
    {
        abort_unless($entry->is_published, 404);

        $data = $request->validate(['helpful' => ['required', 'boolean']]);

        $entry->increment($data['helpful'] ? 'helpful_count' : 'not_helpful_count');

        return back();
    }
}
