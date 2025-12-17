<?php

namespace Tests\Feature;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Domain\Training\TrainingPlan;
use App\Domain\Training\TrainingPlanWeek;
use App\Domain\Training\TrainingPlanDay;
use App\Domain\Training\Workout;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\FitnessGoal;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerTrainingPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EquipmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'SystemExerciseSeeder']);
    }

    // Training Plan Creation Tests

    public function test_trainer_can_create_training_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => '12 Week Strength Program',
            'description' => 'A comprehensive 12-week strength building program',
            'goal' => FitnessGoal::STRENGTH->value,
            'difficulty' => Difficulty::INTERMEDIATE->value,
            'duration_weeks' => 12,
            'days_per_week' => 4,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'goal',
                    'difficulty',
                    'duration_weeks',
                    'days_per_week',
                    'status',
                    'creator',
                ],
            ]);

        $this->assertDatabaseHas('training_plans', [
            'name' => '12 Week Strength Program',
            'creator_id' => $trainer->id,
            'status' => WorkoutStatus::DRAFT->value,
            'duration_weeks' => 12,
            'days_per_week' => 4,
        ]);
    }

    public function test_non_trainer_cannot_create_training_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'duration_weeks' => 4,
            'days_per_week' => 3,
        ]);

        $response->assertStatus(403);
    }

    public function test_training_plan_creation_requires_name(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'duration_weeks' => 8,
            'days_per_week' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_training_plan_creation_requires_duration_weeks(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'days_per_week' => 5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration_weeks']);
    }

    public function test_training_plan_creation_requires_days_per_week(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'duration_weeks' => 8,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days_per_week']);
    }

    public function test_training_plan_validates_days_per_week_range(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'duration_weeks' => 8,
            'days_per_week' => 8, // Invalid: must be 1-7
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days_per_week']);
    }

    public function test_training_plan_validates_difficulty_enum(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'duration_weeks' => 8,
            'days_per_week' => 5,
            'difficulty' => 'invalid_difficulty',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['difficulty']);
    }

    public function test_training_plan_validates_goal_enum(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Test Plan',
            'duration_weeks' => 8,
            'days_per_week' => 5,
            'goal' => 'invalid_goal',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['goal']);
    }

    public function test_premium_training_plan_requires_price(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Premium Plan',
            'duration_weeks' => 12,
            'days_per_week' => 6,
            'pricing_type' => PricingType::PREMIUM->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price_cents']);
    }

    public function test_trainer_can_create_premium_training_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($trainer->user)->postJson('/api/v1/trainer/training-plans', [
            'name' => 'Premium 12 Week Plan',
            'duration_weeks' => 12,
            'days_per_week' => 6,
            'pricing_type' => PricingType::PREMIUM->value,
            'price_cents' => 4999,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('training_plans', [
            'name' => 'Premium 12 Week Plan',
            'pricing_type' => PricingType::PREMIUM->value,
            'price_cents' => 4999,
        ]);
    }

    // Training Plan Update Tests

    public function test_trainer_can_update_their_training_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($trainer->user)->patchJson("/api/v1/trainer/training-plans/{$plan->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plans', [
            'id' => $plan->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_trainer_cannot_update_another_trainers_plan(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create(['creator_id' => $trainer1->id]);

        $response = $this->actingAs($trainer2->user)->patchJson("/api/v1/trainer/training-plans/{$plan->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    // Training Plan Viewing Tests

    public function test_trainer_can_view_their_training_plans(): void
    {
        $trainer = TrainerProfile::factory()->create();
        TrainingPlan::factory()->count(3)->create(['creator_id' => $trainer->id]);
        TrainingPlan::factory()->count(2)->create(); // Other trainers' plans

        $response = $this->actingAs($trainer->user)->getJson('/api/v1/trainer/training-plans');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_trainer_can_view_specific_training_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'name' => 'My Plan',
        ]);

        $response = $this->actingAs($trainer->user)->getJson("/api/v1/trainer/training-plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $plan->id)
            ->assertJsonPath('data.name', 'My Plan');
    }

    public function test_trainer_cannot_view_another_trainers_draft_plan(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer1->id,
            'status' => WorkoutStatus::DRAFT,
        ]);

        $response = $this->actingAs($trainer2->user)->getJson("/api/v1/trainer/training-plans/{$plan->id}");

        $response->assertStatus(403);
    }

    public function test_anyone_can_view_published_training_plan(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer1->id,
            'status' => WorkoutStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($trainer2->user)->getJson("/api/v1/trainer/training-plans/{$plan->id}");

        $response->assertStatus(200);
    }

    // Training Plan Deletion Tests

    public function test_trainer_can_delete_their_training_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->deleteJson("/api/v1/trainer/training-plans/{$plan->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('training_plans', ['id' => $plan->id]);
    }

    public function test_trainer_cannot_delete_another_trainers_plan(): void
    {
        $trainer1 = TrainerProfile::factory()->create();
        $trainer2 = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create(['creator_id' => $trainer1->id]);

        $response = $this->actingAs($trainer2->user)->deleteJson("/api/v1/trainer/training-plans/{$plan->id}");

        $response->assertStatus(403);
    }

    // Status Transition Tests

    public function test_trainer_can_publish_draft_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'status' => WorkoutStatus::DRAFT,
        ]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/publish");

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plans', [
            'id' => $plan->id,
            'status' => WorkoutStatus::PUBLISHED->value,
        ]);

        $plan->refresh();
        $this->assertNotNull($plan->published_at);
    }

    public function test_trainer_can_archive_published_plan(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'status' => WorkoutStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/archive");

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plans', [
            'id' => $plan->id,
            'status' => WorkoutStatus::ARCHIVED->value,
        ]);
    }

    public function test_trainer_can_revert_plan_to_draft(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'status' => WorkoutStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/draft");

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plans', [
            'id' => $plan->id,
            'status' => WorkoutStatus::DRAFT->value,
        ]);
    }

    // Structure Generation Tests

    public function test_generating_plan_structure_creates_weeks_and_days(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 4,
            'days_per_week' => 5,
        ]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/generate-structure");

        $response->assertStatus(200);

        // Check that 4 weeks were created
        $this->assertEquals(4, $plan->weeks()->count());

        // Check that each week has 5 days
        $plan->weeks->each(function ($week) {
            $this->assertEquals(5, $week->days()->count());
        });
    }

    public function test_cannot_regenerate_structure_if_weeks_exist(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 4,
            'days_per_week' => 5,
        ]);

        // Generate structure once
        $plan->generateStructure();

        // Try to generate again
        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/generate-structure");

        $response->assertStatus(422);
    }

    // Week Management Tests

    public function test_trainer_can_update_week_details(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 4,
            'days_per_week' => 5,
        ]);
        $plan->generateStructure();
        $week = $plan->weeks()->first();

        $response = $this->actingAs($trainer->user)->patchJson("/api/v1/trainer/training-plans/{$plan->id}/weeks/{$week->id}", [
            'name' => 'Deload Week',
            'notes' => 'Reduce volume by 50%',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plan_weeks', [
            'id' => $week->id,
            'name' => 'Deload Week',
            'notes' => 'Reduce volume by 50%',
        ]);
    }

    // Day Management Tests

    public function test_trainer_can_update_day_details(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 7,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->first();

        $response = $this->actingAs($trainer->user)->patchJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}", [
            'name' => 'Push Day',
            'notes' => 'Focus on chest and shoulders',
            'is_rest_day' => false,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plan_days', [
            'id' => $day->id,
            'name' => 'Push Day',
            'is_rest_day' => false,
        ]);
    }

    public function test_trainer_can_mark_day_as_rest_day(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 7,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->where('day_number', 7)->first();

        $response = $this->actingAs($trainer->user)->patchJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}", [
            'name' => 'Rest Day',
            'is_rest_day' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('training_plan_days', [
            'id' => $day->id,
            'is_rest_day' => true,
        ]);
    }

    // Workout Assignment Tests

    public function test_trainer_can_assign_workout_to_day(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 5,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->first();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}/workouts", [
            'workout_id' => $workout->id,
            'sort_order' => 0,
            'is_optional' => false,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('training_plan_workouts', [
            'training_plan_day_id' => $day->id,
            'workout_id' => $workout->id,
        ]);
    }

    public function test_trainer_can_assign_multiple_workouts_to_same_day(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 5,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->first();
        $workout1 = Workout::factory()->create(['creator_id' => $trainer->id]);
        $workout2 = Workout::factory()->create(['creator_id' => $trainer->id]);

        $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}/workouts", [
            'workout_id' => $workout1->id,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}/workouts", [
            'workout_id' => $workout2->id,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201);

        $this->assertEquals(2, $day->workouts()->count());
    }

    public function test_trainer_can_remove_workout_from_day(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 5,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->first();
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);

        $planWorkout = $day->workouts()->create([
            'workout_id' => $workout->id,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($trainer->user)->deleteJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}/workouts/{$planWorkout->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('training_plan_workouts', [
            'id' => $planWorkout->id,
        ]);
    }

    public function test_cannot_assign_workout_to_rest_day(): void
    {
        $trainer = TrainerProfile::factory()->create();
        $plan = TrainingPlan::factory()->create([
            'creator_id' => $trainer->id,
            'duration_weeks' => 1,
            'days_per_week' => 7,
        ]);
        $plan->generateStructure();
        $day = $plan->weeks()->first()->days()->first();
        $day->update(['is_rest_day' => true]);
        $workout = Workout::factory()->create(['creator_id' => $trainer->id]);

        $response = $this->actingAs($trainer->user)->postJson("/api/v1/trainer/training-plans/{$plan->id}/days/{$day->id}/workouts", [
            'workout_id' => $workout->id,
        ]);

        $response->assertStatus(422);
    }
}
