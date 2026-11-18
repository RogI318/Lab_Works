<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryOrderItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'unit_price'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
