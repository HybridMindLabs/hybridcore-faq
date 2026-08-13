<?php

namespace Hybridcore\Faq\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        $entries = FaqEntry::orderBy('category')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $groups = $entries->groupBy(fn (FaqEntry $e) => $e->category ?: trans('faq::messages.uncategorized'))
            ->map(fn ($group, $category) => ['category' => $category, 'entries' => $group->values()])
            ->values();

        return Inertia::render('Extensions/hybridcore/faq/Admin/Index', [
            'groups' => $groups,
            'categories' => $entries->pluck('category')->filter()->unique()->sort()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // Blank slug: let FaqEntry::booted()'s creating hook generate one from
        // the question — same generator update() below calls explicitly.
        if (blank($data['slug'])) {
            unset($data['slug']);
        }

        FaqEntry::create($data);

        return back()->with('success', trans('faq::messages.created'));
    }

    public function update(Request $request, FaqEntry $entry): RedirectResponse
    {
        $data = $this->validated($request, $entry);
        $data['slug'] = $data['slug'] ?: FaqEntry::uniqueSlug($data['question'], $entry->id);

        $entry->update($data);

        return back()->with('success', trans('faq::messages.updated'));
    }

    public function destroy(FaqEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('success', trans('faq::messages.deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?FaqEntry $entry = null): array
    {
        return $request->validate([
            'category' => ['nullable', 'string', 'max:60'],
            'question' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('faq_entries', 'slug')->ignore($entry?->id),
            ],
            'answer' => ['required', 'string', 'max:5000'],
            'is_published' => ['boolean'],
            'sort' => ['integer', 'min:0', 'max:9999'],
        ]);
    }
}
