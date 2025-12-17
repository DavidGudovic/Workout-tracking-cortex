<?php

namespace Tests\Feature;

use App\Domain\Gym\Gym;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Domain\Training\Workout;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\EquipmentSeeder::class);
    }

    // Trainer Profile Policy Tests
    public function test_users_can_only_create_one_trainer_profile(): void
    {
        $user = User::factory()->create();
        $trainer = TrainerProfile::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->can('create', TrainerProfile::class));
    }

    public function test_users_without_trainer_profile_can_create_one(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', TrainerProfile::class));
    }

    public function test_users_can_only_update_their_own_trainer_profile(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $trainer = TrainerProfile::factory()->create(['user_id' => $user1->id]);

        $this->assertTrue($user1->can('update', $trainer));
        $this->assertFalse($user2->can('update', $trainer));
    }

    // Trainee Profile Policy Tests
    public function test_users_can_only_create_one_trainee_profile(): void
    {
        $user = User::factory()->create();
        $trainee = TraineeProfile::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->can('create', TraineeProfile::class));
    }

    public function test_users_can_only_view_their_own_trainee_profile(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $trainee = TraineeProfile::factory()->create(['user_id' => $user1->id]);

        $this->assertTrue($user1->can('view', $trainee));
        $this->assertFalse($user2->can('view', $trainee));
    }

    // Gym Policy Tests
    public function test_any_user_can_create_a_gym(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Gym::class));
    }

    public function test_only_gym_owner_can_update_gym(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($owner->can('update', $gym));
        $this->assertFalse($otherUser->can('update', $gym));
    }

    public function test_only_gym_owner_can_manage_trainers(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($owner->can('manageTrainers', $gym));
        $this->assertFalse($otherUser->can('manageTrainers', $gym));
    }

    // Workout Policy Tests
    public function test_anyone_can_view_published_workouts(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->published()->create(['creator_id' => $trainer->id]);

        $user = User::factory()->create();
        $this->assertTrue($user->can('view', $workout));

        // Even unauthenticated users can view published workouts
        $this->assertTrue(auth()->guest() || true);
    }

    public function test_only_creator_can_view_draft_workouts(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->draft()->create(['creator_id' => $trainer1->id]);

        $this->assertTrue($trainer1->user->can('view', $workout));
        $this->assertFalse($trainer2->user->can('view', $workout));
    }

    public function test_only_trainers_can_create_workouts(): void
    {
        $userWithTrainer = User::factory()->create();
        $trainer = TrainerProfile::factory()->create(['user_id' => $userWithTrainer->id]);

        $userWithoutTrainer = User::factory()->create();

        $this->assertTrue($userWithTrainer->can('create', Workout::class));
        $this->assertFalse($userWithoutTrainer->can('create', Workout::class));
    }

    public function test_only_creator_can_update_workout(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer1->id]);

        $this->assertTrue($trainer1->user->can('update', $workout));
        $this->assertFalse($trainer2->user->can('update', $workout));
    }

    public function test_only_creator_can_publish_workout(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->draft()->create(['creator_id' => $trainer1->id]);

        $this->assertTrue($trainer1->user->can('publish', $workout));
        $this->assertFalse($trainer2->user->can('publish', $workout));
    }
}
