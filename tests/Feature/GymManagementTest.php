<?php

namespace Tests\Feature;

use App\Domain\Gym\Gym;
use App\Domain\Gym\GymEquipment;
use App\Domain\Gym\GymTrainer;
use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Domain\Training\Equipment;
use App\Shared\Enums\GymStatus;
use App\Shared\Enums\GymTrainerStatus;
use App\Shared\Enums\TrainerRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'EquipmentSeeder']);
    }

    // Gym Creation Tests

    public function test_user_can_create_gym(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/gyms', [
            'name' => 'Iron Temple Fitness',
            'description' => 'Premier strength training facility',
            'city' => 'Los Angeles',
            'state' => 'California',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'status',
                    'owner',
                ],
            ]);

        $this->assertDatabaseHas('gyms', [
            'name' => 'Iron Temple Fitness',
            'owner_id' => $user->id,
            'status' => GymStatus::ACTIVE->value,
        ]);
    }

    public function test_gym_slug_is_auto_generated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/gyms', [
            'name' => 'Gold\'s Gym Downtown',
        ]);

        $response->assertStatus(201);

        $gym = Gym::where('owner_id', $user->id)->first();
        $this->assertEquals('golds-gym-downtown', $gym->slug);
    }

    public function test_gym_slug_is_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // First gym with the name
        $this->actingAs($user1)->postJson('/api/v1/gyms', [
            'name' => 'Fitness First',
        ]);

        // Second gym with same name should get different slug
        $response = $this->actingAs($user2)->postJson('/api/v1/gyms', [
            'name' => 'Fitness First',
        ]);

        $response->assertStatus(201);

        $gym2 = Gym::where('owner_id', $user2->id)->first();
        $this->assertEquals('fitness-first-1', $gym2->slug);
    }

    public function test_gym_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/gyms', [
            'description' => 'Test description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_create_gym(): void
    {
        $response = $this->postJson('/api/v1/gyms', [
            'name' => 'Test Gym',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_create_multiple_gyms(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/gyms', ['name' => 'Gym 1']);
        $this->actingAs($user)->postJson('/api/v1/gyms', ['name' => 'Gym 2']);

        $this->assertEquals(2, $user->ownedGyms()->count());
    }

    // Gym Update Tests

    public function test_owner_can_update_gym(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)->patchJson("/api/v1/gyms/{$gym->id}", [
            'name' => 'Updated Gym Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gyms', [
            'id' => $gym->id,
            'name' => 'Updated Gym Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_non_owner_cannot_update_gym(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->patchJson("/api/v1/gyms/{$gym->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    // Gym Viewing Tests

    public function test_anyone_can_view_active_gym(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $gym = Gym::factory()->create([
            'owner_id' => $owner->id,
            'status' => GymStatus::ACTIVE,
        ]);

        $response = $this->actingAs($viewer)->getJson("/api/v1/gyms/{$gym->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $gym->id);
    }

    public function test_user_can_list_all_active_gyms(): void
    {
        Gym::factory()->count(5)->create(['status' => GymStatus::ACTIVE]);
        Gym::factory()->count(2)->create(['status' => GymStatus::CLOSED]);

        $response = $this->getJson('/api/v1/gyms');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_owner_can_view_their_gyms(): void
    {
        $owner = User::factory()->create();
        Gym::factory()->count(3)->create(['owner_id' => $owner->id]);
        Gym::factory()->count(2)->create(); // Other users' gyms

        $response = $this->actingAs($owner)->getJson('/api/v1/gyms/my-gyms');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    // Gym Deletion Tests

    public function test_owner_can_delete_gym(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/gyms/{$gym->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('gyms', ['id' => $gym->id]);
    }

    public function test_non_owner_cannot_delete_gym(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->deleteJson("/api/v1/gyms/{$gym->id}");

        $response->assertStatus(403);
    }

    // Equipment Management Tests

    public function test_owner_can_add_equipment_to_gym(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $equipment = Equipment::first(); // From seeder

        $response = $this->actingAs($user)->postJson("/api/v1/gyms/{$gym->id}/equipment", [
            'equipment_id' => $equipment->id,
            'quantity' => 5,
            'notes' => 'Brand new equipment',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('gym_equipment', [
            'gym_id' => $gym->id,
            'equipment_id' => $equipment->id,
            'quantity' => 5,
        ]);
    }

    public function test_owner_cannot_add_duplicate_equipment(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $equipment = Equipment::first();

        // Add equipment first time
        GymEquipment::create([
            'gym_id' => $gym->id,
            'equipment_id' => $equipment->id,
            'quantity' => 3,
        ]);

        // Try to add same equipment again
        $response = $this->actingAs($user)->postJson("/api/v1/gyms/{$gym->id}/equipment", [
            'equipment_id' => $equipment->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_owner_can_update_equipment_quantity(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $equipment = Equipment::first();

        $gymEquipment = GymEquipment::create([
            'gym_id' => $gym->id,
            'equipment_id' => $equipment->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/gyms/{$gym->id}/equipment/{$equipment->id}", [
            'quantity' => 10,
            'notes' => 'Added more',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gym_equipment', [
            'id' => $gymEquipment->id,
            'quantity' => 10,
            'notes' => 'Added more',
        ]);
    }

    public function test_owner_can_remove_equipment_from_gym(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $equipment = Equipment::first();

        GymEquipment::create([
            'gym_id' => $gym->id,
            'equipment_id' => $equipment->id,
            'quantity' => 3,
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/gyms/{$gym->id}/equipment/{$equipment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('gym_equipment', [
            'gym_id' => $gym->id,
            'equipment_id' => $equipment->id,
        ]);
    }

    public function test_non_owner_cannot_manage_gym_equipment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);
        $equipment = Equipment::first();

        $response = $this->actingAs($otherUser)->postJson("/api/v1/gyms/{$gym->id}/equipment", [
            'equipment_id' => $equipment->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(403);
    }

    // Trainer Management Tests

    public function test_owner_can_hire_trainer(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/v1/gyms/{$gym->id}/trainers", [
            'trainer_id' => $trainer->id,
            'role' => TrainerRole::STAFF_TRAINER->value,
            'hourly_rate_cents' => 7500, // $75/hour
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('gym_trainers', [
            'gym_id' => $gym->id,
            'trainer_id' => $trainer->id,
            'status' => GymTrainerStatus::ACTIVE->value,
        ]);
    }

    public function test_owner_cannot_hire_same_trainer_twice(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $trainer = TrainerProfile::factory()->create();

        // Hire trainer first time
        GymTrainer::create([
            'gym_id' => $gym->id,
            'trainer_id' => $trainer->id,
            'status' => GymTrainerStatus::ACTIVE,
            'role' => TrainerRole::STAFF_TRAINER,
        ]);

        // Try to hire same trainer again
        $response = $this->actingAs($user)->postJson("/api/v1/gyms/{$gym->id}/trainers", [
            'trainer_id' => $trainer->id,
            'role' => TrainerRole::HEAD_TRAINER->value,
        ]);

        $response->assertStatus(422);
    }

    public function test_owner_can_update_trainer_details(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $trainer = TrainerProfile::factory()->create();

        $gymTrainer = GymTrainer::create([
            'gym_id' => $gym->id,
            'trainer_id' => $trainer->id,
            'status' => GymTrainerStatus::ACTIVE,
            'role' => TrainerRole::STAFF_TRAINER,
            'hourly_rate_cents' => 5000,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/gyms/{$gym->id}/trainers/{$trainer->id}", [
            'role' => TrainerRole::HEAD_TRAINER->value,
            'hourly_rate_cents' => 10000,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gym_trainers', [
            'id' => $gymTrainer->id,
            'role' => TrainerRole::HEAD_TRAINER->value,
            'hourly_rate_cents' => 10000,
        ]);
    }

    public function test_owner_can_terminate_trainer(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);
        $trainer = TrainerProfile::factory()->create();

        $gymTrainer = GymTrainer::create([
            'gym_id' => $gym->id,
            'trainer_id' => $trainer->id,
            'status' => GymTrainerStatus::ACTIVE,
            'role' => TrainerRole::STAFF_TRAINER,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/gyms/{$gym->id}/trainers/{$trainer->id}/terminate", [
            'termination_reason' => 'Contract ended',
        ]);

        $response->assertStatus(200);

        $gymTrainer->refresh();
        $this->assertEquals(GymTrainerStatus::TERMINATED, $gymTrainer->status);
        $this->assertNotNull($gymTrainer->terminated_at);
    }

    public function test_non_owner_cannot_manage_gym_trainers(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $owner->id]);
        $trainer = TrainerProfile::factory()->create();

        $response = $this->actingAs($otherUser)->postJson("/api/v1/gyms/{$gym->id}/trainers", [
            'trainer_id' => $trainer->id,
            'role' => TrainerRole::STAFF_TRAINER->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_list_gym_trainers(): void
    {
        $user = User::factory()->create();
        $gym = Gym::factory()->create(['owner_id' => $user->id]);

        GymTrainer::factory()->count(3)->create([
            'gym_id' => $gym->id,
            'status' => GymTrainerStatus::ACTIVE,
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/gyms/{$gym->id}/trainers");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
