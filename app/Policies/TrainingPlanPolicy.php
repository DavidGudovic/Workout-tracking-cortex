<?php

namespace App\Policies;

use App\Domain\Training\TrainingPlan;
use App\Domain\Identity\User;
use App\Shared\Enums\WorkoutStatus;

class TrainingPlanPolicy
{
    /**
     * Determine if the user can view the training plan.
     */
    public function view(?User $user, TrainingPlan $trainingPlan): bool
    {
        // Anyone can view published plans
        if ($trainingPlan->status === WorkoutStatus::PUBLISHED) {
            return true;
        }

        // Only creator can view draft/archived plans
        if (!$user) {
            return false;
        }

        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $trainingPlan->creator_id;
    }

    /**
     * Determine if the user can create a training plan.
     */
    public function create(User $user): bool
    {
        // User must have a trainer profile to create plans
        return $user->trainerProfile()->exists();
    }

    /**
     * Determine if the user can update the training plan.
     */
    public function update(User $user, TrainingPlan $trainingPlan): bool
    {
        // Only the creator can update
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $trainingPlan->creator_id;
    }

    /**
     * Determine if the user can delete the training plan.
     */
    public function delete(User $user, TrainingPlan $trainingPlan): bool
    {
        // Only the creator can delete
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $trainingPlan->creator_id;
    }

    /**
     * Determine if the user can publish the training plan.
     */
    public function publish(User $user, TrainingPlan $trainingPlan): bool
    {
        // Only the creator can publish
        $trainer = $user->trainerProfile;
        return $trainer && $trainer->id === $trainingPlan->creator_id;
    }
}
