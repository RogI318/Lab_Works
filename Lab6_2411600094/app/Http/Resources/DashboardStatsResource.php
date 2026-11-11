<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Answer to the docx analysis question on JSON structure: every endpoint
 * returns { data, meta } — the Resource envelope gives "data" for free, and
 * the poller reads "meta.last_updated" for its timestamp. Flat, snake_case
 * keys keep the JS dumb.
 */
class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_products'    => (int) $this['total_products'],
            'total_units'       => (int) $this['total_units'],
            'low_stock_count'   => (int) $this['low_stock_count'],
            'out_of_stock_count'=> (int) $this['out_of_stock_count'],
            'inventory_value'   => (float) $this['inventory_value'],
            'movements_today'   => (int) $this['movements_today'],
            'unread_alerts'     => (int) $this['unread_alerts'],
            'generated_at'      => $this['generated_at'],
        ];
    }

    public function with(Request $request): array
    {
        return ['meta' => ['last_updated' => $this['generated_at']]];
    }
}
