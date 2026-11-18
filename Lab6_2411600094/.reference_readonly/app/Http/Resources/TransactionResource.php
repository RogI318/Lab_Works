<?php
// LAB 6, PART 7: Inventory transaction as JSON
// Created by: php artisan make:resource TransactionResource
// app/Http/Resources/TransactionResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'color' => $this->type_color,
            'quantity' => $this->signed_quantity,
            'reference' => $this->reference,
            'product' => $this->product?->name,
            'user' => $this->user?->name,
            'created_at' => $this->created_at->format('M d, h:i A'),
        ];
    }
}
