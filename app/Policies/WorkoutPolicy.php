<?php

namespace App\Policies;

use App\Domain\Training\Workout;
use App\Domain\Identity\User;
use App\Shared\Enums\WorkoutStatus;

class WorkoutPolicy
{
    /**
     * Determine if the user can view the workout.
     */
    public function view(?User $user, Workout $workout): bool
    {
        // Anyone can view published workouts
        if ($workout->status === WorkoutStatus::PUBLISHED) {
            return true;
        }

        // Only creator can view draft/archived workouts
        if (!$user) {
            return false;
        }

        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $workout->creator_id;
    }

    /**
     * Determine if the user can create a workout.
     */
    public function create(User $user): bool
    {
        // User must have a trainer profile to create workouts
        return $user->trainerProfile()->exists();
    }

    /**
     * Determine if the user can update the workout.
     */
    public function update(User $user, Workout $workout): bool
    {
        // Only the creator can update
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $workout->creator_id;
    }

    /**
     * Determine if the user can delete the workout.
     */
    public function delete(User $user, Workout $workout): bool
    {
        // Only the creator can delete
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $workout->creator_id;
    }

    /**
     * Determine if the user can publish the workout.
     */
    public function publish(User $user, Workout $workout): bool
    {
        // Only the creator can publish
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $workout->creator_id;
    }
}
