<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_person', 'email', 'phone', 'lead_time_days', 'address', 'notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'lead_time_days' => 'integer'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
