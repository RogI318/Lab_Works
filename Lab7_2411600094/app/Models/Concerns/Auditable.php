<?php
namespace App\Models\Concerns;
use App\Models\AuditLog;
trait Auditable
{
    public static function bootAuditable(): void
    {
        if (app()->runningInConsole() && !app()->runningUnitTests())
            return;
        static::created(fn($m) => AuditLog::record('created', $m, null, $m->getAttributes()));
        static::updated(function ($m) {
            $changes = $m->getChanges();
            foreach (['created_at', 'updated_at', 'remember_token', 'password', 'pending_password'] as $k)
                unset($changes[$k]);
            if ($changes)
                AuditLog::record('updated', $m, $m->getOriginal(), $changes); });
        static::deleted(fn($m) => AuditLog::record('deleted', $m, $m->getOriginal()));
        if (method_exists(static::class, 'restored'))
            static::restored(fn($m) => AuditLog::record('restored', $m));
    }
}
