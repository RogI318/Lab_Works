<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryOrder extends Model
{
    protected $fillable = ['order_number', 'direction', 'contact_name', 'status', 'notes', 'user_id', 'completed_at', 'approved_by', 'approved_at'];
    protected $casts = ['completed_at' => 'datetime', 'approved_at' => 'datetime'];
    public function items(): HasMany
    {
        return $this->hasMany(InventoryOrderItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
