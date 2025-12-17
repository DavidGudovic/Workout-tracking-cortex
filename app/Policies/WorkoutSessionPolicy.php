<?php

namespace App\Policies;

use App\Domain\Execution\WorkoutSession;
use App\Domain\Identity\User;

class WorkoutSessionPolicy
{
    /**
     * Determine if the user can view the workout session.
     */
    public function view(User $user, WorkoutSession $workoutSession): bool
    {
        // Only the trainee who owns the session can view it
        $trainee = $user->traineeProfile;
        return $trainee && $trainee->id === $workoutSession->trainee_id;
    }

    /**
     * Determine if the user can create a workout session.
     */
    public function create(User $user): bool
    {
        // User must have a trainee profile to create sessions
        return $user->traineeProfile()->exists();
    }

    /**
     * Determine if the user can update the workout session.
     */
    public function update(User $user, WorkoutSession $workoutSession): bool
    {
        // Only the trainee who owns the session can update it
        $trainee = $user->traineeProfile;
        return $trainee && $trainee->id === $workoutSession->trainee_id;
    }

    /**
     * Determine if the user can delete the workout session.
     */
    public function delete(User $user, WorkoutSession $workoutSession): bool
    {
        // Only the trainee who owns the session can delete it
        $trainee = $user->traineeProfile;
        return $trainee && $trainee->id === $workoutSession->trainee_id;
    }
}
