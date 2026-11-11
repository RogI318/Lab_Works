<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Transaction shape for the recent-movements API (Part 7). */
class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'created_at'       => $this->created_at->toIso8601String(),
            'created_for_humans' => $this->created_at->diffForHumans(),
            'type'             => $this->type,
            'type_label'       => $this->typeLabel(),
            'badge_class'      => $this->typeBadgeClass(),
            'quantity_signed'  => $this->signedQuantity(),
            'reference_number' => $this->reference_number,
            'product'          => $this->whenLoaded('product', fn () => [
                'id'   => $this->product?->id,
                'name' => $this->product?->name,
                'url'  => $this->product ? route('products.show', $this->product) : null,
            ]),
            'user'             => $this->whenLoaded('user', fn () => ['name' => $this->user?->name]),
            'url'              => route('transactions.show', $this),
        ];
    }
}
