<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Product shape for the low-stock API (Part 7). */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'sku'               => $this->sku,
            'quantity'          => (int) $this->quantity,
            'reorder_level'     => (int) $this->reorder_level,
            'stock_status'      => $this->stock_status,
            'stock_status_color'=> $this->stock_status_color,
            'category'          => $this->whenLoaded('category', fn () => $this->category?->name),
            'url'               => route('products.show', $this),
        ];
    }
}
