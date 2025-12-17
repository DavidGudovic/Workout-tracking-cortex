<?php

namespace App\Policies;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;

class TrainerProfilePolicy
{
    /**
     * Determine if the user can view the trainer profile.
     */
    public function view(?User $user, TrainerProfile $trainerProfile): bool
    {
        // Anyone can view trainer profiles
        return true;
    }

    /**
     * Determine if the user can create a trainer profile.
     */
    public function create(User $user): bool
    {
        // Users can only have one trainer profile
        return !$user->trainerProfile()->exists();
    }

    /**
     * Determine if the user can update the trainer profile.
     */
    public function update(User $user, TrainerProfile $trainerProfile): bool
    {
        // Only the owner can update their profile
        return $user->id === $trainerProfile->user_id;
    }

    /**
     * Determine if the user can delete the trainer profile.
     */
    public function delete(User $user, TrainerProfile $trainerProfile): bool
    {
        // Only the owner can delete their profile
        return $user->id === $trainerProfile->user_id;
    }
}
