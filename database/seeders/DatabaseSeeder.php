<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['admin', 'manager', 'staff', 'customer'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('123456'),
                'type' => 'admin',
                'status' => 'active',
            ],
        );
        $admin->assignRole('admin');

        $category = Category::firstOrCreate(
            ['slug' => 'classic-tea'],
            ['name' => 'Classic Milk Tea', 'sort_order' => 1],
        );

        Product::firstOrCreate(
            ['slug' => 'pearl-milk-tea'],
            [
                'category_id' => $category->id,
                'name' => 'bubble tea',
                'base_price' => 12.00,
                'stock' => 999,
                'status' => 'active',
                'sort_order' => 1,
            ],
        );
    }
}
