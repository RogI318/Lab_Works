<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    public static function record(string $action, ?Model $model = null, ?array $old = null, ?array $new = null, ?string $label = null): void
    {
        try {
            $u = auth()->user();
            static::create(['user_id' => $u?->id, 'user_name' => $u?->name, 'action' => $action, 'auditable_type' => $model ? class_basename($model) : ($action === 'viewed' ? 'Page' : null), 'auditable_id' => $model?->getKey(), 'label' => $label ?? ($model?->name ?? $model?->email), 'old_values' => static::clean($old), 'new_values' => static::clean($new), 'ip_address' => request()?->ip(), 'user_agent' => substr((string) request()?->userAgent(), 0, 255)]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
    private static function clean(?array $values): ?array
    {
        if (!$values)
            return null;
        foreach (['password', 'pending_password', 'remember_token'] as $k)
            unset($values[$k]);
        return $values;
    }
}
