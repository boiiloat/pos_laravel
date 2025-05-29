<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_date' => $this->created_date,
            'created_by' => $this->creator ? $this->creator->fullname : null,
            'deleted_date' => $this->deleted_date,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'products' => $this->whenLoaded('products', function () {
                return $this->products->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'image' => $product->image,
                        'category_id' => $product->category_id,
                        'created_date' => $product->created_date,
                        'created_by' => $product->creator ? $product->creator->fullname : null,
                        'deleted_date' => $product->deleted_date,
                        'deleted_by' => $product->deleted_by,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'creator_name' => $product->creator ? $product->creator->fullname : null,
                        'category_name' => $product->category ? $product->category->name : null,
                        'creator' => $product->creator,
                        'category' => $product->category
                    ];
                });
            })
        ];
    }
}