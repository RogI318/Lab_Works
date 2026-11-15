<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Lab 7 Part 5 Step 4: one short item as the reorder widget draws it. */
class ReorderAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => (int) $this['id'],
            'sku'       => $this['sku'],
            'name'      => $this['name'],
            'url'       => $this['url'],
            'on_hand'   => (int) $this['on_hand'],
            'point'     => (int) $this['point'],
            'suggested' => (int) $this['suggested'],
            'on_order'  => (int) $this['on_order'],
            'supplier'  => $this['supplier'],
        ];
    }
}
