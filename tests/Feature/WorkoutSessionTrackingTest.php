<?php

namespace Tests\Feature;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\SetLog;
use App\Domain\Execution\WorkoutSession;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\Exercise;
use App\Domain\Training\Workout;
use App\Domain\Training\WorkoutExercise;
use App\Shared\Enums\ExerciseLogStatus;
use App\Shared\Enums\SessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutSessionTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EquipmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'SystemExerciseSeeder']);
    }

    /*
    |--------------------------------------------------------------------------
    | Session Management Tests
    |--------------------------------------------------------------------------
    */

    public function test_trainee_can_start_workout_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $workout = Workout::factory()
            ->has(WorkoutExercise::factory()->count(3), 'workoutExercises')
            ->create();

        $response = $this->actingAs($trainee->user)->postJson('/api/v1/sessions', [
            'workout_id' => $workout->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workout' => ['id', 'name'],
                    'status',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('workout_sessions', [
            'trainee_id' => $trainee->id,
            'workout_id' => $workout->id,
            'status' => SessionStatus::STARTED->value,
        ]);
    }

    public function test_trainee_can_list_their_workout_sessions(): void
    {
        $trainee = TraineeProfile::factory()->create();
        WorkoutSession::factory()->count(5)->create(['trainee_id' => $trainee->id]);

        // Create sessions for another trainee (should not be returned)
        $otherTrainee = TraineeProfile::factory()->create();
        WorkoutSession::factory()->count(3)->create(['trainee_id' => $otherTrainee->id]);

        $response = $this->actingAs($trainee->user)->getJson('/api/v1/sessions');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_trainee_can_view_specific_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);

        $response = $this->actingAs($trainee->user)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'workout',
                    'status',
                    'started_at',
                    'exercise_logs',
                ],
            ]);
    }

    public function test_trainee_cannot_view_other_trainee_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $otherTrainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $otherTrainee->id]);

        $response = $this->actingAs($trainee->user)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(403);
    }

    public function test_trainee_can_complete_workout_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/complete", [
            'notes' => 'Great workout!',
            'rating' => 4,
        ]);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals(SessionStatus::COMPLETED, $session->status);
        $this->assertNotNull($session->completed_at);
        $this->assertEquals('Great workout!', $session->notes);
        $this->assertEquals(4, $session->rating);
    }

    public function test_completing_session_calculates_totals(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $workout = Workout::factory()->create();
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);

        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout->id,
            'status' => SessionStatus::IN_PROGRESS,
        ]);

        $exerciseLog = ExerciseLog::factory()->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $workoutExercise->exercise_id,
            'workout_exercise_id' => $workoutExercise->id,
        ]);

        // Create 3 sets: 100kg x 10 reps each = 1000kg per set, 3000kg total
        SetLog::factory()->create([
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => 1,
            'weight_kg' => 100,
            'actual_reps' => 10,
        ]);
        SetLog::factory()->create([
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => 2,
            'weight_kg' => 100,
            'actual_reps' => 10,
        ]);
        SetLog::factory()->create([
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => 3,
            'weight_kg' => 100,
            'actual_reps' => 10,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/complete");

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals(3000, $session->total_volume_kg);
        $this->assertNotNull($session->total_duration_seconds);
    }

    public function test_trainee_can_abandon_workout_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/abandon");

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals(SessionStatus::ABANDONED, $session->status);
        $this->assertNotNull($session->completed_at);
    }

    public function test_rating_must_be_between_1_and_5(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/complete", [
            'rating' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    /*
    |--------------------------------------------------------------------------
    | Exercise Logging Tests
    |--------------------------------------------------------------------------
    */

    public function test_trainee_can_start_exercise_in_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $workout = Workout::factory()->create();
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);

        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout->id,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises", [
            'workout_exercise_id' => $workoutExercise->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'exercise',
                    'status',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('exercise_logs', [
            'workout_session_id' => $session->id,
            'workout_exercise_id' => $workoutExercise->id,
            'exercise_id' => $workoutExercise->exercise_id,
            'status' => ExerciseLogStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_trainee_can_complete_exercise(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create([
            'workout_session_id' => $session->id,
            'status' => ExerciseLogStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($trainee->user)->patchJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/complete", [
            'notes' => 'Felt strong today',
        ]);

        $response->assertStatus(200);

        $exerciseLog->refresh();
        $this->assertEquals(ExerciseLogStatus::COMPLETED, $exerciseLog->status);
        $this->assertEquals('Felt strong today', $exerciseLog->notes);
        $this->assertNotNull($exerciseLog->completed_at);
    }

    public function test_trainee_can_skip_exercise(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create([
            'workout_session_id' => $session->id,
            'status' => ExerciseLogStatus::PENDING,
        ]);

        $response = $this->actingAs($trainee->user)->patchJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/skip");

        $response->assertStatus(200);

        $exerciseLog->refresh();
        $this->assertEquals(ExerciseLogStatus::SKIPPED, $exerciseLog->status);
        $this->assertNotNull($exerciseLog->completed_at);
    }

    /*
    |--------------------------------------------------------------------------
    | Set Logging Tests
    |--------------------------------------------------------------------------
    */

    public function test_trainee_can_log_set_with_reps(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create([
            'workout_session_id' => $session->id,
            'status' => ExerciseLogStatus::IN_PROGRESS,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'weight_kg' => 100.5,
            'target_reps' => 10,
            'actual_reps' => 12,
            'rpe' => 8,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'set_number',
                    'weight_kg',
                    'actual_reps',
                    'rpe',
                ],
            ]);

        $this->assertDatabaseHas('set_logs', [
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => 1,
            'weight_kg' => 100.5,
            'actual_reps' => 12,
            'rpe' => 8,
        ]);
    }

    public function test_trainee_can_log_set_with_duration(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'target_duration_seconds' => 60,
            'actual_duration_seconds' => 65,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('set_logs', [
            'exercise_log_id' => $exerciseLog->id,
            'actual_duration_seconds' => 65,
        ]);
    }

    public function test_trainee_can_log_set_with_distance(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'target_distance_meters' => 1000,
            'actual_distance_meters' => 1050,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('set_logs', [
            'exercise_log_id' => $exerciseLog->id,
            'actual_distance_meters' => 1050,
        ]);
    }

    public function test_trainee_can_mark_set_as_warmup(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'weight_kg' => 60,
            'actual_reps' => 10,
            'is_warmup' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('set_logs', [
            'exercise_log_id' => $exerciseLog->id,
            'is_warmup' => true,
        ]);
    }

    public function test_trainee_can_mark_set_as_to_failure(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'weight_kg' => 100,
            'actual_reps' => 8,
            'is_failure' => true,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('set_logs', [
            'exercise_log_id' => $exerciseLog->id,
            'is_failure' => true,
        ]);
    }

    public function test_set_requires_at_least_one_performance_metric(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'weight_kg' => 100,
            // No reps, duration, or distance
        ]);

        $response->assertStatus(422);
    }

    public function test_rpe_must_be_between_1_and_10(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'actual_reps' => 10,
            'rpe' => 15,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rpe']);
    }

    public function test_trainee_can_update_set(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $trainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);
        $setLog = SetLog::factory()->create([
            'exercise_log_id' => $exerciseLog->id,
            'actual_reps' => 10,
        ]);

        $response = $this->actingAs($trainee->user)->patchJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets/{$setLog->id}", [
            'actual_reps' => 12,
            'rpe' => 9,
        ]);

        $response->assertStatus(200);

        $setLog->refresh();
        $this->assertEquals(12, $setLog->actual_reps);
        $this->assertEquals(9, $setLog->rpe);
    }

    /*
    |--------------------------------------------------------------------------
    | Immutability Tests
    |--------------------------------------------------------------------------
    */

    public function test_cannot_modify_session_after_completion(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/complete");

        $response->assertStatus(422);
    }

    public function test_cannot_add_exercise_to_completed_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $workout = Workout::factory()->create();
        $workoutExercise = WorkoutExercise::factory()->create(['workout_id' => $workout->id]);

        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout->id,
            'status' => SessionStatus::COMPLETED,
        ]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises", [
            'workout_exercise_id' => $workoutExercise->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_modify_set_in_completed_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::COMPLETED,
        ]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);
        $setLog = SetLog::factory()->create(['exercise_log_id' => $exerciseLog->id]);

        $response = $this->actingAs($trainee->user)->patchJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets/{$setLog->id}", [
            'actual_reps' => 15,
        ]);

        $response->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | Session History Tests
    |--------------------------------------------------------------------------
    */

    public function test_trainee_can_filter_sessions_by_date_range(): void
    {
        $trainee = TraineeProfile::factory()->create();

        // Create sessions at different dates
        WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'started_at' => now()->subDays(10),
        ]);
        WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'started_at' => now()->subDays(5),
        ]);
        WorkoutSession::factory()->create([
            'trainee_id' => $trainee->id,
            'started_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($trainee->user)->getJson('/api/v1/sessions?' . http_build_query([
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Only the 5-day and 1-day old sessions
    }

    public function test_trainee_can_filter_sessions_by_workout(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $workout1 = Workout::factory()->create();
        $workout2 = Workout::factory()->create();

        WorkoutSession::factory()->count(3)->create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout1->id,
        ]);
        WorkoutSession::factory()->count(2)->create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout2->id,
        ]);

        $response = $this->actingAs($trainee->user)->getJson("/api/v1/sessions?workout_id={$workout1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_trainee_can_filter_sessions_by_status(): void
    {
        $trainee = TraineeProfile::factory()->create();

        WorkoutSession::factory()->count(3)->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::COMPLETED,
        ]);
        WorkoutSession::factory()->count(2)->create([
            'trainee_id' => $trainee->id,
            'status' => SessionStatus::STARTED,
        ]);

        $response = $this->actingAs($trainee->user)->getJson('/api/v1/sessions?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization Tests
    |--------------------------------------------------------------------------
    */

    public function test_only_trainee_can_access_session_endpoints(): void
    {
        $user = \App\Domain\Identity\User::factory()->create();
        // User has no trainee profile

        $response = $this->actingAs($user)->getJson('/api/v1/sessions');

        $response->assertStatus(403);
    }

    public function test_trainee_cannot_complete_another_trainee_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $otherTrainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $otherTrainee->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/complete");

        $response->assertStatus(403);
    }

    public function test_trainee_cannot_log_exercise_in_another_trainee_session(): void
    {
        $trainee = TraineeProfile::factory()->create();
        $otherTrainee = TraineeProfile::factory()->create();
        $session = WorkoutSession::factory()->create(['trainee_id' => $otherTrainee->id]);
        $exerciseLog = ExerciseLog::factory()->create(['workout_session_id' => $session->id]);

        $response = $this->actingAs($trainee->user)->postJson("/api/v1/sessions/{$session->id}/exercises/{$exerciseLog->id}/sets", [
            'set_number' => 1,
            'actual_reps' => 10,
        ]);

        $response->assertStatus(403);
    }
}
