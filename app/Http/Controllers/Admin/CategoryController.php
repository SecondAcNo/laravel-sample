<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $filters = $request->only(['q', 'is_active']);

        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('tickets')
                ->filter($filters)
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $category = Category::create($request->validated());

        $auditLogService->record($request, 'category created', $category, null, $category->only([
            'name',
            'description',
            'is_active',
        ]));

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, AuditLogService $auditLogService): RedirectResponse
    {
        $beforeValues = $category->only(['name', 'description', 'is_active']);
        $category->update($request->validated());
        $auditLogService->record($request, 'category updated', $category, $beforeValues, $category->only([
            'name',
            'description',
            'is_active',
        ]));

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'Category updated.');
    }
}
