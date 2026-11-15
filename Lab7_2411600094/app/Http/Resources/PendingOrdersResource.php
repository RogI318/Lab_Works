<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lab 7 Part 5 Step 4: the pending-approvals counters.
 *
 * Not a purchase order and not a supplier — one cached aggregate row — so it
 * gets its own Resource rather than being squeezed into PurchaseOrderResource
 * and making every other caller guess which keys exist.
 */
class PendingOrdersResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'count'        => (int) $this['count'],
            'value'        => (float) $this['value'],
            'generated_at' => $this['generated_at'],
        ];
    }
}
