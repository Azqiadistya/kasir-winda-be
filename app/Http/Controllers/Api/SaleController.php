<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Sale::class, 'sale');
    }

    public function index(Request $request)
    {
        $query = Sale::with('saleItems.product')->orderByDesc('created_at');

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $sales = $query->paginate($request->integer('per_page', 15));

        return SaleResource::collection($sales);
    }

    public function store(SaleRequest $request)
    {
        $data = $request->validated();
        $saleId = $data['id'] ?? (string) Str::orderedUuid();

        $existing = Sale::with('saleItems.product')->find($saleId);
        if ($existing) {
            return new SaleResource($existing);
        }

        $user = $request->user();

        try {
            $sale = DB::transaction(function () use ($data, $saleId, $user) {
                $total = 0;

                foreach ($data['items'] as $item) {
                    $total += $item['qty'] * $item['price'];
                }

                $sale = Sale::create([
                    'id' => $saleId,
                    'paid' => $data['paid'],
                    'total' => $total,
                    'change' => max(0, $data['paid'] - $total),
                    'cashier' => $user?->name ?? $user?->username ?? 'kasir',
                    'created_at' => $data['created_at'] ?? now(),
                ]);

                foreach ($data['items'] as $itemData) {
                    /** @var Product $product */
                    $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                    if ($product->stock < $itemData['qty']) {
                        throw new \RuntimeException("Stok produk {$product->name} tidak cukup");
                    }

                    $product->decrement('stock', $itemData['qty']);

                    SaleItem::create([
                        'id' => $itemData['id'] ?? (string) Str::orderedUuid(),
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'qty' => $itemData['qty'],
                        'price' => $itemData['price'],
                    ]);
                }

                return $sale->load('saleItems.product');
            });
        } catch (\Throwable $th) {
            return $this->error($th->getMessage(), 422);
        }

        return (new SaleResource($sale))->response()->setStatusCode(201);
    }

    public function show(Sale $sale)
    {
        return new SaleResource($sale->load('saleItems.product'));
    }
}
