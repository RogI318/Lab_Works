<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// LAB 6: one row of the audit trail. Rows are only ever added, never edited or deleted by the app.
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    // Values that must never be written to the log
    public const HIDDEN = ['password', 'remember_token', 'pending_password'];

    protected $fillable = [
        'user_id', 'user_name', 'action', 'auditable_type', 'auditable_id',
        'label', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Writes one audit row. Never lets a logging problem break the real action.
    public static function record(string $action, ?Model $model = null, ?array $old = null, ?array $new = null, ?string $label = null, ?int $userId = null, ?string $userName = null): void
    {
        try {
            $user = auth()->user();

            static::create([
                'user_id' => $userId ?? $user?->id,
                'user_name' => $userName ?? $user?->name,
                'action' => $action,
                'auditable_type' => $model ? class_basename($model) : ($action === 'viewed' ? 'Page' : null),
                'auditable_id' => $model?->getKey(),
                'label' => $label ?? ($model ? static::labelFor($model) : null),
                'old_values' => $old ? static::clean($old) : null,
                'new_values' => $new ? static::clean($new) : null,
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function labelFor(Model $model): string
    {
        if (method_exists($model, 'auditLabel')) {
            return (string) $model->auditLabel();
        }

        return (string) ($model->name ?? $model->email ?? ('#'.$model->getKey()));
    }

    // Hides secrets and turns values into text the log page can show
    public static function clean(array $values): array
    {
        $out = [];

        foreach ($values as $key => $value) {
            $out[$key] = in_array($key, self::HIDDEN, true) ? '[hidden]' : (is_scalar($value) || $value === null ? $value : json_encode($value));
        }

        return $out;
    }

    public function getTypeNameAttribute(): string
    {
        return $this->auditable_type ?? '-';
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            'restored' => 'info',
            'viewed' => 'light text-dark border',
            'exported' => 'dark',
            'notification_read' => 'secondary',
            'login' => 'secondary',
            'logout' => 'secondary',
            'login_failed' => 'warning',
            default => 'secondary',
        };
    }
}
