<?php

namespace App\Policies;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\User;

class TraineeProfilePolicy
{
    /**
     * Determine if the user can view the trainee profile.
     */
    public function view(User $user, TraineeProfile $traineeProfile): bool
    {
        // Only the owner and their trainers can view
        return $user->id === $traineeProfile->user_id;
    }

    /**
     * Determine if the user can create a trainee profile.
     */
    public function create(User $user): bool
    {
        // Users can only have one trainee profile
        return !$user->traineeProfile()->exists();
    }

    /**
     * Determine if the user can update the trainee profile.
     */
    public function update(User $user, TraineeProfile $traineeProfile): bool
    {
        // Only the owner can update their profile
        return $user->id === $traineeProfile->user_id;
    }

    /**
     * Determine if the user can delete the trainee profile.
     */
    public function delete(User $user, TraineeProfile $traineeProfile): bool
    {
        // Only the owner can delete their profile
        return $user->id === $traineeProfile->user_id;
    }
}
