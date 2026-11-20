<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\Auditable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pending_password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Lab 6: minimal role flag (migration 2026_11_11_001959)
            'is_admin' => 'boolean',
            'pending_changes_at' => 'datetime',
        ];
    }

    /** Users who may delete transactions and receive low-stock alerts. */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('role', 'admin')->orWhere('is_admin', true); });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || (bool) $this->is_admin;
    }
    public function isStaff(): bool
    {
        return $this->role === 'staff' && !$this->isAdmin();
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function canManageStock(): bool
    {
        return $this->isAdmin() || $this->isStaff() || $this->isManager();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function approvedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'approved_by');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'created_by');
    }
    public function hasPendingChanges(): bool
    {
        return $this->pending_email !== null || $this->pending_password !== null;
    }
    public function pendingChangesToken(): string
    {
        return hash_hmac('sha256', $this->pending_email . '|' . $this->pending_password, config('app.key'));
    }
}
