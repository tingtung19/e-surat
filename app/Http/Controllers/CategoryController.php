<?php

namespace App\Http\Controllers;

use App\Models\LetterCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('master.categories.index', ['categories' => LetterCategory::with('parent')->withCount('children')->latest()->paginate(15), 'parents' => LetterCategory::whereNull('parent_id')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('master.categories.create', ['parents' => LetterCategory::whereNull('parent_id')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureDepth($data['parent_id'] ?? null);
        LetterCategory::create($data);

        return redirect()->route('master.categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(LetterCategory $category): View
    {
        return view('master.categories.edit', ['category' => $category, 'parents' => LetterCategory::whereNull('parent_id')->whereKeyNot($category->id)->orderBy('name')->get()]);
    }

    public function update(Request $request, LetterCategory $category): RedirectResponse
    {
        $data = $this->validated($request);
        abort_if(($data['parent_id'] ?? null) == $category->id, 422, 'Kategori tidak dapat menjadi parent dirinya sendiri.');
        $this->ensureDepth($data['parent_id'] ?? null);
        $category->update($data);

        return redirect()->route('master.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(LetterCategory $category): RedirectResponse
    {
        abort_if($category->children()->exists() || $category->letters()->exists(), 422, 'Kategori yang memiliki sub-kategori atau surat tidak dapat dihapus.');
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', Rule::exists('letter_categories', 'id')],
            'number_format' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function ensureDepth(?int $parentId): void
    {
        if ($parentId && LetterCategory::whereKey($parentId)->whereNotNull('parent_id')->exists()) {
            abort(422, 'Sub-kategori hanya dapat memiliki maksimal satu parent.');
        }
    }
}
