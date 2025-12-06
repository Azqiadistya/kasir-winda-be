<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncPushRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    public function pull(Request $request)
    {
        $since = $request->query('since');
        $sinceTime = $since ? Carbon::parse($since) : null;

        $tables = [
            'categories' => [Category::class, ['id', 'name', 'created_at', 'updated_at', 'deleted_at'], true],
            'products' => [Product::class, ['id', 'category_id', 'name', 'barcode', 'price', 'cost', 'stock', 'image', 'created_at', 'updated_at', 'deleted_at'], true],
            'sales' => [Sale::class, ['id', 'paid', 'total', 'change', 'cashier', 'created_at', 'updated_at', 'deleted_at'], true],
            'sale_items' => [SaleItem::class, ['id', 'sale_id', 'product_id', 'qty', 'price', 'created_at', 'updated_at', 'deleted_at'], true],
            'expenses' => [Expense::class, ['id', 'title', 'amount', 'created_at', 'updated_at', 'deleted_at'], true],
            'store_settings' => [StoreSetting::class, ['id', 'name', 'address', 'postal', 'phone', 'logo', 'cashier_name', 'created_at', 'updated_at'], false],
            'users' => [User::class, ['id', 'name', 'username', 'role', 'created_at', 'updated_at', 'deleted_at'], true],
        ];

        $data = [];

        foreach ($tables as $table => [$modelClass, $columns, $withDeleted]) {
            $query = $modelClass::query();
            if ($withDeleted && in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses($modelClass))) {
                $query->withTrashed();
            }

            if ($sinceTime) {
                $query->where(function ($q) use ($sinceTime, $withDeleted) {
                    $q->where('updated_at', '>', $sinceTime);
                    if ($withDeleted) {
                        $q->orWhere('deleted_at', '>', $sinceTime);
                    }
                });
            }

            $data[$table] = $query->get()->map->only($columns)->values();
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'serverTime' => now()->toIso8601String(),
            ],
        ]);
    }

    public function push(SyncPushRequest $request)
    {
        $request->validated();
        $changes = $request->input('changes', []);
        $applied = [];
        $conflicts = [];
        $serverTime = now()->toIso8601String();

        DB::transaction(function () use ($changes, &$applied, &$conflicts) {
            $this->applyGenericChanges('categories', Category::class, ['name'], $changes, $applied, $conflicts);
            $this->applyGenericChanges('products', Product::class, ['category_id', 'name', 'barcode', 'price', 'cost', 'stock', 'image'], $changes, $applied, $conflicts);
            $this->applyGenericChanges('sales', Sale::class, ['paid', 'total', 'change', 'cashier', 'created_at'], $changes, $applied, $conflicts, true);
            $this->applySaleItems($changes['sale_items'] ?? [], $applied, $conflicts);
            $this->applyGenericChanges('expenses', Expense::class, ['title', 'amount', 'created_at'], $changes, $applied, $conflicts);
            $this->applyGenericChanges('store_settings', StoreSetting::class, ['name', 'address', 'postal', 'phone', 'logo', 'cashier_name'], $changes, $applied, $conflicts, false);
            $this->applyGenericChanges('users', User::class, ['name', 'username', 'password', 'role'], $changes, $applied, $conflicts, true, true);
        });

        return $this->success([
            'applied' => $applied,
            'conflicts' => $conflicts,
            'serverTime' => $serverTime,
        ]);
    }

    private function applyGenericChanges(
        string $table,
        string $modelClass,
        array $fillable,
        array $changes,
        array &$applied,
        array &$conflicts,
        bool $withDeleted = true,
        bool $hashPassword = false
    ): void {
        foreach ($changes[$table] ?? [] as $change) {
            $incomingTime = Carbon::parse($change['updated_at']);
            $id = $change['id'] ?? (string) Str::orderedUuid();

            $query = $modelClass::query();
            if ($withDeleted && in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses($modelClass))) {
                $query->withTrashed()->lockForUpdate();
            } else {
                $query->lockForUpdate();
            }

            /** @var \Illuminate\Database\Eloquent\Model|null $model */
            $model = $query->find($id);

            if ($model && $model->updated_at && $model->updated_at->gt($incomingTime)) {
                $conflicts[$table][] = $model->toArray();
                continue;
            }

            if ($change['op'] === 'delete') {
                if ($model) {
                    $model->timestamps = false;
                    $model->updated_at = $incomingTime;
                    if ($withDeleted && in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses($modelClass))) {
                        $model->deleted_at = $incomingTime;
                    }
                    $model->save();
                    $model->timestamps = true;
                    $applied[$table][] = $id;
                }
                continue;
            }

            $payload = $change['data'] ?? [];
            $filtered = array_intersect_key($payload, array_flip($fillable));
            if ($hashPassword && isset($filtered['password'])) {
                $filtered['password'] = bcrypt($filtered['password']);
            }

            if (!$model) {
                $model = new $modelClass();
                $model->id = $id;
            }

            $model->timestamps = false;
            $model->fill($filtered);
            $model->updated_at = $incomingTime;
            if (isset($payload['created_at'])) {
                $model->created_at = Carbon::parse($payload['created_at']);
            }
            if ($withDeleted && in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses($modelClass))) {
                $model->deleted_at = null;
            }
            $model->save();
            $model->timestamps = true;

            $applied[$table][] = $model->id;
        }
    }

    private function applySaleItems(array $changes, array &$applied, array &$conflicts): void
    {
        foreach ($changes as $change) {
            $incomingTime = Carbon::parse($change['updated_at']);
            $id = $change['id'] ?? (string) Str::orderedUuid();

            $existing = SaleItem::withTrashed()->lockForUpdate()->find($id);
            if ($existing && $existing->updated_at && $existing->updated_at->gt($incomingTime)) {
                $conflicts['sale_items'][] = $existing->toArray();
                continue;
            }

            if ($change['op'] === 'delete') {
                if ($existing) {
                    $existing->timestamps = false;
                    $existing->updated_at = $incomingTime;
                    $existing->deleted_at = $incomingTime;
                    $existing->save();
                    $existing->timestamps = true;
                    $applied['sale_items'][] = $id;
                }
                continue;
            }

            $data = $change['data'] ?? [];
            $productId = $data['product_id'] ?? $existing?->product_id;
            $qty = $data['qty'] ?? $existing?->qty ?? 0;

            $previousQty = ($existing && !$existing->trashed()) ? $existing->qty : 0;
            $delta = $qty - $previousQty;

            /** @var Product $product */
            $product = Product::lockForUpdate()->find($productId);
            if ($product && $delta > 0 && $product->stock < $delta) {
                $conflicts['sale_items'][] = [
                    'id' => $id,
                    'message' => "Stok produk {$product->name} tidak cukup",
                ];
                continue;
            }

            if ($product && $delta !== 0) {
                $product->decrement('stock', $delta);
            }

            $payload = [
                'id' => $id,
                'sale_id' => $data['sale_id'] ?? $existing?->sale_id,
                'product_id' => $productId,
                'qty' => $qty,
                'price' => $data['price'] ?? $existing?->price ?? 0,
            ];

            $model = $existing ?? new SaleItem();
            $model->timestamps = false;
            $model->fill($payload);
            $model->updated_at = $incomingTime;
            $model->deleted_at = null;
            if (isset($data['created_at'])) {
                $model->created_at = Carbon::parse($data['created_at']);
            }
            $model->save();
            $model->timestamps = true;

            $applied['sale_items'][] = $id;
        }
    }
}
