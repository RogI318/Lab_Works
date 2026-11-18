<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

// LAB 6: add "use Auditable;" to a model and every create / update / delete / restore is logged.
trait Auditable
{
    // Columns that change on their own and are not worth a log row
    protected static array $auditIgnore = ['updated_at', 'created_at', 'remember_token'];

    public static function bootAuditable(): void
    {
        // Seeders and artisan commands are not user actions
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        static::created(function ($model) {
            AuditLog::record('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = array_diff_key($model->getChanges(), array_flip(static::$auditIgnore));

            // Restoring from the Trash is logged by the "restored" event below
            if (array_keys($changes) === ['deleted_at']) {
                return;
            }

            if (! $changes) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $changes);
            AuditLog::record('updated', $model, $old, $changes);
        });

        static::deleted(function ($model) {
            AuditLog::record('deleted', $model, $model->getOriginal());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                AuditLog::record('restored', $model);
            });
        }
    }
}
