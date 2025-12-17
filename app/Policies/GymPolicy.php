<?php

namespace App\Policies;

use App\Domain\Gym\Gym;
use App\Domain\Identity\User;

class GymPolicy
{
    /**
     * Determine if the user can view the gym.
     */
    public function view(?User $user, Gym $gym): bool
    {
        // Anyone can view active gyms
        return true;
    }

    /**
     * Determine if the user can create a gym.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create a gym
        return true;
    }

    /**
     * Determine if the user can update the gym.
     */
    public function update(User $user, Gym $gym): bool
    {
        // Only the gym owner can update
        return $user->id === $gym->owner_id;
    }

    /**
     * Determine if the user can delete the gym.
     */
    public function delete(User $user, Gym $gym): bool
    {
        // Only the gym owner can delete
        return $user->id === $gym->owner_id;
    }

    /**
     * Determine if the user can manage trainers for the gym.
     */
    public function manageTrainers(User $user, Gym $gym): bool
    {
        // Only the gym owner can manage trainers
        return $user->id === $gym->owner_id;
    }

    /**
     * Determine if the user can manage equipment for the gym.
     */
    public function manageEquipment(User $user, Gym $gym): bool
    {
        // Only the gym owner can manage equipment
        return $user->id === $gym->owner_id;
    }
}
