<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lab 7 Part 5 Step 4: one purchase order as the recent-orders widget draws it.
 *
 * The keys are the columns the card prints — nothing else is shipped, which is
 * also why ProcurementMetricsService::recentOrders() selects only these.
 */
class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => (int) $this['id'],
            'reference'          => $this['reference'],
            'url'                => $this['url'],
            'supplier'           => $this['supplier'],
            'status'             => $this['status'],
            'status_label'       => $this['status_label'],
            'badge_class'        => $this['badge_class'],
            'total'              => (float) $this['total'],
            'order_date'         => $this['order_date'],
            'is_auto_generated'  => (bool) $this['auto'],
        ];
    }
}
