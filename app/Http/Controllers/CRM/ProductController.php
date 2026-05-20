<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreProductRequest;
use App\Http\Requests\CRM\UpdateProductRequest;
use App\Models\Product;
use App\Support\CRM\FormatsCrmData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'category' => $request->string('category')->toString(),
        ];

        $products = Product::query()
            ->withCount('opportunities')
            ->search($filters['search'])
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($filters['category'], fn ($query, string $value) => $query->where('category', $value))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Product $product): array => $this->payload($product));

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Product::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'mode' => 'create',
            'product' => null,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'mode' => 'edit',
            'product' => $this->payload($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Produto ativado.' : 'Produto desativado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category,
            'description' => $product->description,
            'base_price' => $product->base_price,
            'formatted_base_price' => $product->base_price !== null ? FormatsCrmData::money($product->base_price) : null,
            'is_active' => $product->is_active,
            'sort_order' => $product->sort_order,
            'opportunities_count' => $product->opportunities_count ?? 0,
        ];
    }
}
