<?php

namespace App\Policies;

use App\Models\AcademicIntervention;
use App\Models\User;

class AcademicInterventionPolicy
{
    // Only administrators can view the intervention list.
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    // Only administrators can view intervention details.
    public function view(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin;
    }

    // Only administrators can create interventions.
    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    // Only administrators can edit pending interventions.
    public function update(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin
            && $intervention->status === 'pending';
    }

    // Only administrators can delete pending interventions.
    public function delete(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin
            && $intervention->status === 'pending';
    }

    // Only administrators can approve pending interventions.
    public function approve(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin
            && $intervention->status === 'pending';
    }

    // Only administrators can reject pending interventions.
    public function reject(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin
            && $intervention->status === 'pending';
    }

    // Only administrators can complete approved interventions.
    public function complete(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin
            && in_array($intervention->status, [
                'approved',
                'in_progress'
            ]);
    }

    // Only administrators can restore deleted records.
    public function restore(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin;
    }

    // Only administrators can permanently delete records.
    public function forceDelete(
        User $user,
        AcademicIntervention $intervention
    ): bool {
        return (bool) $user->is_admin;
    }
}
