<?php

namespace Tests\Feature;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Shared\Enums\ExperienceLevel;
use App\Shared\Enums\FitnessGoal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EquipmentSeeder']);
    }

    // Trainer Profile Tests

    public function test_authenticated_user_can_create_trainer_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainer', [
            'display_name' => 'John Trainer',
            'bio' => 'Certified personal trainer with 5 years experience',
            'specializations' => ['Strength Training', 'Weight Loss'],
            'certifications' => ['NASM-CPT', 'ACE'],
            'years_experience' => 5,
            'hourly_rate_cents' => 5000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'display_name',
                    'slug',
                    'bio',
                    'specializations',
                    'certifications',
                    'years_experience',
                    'hourly_rate',
                    'status',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('trainer_profiles', [
            'user_id' => $user->id,
            'display_name' => 'John Trainer',
        ]);
    }

    public function test_user_cannot_create_duplicate_trainer_profile(): void
    {
        $user = User::factory()->create();
        TrainerProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainer', [
            'display_name' => 'Another Profile',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'You already have a trainer profile']);
    }

    public function test_trainer_profile_requires_display_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainer', [
            'bio' => 'Test bio',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['display_name']);
    }

    public function test_authenticated_user_can_get_their_trainer_profile(): void
    {
        $user = User::factory()->create();
        $profile = TrainerProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/profiles/trainer');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $profile->id,
                    'display_name' => $profile->display_name,
                ],
            ]);
    }

    public function test_user_without_trainer_profile_gets_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/profiles/trainer');

        $response->assertStatus(404)
            ->assertJson(['message' => 'You do not have a trainer profile']);
    }

    public function test_authenticated_user_can_update_their_trainer_profile(): void
    {
        $user = User::factory()->create();
        $profile = TrainerProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/profiles/trainer', [
            'display_name' => 'New Name',
            'bio' => 'Updated bio',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('trainer_profiles', [
            'id' => $profile->id,
            'display_name' => 'New Name',
            'bio' => 'Updated bio',
        ]);
    }

    public function test_authenticated_user_can_delete_their_trainer_profile(): void
    {
        $user = User::factory()->create();
        $profile = TrainerProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/profiles/trainer');

        $response->assertStatus(204);

        $this->assertDatabaseMissing('trainer_profiles', [
            'id' => $profile->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_trainer_profile(): void
    {
        $response = $this->postJson('/api/v1/profiles/trainer', [
            'display_name' => 'Test Trainer',
        ]);

        $response->assertStatus(401);
    }

    // Trainee Profile Tests

    public function test_authenticated_user_can_create_trainee_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Jane Trainee',
            'date_of_birth' => '1995-05-15',
            'gender' => 'female',
            'height_cm' => 165,
            'weight_kg' => 60,
            'fitness_goal' => FitnessGoal::WEIGHT_LOSS->value,
            'experience_level' => ExperienceLevel::BEGINNER->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'display_name',
                    'date_of_birth',
                    'age',
                    'gender',
                    'height_cm',
                    'weight_kg',
                    'bmi',
                    'fitness_goal',
                    'experience_level',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('trainee_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Jane Trainee',
        ]);
    }

    public function test_user_cannot_create_duplicate_trainee_profile(): void
    {
        $user = User::factory()->create();
        TraineeProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Another Profile',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'You already have a trainee profile']);
    }

    public function test_trainee_profile_requires_display_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'height_cm' => 170,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['display_name']);
    }

    public function test_trainee_profile_validates_fitness_goal_enum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Test',
            'fitness_goal' => 'invalid_goal',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fitness_goal']);
    }

    public function test_trainee_profile_validates_experience_level_enum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Test',
            'experience_level' => 'invalid_level',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['experience_level']);
    }

    public function test_trainee_profile_validates_height_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Test',
            'height_cm' => 400, // Too tall
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['height_cm']);
    }

    public function test_trainee_profile_validates_weight_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Test',
            'weight_kg' => 10, // Too light
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weight_kg']);
    }

    public function test_authenticated_user_can_get_their_trainee_profile(): void
    {
        $user = User::factory()->create();
        $profile = TraineeProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/profiles/trainee');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $profile->id,
                    'display_name' => $profile->display_name,
                ],
            ]);
    }

    public function test_user_without_trainee_profile_gets_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/profiles/trainee');

        $response->assertStatus(404)
            ->assertJson(['message' => 'You do not have a trainee profile']);
    }

    public function test_authenticated_user_can_update_their_trainee_profile(): void
    {
        $user = User::factory()->create();
        $profile = TraineeProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Old Name',
            'weight_kg' => 70,
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/profiles/trainee', [
            'display_name' => 'New Name',
            'weight_kg' => 75,
            'fitness_goal' => FitnessGoal::HYPERTROPHY->value,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('trainee_profiles', [
            'id' => $profile->id,
            'display_name' => 'New Name',
            'weight_kg' => 75,
        ]);
    }

    public function test_authenticated_user_can_delete_their_trainee_profile(): void
    {
        $user = User::factory()->create();
        $profile = TraineeProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson('/api/v1/profiles/trainee');

        $response->assertStatus(204);

        $this->assertDatabaseMissing('trainee_profiles', [
            'id' => $profile->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_trainee_profile(): void
    {
        $response = $this->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'Test Trainee',
        ]);

        $response->assertStatus(401);
    }

    // Multi-Role Tests

    public function test_user_can_have_both_trainer_and_trainee_profiles(): void
    {
        $user = User::factory()->create();

        // Create trainer profile
        $trainerResponse = $this->actingAs($user)->postJson('/api/v1/profiles/trainer', [
            'display_name' => 'John as Trainer',
        ]);
        $trainerResponse->assertStatus(201);

        // Create trainee profile
        $traineeResponse = $this->actingAs($user)->postJson('/api/v1/profiles/trainee', [
            'display_name' => 'John as Trainee',
        ]);
        $traineeResponse->assertStatus(201);

        // Verify both exist
        $this->assertDatabaseHas('trainer_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseHas('trainee_profiles', ['user_id' => $user->id]);
    }
}
