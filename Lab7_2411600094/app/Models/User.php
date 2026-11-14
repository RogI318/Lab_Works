<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /* ---------- Lab 7 Part 1 Step 2: the four roles ---------- */
    public const ROLE_ADMIN     = 'admin';
    public const ROLE_MANAGER   = 'manager';
    public const ROLE_WAREHOUSE = 'warehouse';
    public const ROLE_STAFF     = 'staff';

    /** Approved values, in the order the Users dropdown lists them. */
    public const ROLES = [
        self::ROLE_ADMIN     => 'Administrator',
        self::ROLE_MANAGER   => 'Manager',
        self::ROLE_WAREHOUSE => 'Warehouse Staff',
        self::ROLE_STAFF     => 'Staff',
    ];

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
            // Lab 7 Part 1: fine-grained role (migration 2026_11_15_100005)
            'role' => 'string',
        ];
    }

    /**
     * Lab 7 Part 1 Step 2: `is_admin` is what every Lab 5/6 check still reads
     * (AdminOnly middleware, the bell, restore, syncLowStockAlerts), so the two
     * columns must never disagree. Whichever one the caller touched wins, and
     * the other follows — a role change updates the flag and the old checkbox
     * keeps working during the transition.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            if ($user->isDirty('role')) {
                $user->is_admin = $user->role === self::ROLE_ADMIN;
            } elseif ($user->isDirty('is_admin')) {
                $user->role = $user->is_admin ? self::ROLE_ADMIN : self::ROLE_STAFF;
            } elseif ($user->exists === false && $user->role === null) {
                $user->role = $user->is_admin ? self::ROLE_ADMIN : self::ROLE_STAFF;
            }
        });
    }

    /* ---------- Role helpers ---------- */

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role ?: ($this->is_admin ? self::ROLE_ADMIN : self::ROLE_STAFF), $roles, true);
    }

    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    public function isWarehouse(): bool
    {
        return $this->hasRole(self::ROLE_WAREHOUSE);
    }

    /** "Administrator", "Warehouse Staff", … for the tables and badges. */
    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    public function roleBadgeClass(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN     => 'bg-primary',
            self::ROLE_MANAGER   => 'bg-info text-dark',
            self::ROLE_WAREHOUSE => 'success',
            default              => 'bg-secondary',
        };
    }

    /** Users who may delete transactions and receive low-stock alerts. */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('is_admin', true);
    }

    /** Lab 7: everyone holding one of the listed roles. */
    public function scopeWithRoles(Builder $query, array $roles): Builder
    {
        return $query->whereIn('role', $roles);
    }
}
