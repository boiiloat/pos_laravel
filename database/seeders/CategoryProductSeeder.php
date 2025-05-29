<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        
        $categories = Category::factory(5)->create([
            'created_by' => $user->id
        ]);

        $categories->each(function ($category) use ($user) {
            Product::factory(10)->create([
                'category_id' => $category->id,
                'created_by' => $user->id
            ]);
        });
    }
}