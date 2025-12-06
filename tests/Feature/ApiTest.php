<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'owner'): User
    {
        return User::create([
            'id' => (string) Str::orderedUuid(),
            'name' => ucfirst($role),
            'username' => $role,
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }

    public function test_login_returns_token_and_user(): void
    {
        $user = $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'username', 'role']]]);
    }

    public function test_product_crud(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $category = Category::create([
            'id' => (string) Str::orderedUuid(),
            'name' => 'Kategori',
        ]);

        $create = $this->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'name' => 'Produk A',
            'barcode' => 'XX1',
            'price' => 10000,
            'cost' => 7000,
            'stock' => 10,
        ]);

        $create->assertCreated()->assertJsonStructure(['data' => ['id', 'name']]);
        $productId = $create['data']['id'];

        $update = $this->putJson("/api/v1/products/{$productId}", [
            'category_id' => $category->id,
            'name' => 'Produk B',
            'barcode' => 'XX1',
            'price' => 12000,
            'cost' => 8000,
            'stock' => 5,
        ]);
        $update->assertOk()->assertJsonPath('data.name', 'Produk B');

        $delete = $this->deleteJson("/api/v1/products/{$productId}");
        $delete->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_sale_reduces_stock(): void
    {
        $user = $this->makeUser('kasir');
        Sanctum::actingAs($user);

        $category = Category::create([
            'id' => (string) Str::orderedUuid(),
            'name' => 'Kategori',
        ]);

        $product = Product::create([
            'id' => (string) Str::orderedUuid(),
            'category_id' => $category->id,
            'name' => 'Produk',
            'price' => 10000,
            'cost' => 5000,
            'stock' => 5,
        ]);

        $response = $this->postJson('/api/v1/sales', [
            'paid' => 20000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'price' => 10000,
                ],
            ],
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['id', 'items']]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    }

    public function test_sync_push_and_pull(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $pull = $this->getJson('/api/v1/sync/pull');
        $pull->assertOk()->assertJsonStructure(['data' => ['categories', 'products'], 'meta' => ['serverTime']]);

        $categoryId = (string) Str::orderedUuid();
        $now = now()->toIso8601String();

        $push = $this->postJson('/api/v1/sync/push', [
            'clientTime' => $now,
            'changes' => [
                'categories' => [
                    [
                        'op' => 'upsert',
                        'id' => $categoryId,
                        'updated_at' => $now,
                        'data' => [
                            'id' => $categoryId,
                            'name' => 'Sinkron Baru',
                        ],
                    ],
                ],
                'products' => [],
                'sales' => [],
                'sale_items' => [],
                'expenses' => [],
                'store_settings' => [],
                'users' => [],
            ],
        ]);

        $push->assertOk()->assertJsonPath('data.applied.categories.0', $categoryId);
        $this->assertDatabaseHas('categories', ['id' => $categoryId, 'name' => 'Sinkron Baru']);
    }
}
