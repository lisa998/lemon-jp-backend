<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LingerieProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'product_code' => $this->product_code,
            'description' => $this->description,
            'detail' => $this->detail,
            'skus' => $this->skus->map(function ($sku) {
                return [
                    'id' => $sku->id,
                    'size' => $sku->size?->size,
                    'color' => $sku->color?->color,
                    'price' => $sku->price,
                    'stock_quantity' => $sku->stock_quantity,
                ];
            }),
        ];
    }
}
