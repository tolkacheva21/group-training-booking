<?php

namespace App\Policies;

use App\Models\Record;
use App\Models\Status;
use App\Models\User;

class RecordPolicy
{
    /**
     * Admins and managers work with records of all users.
     */
    public function viewAll(User $user): bool
    {
        return in_array($user->role?->name, ['admin', 'manager'], true);
    }

    /**
     * Only visitors (the "user" role) book workouts for themselves.
     * Admins, managers and trainers never book as visitors.
     */
    public function book(User $user): bool
    {
        return $user->role?->name === 'user';
    }

    /**
     * Determine whether the user can view the record.
     */
    public function view(User $user, Record $record): bool
    {
        return $this->viewAll($user)
            || $record->user_id === $user->id
            || $record->workout->trainer_id === $user->id;
    }

    /**
     * Admins and managers confirm records that are not confirmed yet.
     */
    public function confirm(User $user, Record $record): bool
    {
        return $this->viewAll($user) && $record->hasStatus(Status::Pending);
    }

    /**
     * Admins and managers reject pending or confirmed records, for example when the workout is overbooked.
     */
    public function reject(User $user, Record $record): bool
    {
        return $this->viewAll($user)
            && ($record->hasStatus(Status::Pending) || $record->hasStatus(Status::Confirmed));
    }

    /**
     * Determine whether the user can cancel the record. Completed records stay in history.
     */
    public function delete(User $user, Record $record): bool
    {
        return ! $record->hasStatus(Status::Completed)
            && ($this->viewAll($user) || $record->user_id === $user->id);
    }

    /**
     * Determine whether the user can pay for the record (only its owner).
     */
    public function pay(User $user, Record $record): bool
    {
        return $record->user_id === $user->id;
    }
}
