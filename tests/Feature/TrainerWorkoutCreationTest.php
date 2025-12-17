<?php

namespace Tests\Feature;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Domain\Training\Exercise;
use App\Domain\Training\Workout;
use App\Domain\Training\WorkoutExercise;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerWorkoutCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EquipmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'SystemExerciseSeeder']);
    }

    // Workout Creation Tests

    public function test_trainer_can_create_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'name' => 'Full Body Strength',
            'description' => 'A comprehensive full body workout',
            'difficulty' => Difficulty::INTERMEDIATE->value,
            'estimated_duration_minutes' => 60,
            'tags' => ['strength', 'full-body'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'difficulty',
                    'status',
                    'creator',
                ],
            ]);

        $this->assertDatabaseHas('workouts', [
            'name' => 'Full Body Strength',
            'creator_id' => $trainer->id,
            'status' => WorkoutStatus::DRAFT->value,
        ]);
    }

    public function test_non_trainer_cannot_create_workout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/trainer/workouts', [
            'name' => 'Test Workout',
        ]);

        $response->assertStatus(403);
    }

    public function test_workout_creation_requires_name(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'description' => 'Test description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_workout_name_has_max_length(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'name' => str_repeat('a', 201),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_workout_creation_validates_difficulty_enum(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'name' => 'Test Workout',
            'difficulty' => 'invalid_difficulty',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['difficulty']);
    }

    public function test_premium_workout_requires_price(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'name' => 'Premium Workout',
            'pricing_type' => PricingType::PREMIUM->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price_cents']);
    }

    public function test_trainer_can_create_premium_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/workouts', [
            'name' => 'Premium Workout',
            'pricing_type' => PricingType::PREMIUM->value,
            'price_cents' => 1999,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('workouts', [
            'name' => 'Premium Workout',
            'pricing_type' => PricingType::PREMIUM->value,
            'price_cents' => 1999,
        ]);
    }

    // Workout Update Tests

    public function test_trainer_can_update_their_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create([
            'creator_id' => $trainer->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($trainer->user)->patchJson("/api/v1/trainer/workouts/{$workout->id}", [
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'name' => 'New Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_trainer_cannot_update_another_trainers_workout(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer1->id]);

        $response = $this->actingAs($trainer2->user)->patchJson("/api/v1/trainer/workouts/{$workout->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    // Workout Deletion Tests

    public function test_trainer_can_delete_their_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->deleteJson("/api/v1/trainer/workouts/{$workout->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('workouts', [
            'id' => $workout->id,
        ]);
    }

    public function test_trainer_cannot_delete_another_trainers_workout(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer1->id]);

        $response = $this->actingAs($trainer2->user)->deleteJson("/api/v1/trainer/workouts/{$workout->id}");

        $response->assertStatus(403);
    }

    // Workout Listing Tests

    public function test_trainer_can_list_their_workouts(): void
    {
        $trainer = TrainerProfile::factory()->create();
        Workout::factory()->count(3)->create(['creator_id' => $trainer->id]);
        Workout::factory()->count(2)->create(); // Other trainers' workouts

        $response = $this->actingAs($trainer->user)->getJson('/api/v1/trainer/workouts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_trainer_can_view_their_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->getJson("/api/v1/trainer/workouts/{$workout->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $workout->id,
                    'name' => $workout->name,
                ],
            ]);
    }

    // Workout Status Tests

    public function test_trainer_can_publish_draft_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->draft()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/publish");

        $response->assertStatus(200);

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'status' => WorkoutStatus::PUBLISHED->value,
        ]);

        $workout->refresh();
        $this->assertNotNull($workout->published_at);
    }

    public function test_trainer_can_archive_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->published()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/archive");

        $response->assertStatus(200);

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'status' => WorkoutStatus::ARCHIVED->value,
        ]);
    }

    public function test_trainer_can_revert_to_draft(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->published()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/draft");

        $response->assertStatus(200);

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'status' => WorkoutStatus::DRAFT->value,
        ]);
    }

    // Workout Exercise Management Tests

    public function test_trainer_can_add_exercise_to_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);
        $exercise = Exercise::first();

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
            'sort_order' => 1,
            'sets' => 4,
            'target_reps' => 10,
            'rest_seconds' => 90,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('workout_exercises', [
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'sets' => 4,
            'target_reps' => 10,
        ]);
    }

    public function test_adding_exercise_updates_workout_totals(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create([
            'creator_id' => $trainer->id,
            'total_exercises' => 0,
            'total_sets' => 0,
        ]);
        $exercise = Exercise::first();

        $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
            'sort_order' => 1,
            'sets' => 3,
            'target_reps' => 12,
        ]);

        $workout->refresh();
        $this->assertEquals(1, $workout->total_exercises);
        $this->assertEquals(3, $workout->total_sets);
    }

    public function test_workout_exercise_requires_at_least_one_target(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);
        $exercise = Exercise::first();

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
            'sort_order' => 1,
            'sets' => 3,
            // No target_reps, target_duration_seconds, or target_distance_meters
        ]);

        $response->assertStatus(422);
    }

    public function test_trainer_can_update_workout_exercise(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);
        $exercise = Exercise::first();
        $workoutExercise = WorkoutExercise::factory()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
        ]);

        $response = $this->actingAs($trainer->user)->patchJson(
            "/api/v1/trainer/workouts/{$workout->id}/exercises/{$workoutExercise->id}",
            ['sets' => 5, 'target_reps' => 8]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('workout_exercises', [
            'id' => $workoutExercise->id,
            'sets' => 5,
            'target_reps' => 8,
        ]);
    }

    public function test_trainer_can_remove_exercise_from_workout(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);
        $exercise = Exercise::first();
        $workoutExercise = WorkoutExercise::factory()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
        ]);

        $response = $this->actingAs($trainer->user)->deleteJson(
            "/api/v1/trainer/workouts/{$workout->id}/exercises/{$workoutExercise->id}"
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('workout_exercises', [
            'id' => $workoutExercise->id,
        ]);
    }

    public function test_removing_exercise_updates_workout_totals(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);
        $exercise = Exercise::first();
        $workoutExercise = WorkoutExercise::factory()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'sets' => 4,
        ]);

        $workout->refresh();
        $initialExercises = $workout->total_exercises;
        $initialSets = $workout->total_sets;

        $this->actingAs($trainer->user)->deleteJson(
            "/api/v1/trainer/workouts/{$workout->id}/exercises/{$workoutExercise->id}"
        );

        $workout->refresh();
        $this->assertEquals($initialExercises - 1, $workout->total_exercises);
        $this->assertEquals($initialSets - 4, $workout->total_sets);
    }

    public function test_trainer_cannot_add_exercise_to_another_trainers_workout(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $workout = Workout::factory()->create(['creator_id' => $trainer1->id]);
        $exercise = Exercise::first();

        $response = $this->actingAs($trainer2->user)->postJson("/api/v1/trainer/workouts/{$workout->id}/exercises", [
            'exercise_id' => $exercise->id,
            'sort_order' => 1,
            'sets' => 3,
            'target_reps' => 10,
        ]);

        $response->assertStatus(403);
    }
}
