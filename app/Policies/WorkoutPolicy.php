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
     * Trainers schedule and edit their own workouts.
     */
    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['admin', 'manager', 'trainer'], true);
    }

    /**
     * Determine whether the user can view the workout and its records.
     */
    public function view(User $user, Workout $workout): bool
    {
        return $this->manage($user) || $workout->trainer_id === $user->id;
    }

    /**
     * Determine whether the user can edit the workout.
     */
    public function update(User $user, Workout $workout): bool
    {
        return $this->manage($user) || $workout->trainer_id === $user->id;
    }

    /**
     * Determine whether the user can delete the workout.
     */
    public function delete(User $user, Workout $workout): bool
    {
        return $this->manage($user) || $workout->trainer_id === $user->id;
    }
}
