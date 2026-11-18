<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'pending_changes_at' => 'datetime',
        ];
    }

    // An email/password change an admin made that the user has not confirmed yet
    public function hasPendingChanges(): bool
    {
        return $this->pending_email !== null || $this->pending_password !== null;
    }

    // Fingerprint of the pending change; a confirmation link only works while it matches
    public function pendingChangesToken(): string
    {
        return hash_hmac('sha256', $this->pending_email.'|'.$this->pending_password, config('app.key'));
    }

    // Only admins can manage users
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    // LAB 6: admin and staff can record Stock In / Stock Out and manage the inventory
    public function canManageStock(): bool
    {
        return $this->isAdmin() || $this->isStaff();
    }
}
