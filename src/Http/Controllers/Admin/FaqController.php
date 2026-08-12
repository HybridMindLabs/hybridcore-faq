<?php

namespace Hybridcore\Faq\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Hybridcore\Faq\Models\FaqEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('Extensions/hybridcore/faq/Admin/Index', [
            'entries' => $entries,
            'categories' => $entries->pluck('category')->filter()->unique()->sort()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FaqEntry::create($this->validated($request));

        return back()->with('success', trans('faq::messages.created'));
    }

    public function update(Request $request, FaqEntry $entry): RedirectResponse
    {
        $entry->update($this->validated($request));

        return back()->with('success', trans('faq::messages.updated'));
    }

    public function destroy(FaqEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('success', trans('faq::messages.deleted'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['nullable', 'string', 'max:60'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_published' => ['boolean'],
            'sort' => ['integer', 'min:0', 'max:9999'],
        ]);
    }
}
