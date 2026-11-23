<?php

use App\Models\Inventory;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Workout;

beforeEach(function () {
    $this->manager = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'manager'])->id]);
    $this->trainer = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'trainer'])->id]);
    $this->room = Room::create(['number' => 1, 'capacity' => 10]);
    $this->inventory = Inventory::factory()->create(['quantity' => 10]);
});

function workoutPayload(object $test, array $overrides = []): array
{
    return [
        'trainer_id' => $test->trainer->id,
        'room_id' => $test->room->id,
        'workout_start' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
        'workout_end' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i'),
        'inventory_id' => $test->inventory->id,
        'inventory_quantity' => 4,
        ...$overrides,
    ];
}

function workoutWithInventory(object $test, int $quantity, string $start, string $end, ?User $trainer = null, ?Room $room = null): Workout
{
    return Workout::create([
        'trainer_id' => ($trainer ?? $test->trainer)->id,
        'room_id' => ($room ?? $test->room)->id,
        'inventory_id' => $test->inventory->id,
        'inventory_quantity' => $quantity,
        'workout_start' => now()->addDay()->setTimeFromTimeString($start),
        'workout_end' => now()->addDay()->setTimeFromTimeString($end),
    ]);
}

test('manager assigns available inventory to a new workout', function () {
    $this->actingAs($this->manager)
        ->post(route('workouts.store'), workoutPayload($this))
        ->assertRedirect(route('workouts.index'));

    expect(Workout::sole())
        ->inventory_id->toBe($this->inventory->id)
        ->inventory_quantity->toBe(4);
});

test('inventory occupied by an overlapping workout cannot be assigned', function () {
    // Другой тренер и другой зал, чтобы конфликт был именно по инвентарю
    $otherTrainer = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'trainer'])->id]);
    $otherRoom = Room::create(['number' => 2, 'capacity' => 10]);
    workoutWithInventory($this, 8, '10:30', '11:30', $otherTrainer, $otherRoom);

    $this->actingAs($this->manager)
        ->post(route('workouts.store'), workoutPayload($this, ['inventory_quantity' => 3]))
        ->assertSessionHasErrors('inventory_quantity');

    expect(Workout::count())->toBe(1);
});

test('inventory is free again after the other workout ends', function () {
    $otherTrainer = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'trainer'])->id]);
    $otherRoom = Room::create(['number' => 2, 'capacity' => 10]);
    workoutWithInventory($this, 10, '08:00', '10:00', $otherTrainer, $otherRoom);

    $this->actingAs($this->manager)
        ->post(route('workouts.store'), workoutPayload($this, ['inventory_quantity' => 10]))
        ->assertSessionHasNoErrors();

    expect(Workout::count())->toBe(2);
});

test('updating a workout does not count its own inventory as occupied', function () {
    $workout = workoutWithInventory($this, 10, '10:00', '11:00');

    $this->actingAs($this->manager)
        ->put(route('workouts.update', $workout), workoutPayload($this, ['inventory_quantity' => 10]))
        ->assertSessionHasNoErrors();
});

test('inventory quantity is required when inventory is selected', function () {
    $this->actingAs($this->manager)
        ->post(route('workouts.store'), workoutPayload($this, ['inventory_quantity' => null]))
        ->assertSessionHasErrors('inventory_quantity');
});

test('workout can be created without inventory', function () {
    $this->actingAs($this->manager)
        ->post(route('workouts.store'), workoutPayload($this, ['inventory_id' => null, 'inventory_quantity' => 5]))
        ->assertSessionHasNoErrors();

    expect(Workout::sole())
        ->inventory_id->toBeNull()
        ->inventory_quantity->toBeNull();
});

test('manager creates, updates and deletes inventory', function () {
    $this->actingAs($this->manager)
        ->post(route('inventories.store'), ['name' => 'Гантели', 'quantity' => 12])
        ->assertRedirect(route('inventories.index'));

    $dumbbells = Inventory::where('name', 'Гантели')->sole();

    $this->put(route('inventories.update', $dumbbells), ['name' => 'Гантели 5 кг', 'quantity' => 6])
        ->assertRedirect(route('inventories.index'));

    expect($dumbbells->fresh())->name->toBe('Гантели 5 кг')->quantity->toBe(6);

    $this->delete(route('inventories.destroy', $dumbbells))->assertRedirect(route('inventories.index'));

    expect(Inventory::whereKey($dumbbells->id)->exists())->toBeFalse();
});

test('inventory quantity cannot drop below what upcoming workouts occupy', function () {
    workoutWithInventory($this, 7, '10:00', '11:00');

    $this->actingAs($this->manager)
        ->put(route('inventories.update', $this->inventory), ['name' => $this->inventory->name, 'quantity' => 6])
        ->assertSessionHasErrors('quantity');

    expect($this->inventory->fresh()->quantity)->toBe(10);
});

test('inventory assigned to an upcoming workout cannot be deleted', function () {
    workoutWithInventory($this, 1, '10:00', '11:00');

    $this->actingAs($this->manager)
        ->delete(route('inventories.destroy', $this->inventory))
        ->assertSessionHasErrors('inventory');

    expect($this->inventory->fresh())->not->toBeNull();
});

test('trainer cannot manage inventory', function () {
    $this->actingAs($this->trainer)->get(route('inventories.index'))->assertForbidden();
});
