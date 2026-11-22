<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    /**
     * Admins and managers create, edit and delete workouts of all trainers.
     */
    public function manage(User $user): bool
    {
        return in_array($user->role?->name, ['admin', 'manager'], true);
    }

    /**
     * Determine whether the user can view the workout and its records.
     */
    public function view(User $user, Workout $workout): bool
    {
        return $this->manage($user) || $workout->trainer_id === $user->id;
    }
}
