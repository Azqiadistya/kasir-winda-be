<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Expense;
use App\Models\StoreSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $owner = User::create([
            'id' => (string) Str::orderedUuid(),
            'name' => 'Owner',
            'username' => 'owner',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        $cashier = User::create([
            'id' => (string) Str::orderedUuid(),
            'name' => 'Kasir',
            'username' => 'kasir',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        $categories = collect([
            'Minuman',
            'Makanan',
            'Snack',
        ])->mapWithKeys(fn ($name) => [$name => Category::create([
            'id' => (string) Str::orderedUuid(),
            'name' => $name,
        ])]);

        $products = [
            [
                'id' => (string) Str::orderedUuid(),
                'category_id' => $categories['Minuman']->id,
                'name' => 'Kopi Hitam',
                'barcode' => 'BRCD001',
                'price' => 10000,
                'cost' => 7000,
                'stock' => 50,
            ],
            [
                'id' => (string) Str::orderedUuid(),
                'category_id' => $categories['Minuman']->id,
                'name' => 'Teh Botol',
                'barcode' => 'BRCD002',
                'price' => 5000,
                'cost' => 3000,
                'stock' => 100,
            ],
            [
                'id' => (string) Str::orderedUuid(),
                'category_id' => $categories['Makanan']->id,
                'name' => 'Nasi Goreng',
                'barcode' => 'BRCD003',
                'price' => 20000,
                'cost' => 12000,
                'stock' => 20,
            ],
            [
                'id' => (string) Str::orderedUuid(),
                'category_id' => $categories['Snack']->id,
                'name' => 'Keripik Kentang',
                'barcode' => 'BRCD004',
                'price' => 15000,
                'cost' => 9000,
                'stock' => 40,
            ],
        ];

        Product::insert($products);

        $sale = Sale::create([
            'id' => (string) Str::orderedUuid(),
            'paid' => 30000,
            'total' => 25000,
            'change' => 5000,
            'cashier' => $cashier->name,
        ]);

        SaleItem::create([
            'id' => (string) Str::orderedUuid(),
            'sale_id' => $sale->id,
            'product_id' => $products[0]['id'],
            'qty' => 1,
            'price' => $products[0]['price'],
        ]);

        SaleItem::create([
            'id' => (string) Str::orderedUuid(),
            'sale_id' => $sale->id,
            'product_id' => $products[1]['id'],
            'qty' => 3,
            'price' => $products[1]['price'],
        ]);

        Expense::create([
            'id' => (string) Str::orderedUuid(),
            'title' => 'Biaya Listrik',
            'amount' => 150000,
        ]);

        StoreSetting::create([
            'id' => (string) Str::orderedUuid(),
            'name' => 'Kasir Winda',
            'address' => 'Jl. Contoh No.1',
            'postal' => '12345',
            'phone' => '081234567890',
            'cashier_name' => 'Winda',
        ]);
    }
}
