<?php

use App\Models\Inventory;
use App\Models\Record;
use App\Models\Role;
use App\Models\Room;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;

function schedulerUser(string $role = 'user'): User
{
    return User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);
}

function schedulerWorkout(string $start, string $end): Workout
{
    $room = Room::create(['number' => fake()->unique()->numberBetween(1, 999), 'capacity' => 10]);

    return Workout::create([
        'trainer_id' => schedulerUser('trainer')->id,
        'room_id' => $room->id,
        'workout_start' => now()->modify($start),
        'workout_end' => now()->modify($end),
    ]);
}

test('scheduler completes records of finished workouts only', function () {
    $finished = schedulerWorkout('-2 hours', '-1 hour');
    $future = schedulerWorkout('+1 hour', '+2 hours');

    $completedRecord = Record::create([
        'workout_id' => $finished->id,
        'user_id' => schedulerUser()->id,
        'status_id' => Status::idOf(Status::Confirmed),
    ]);
    $futureRecord = Record::create([
        'workout_id' => $future->id,
        'user_id' => schedulerUser()->id,
        'status_id' => Status::idOf(Status::Confirmed),
    ]);

    $this->artisan('records:complete-finished')->assertSuccessful();

    expect($completedRecord->fresh()->status->name)->toBe(Status::Completed)
        ->and($futureRecord->fresh()->status->name)->toBe(Status::Confirmed);
});

test('scheduler is idempotent when run twice', function () {
    $finished = schedulerWorkout('-2 hours', '-1 hour');

    $record = Record::create([
        'workout_id' => $finished->id,
        'user_id' => schedulerUser()->id,
        'status_id' => Status::idOf(Status::Confirmed),
    ]);

    $this->artisan('records:complete-finished')->assertSuccessful();
    $this->artisan('records:complete-finished')->assertSuccessful();

    expect($record->fresh()->status->name)->toBe(Status::Completed);
});

test('scheduler catches up workouts finished while the scheduler was off', function () {
    $longFinished = schedulerWorkout('-3 days', '-3 days +1 hour');

    $record = Record::create([
        'workout_id' => $longFinished->id,
        'user_id' => schedulerUser()->id,
        'status_id' => Status::idOf(Status::Confirmed),
    ]);

    $this->artisan('records:complete-finished')->assertSuccessful();

    expect($record->fresh()->status->name)->toBe(Status::Completed);
});

test('scheduler keeps pending records untouched', function () {
    $finished = schedulerWorkout('-2 hours', '-1 hour');

    $record = Record::create([
        'workout_id' => $finished->id,
        'user_id' => schedulerUser()->id,
        'status_id' => Status::idOf(Status::Pending),
    ]);

    $this->artisan('records:complete-finished')->assertSuccessful();

    expect($record->fresh()->status->name)->toBe(Status::Pending);
});

test('inventory is occupied only during its workout interval', function () {
    $inventory = Inventory::factory()->create(['quantity' => 10]);
    $room = Room::create(['number' => 1, 'capacity' => 10]);
    $trainer = schedulerUser('trainer');

    Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'inventory_id' => $inventory->id,
        'inventory_quantity' => 6,
        'workout_start' => now()->subHour(),
        'workout_end' => now()->addHour(),
    ]);

    // сейчас тренировка идёт — инвентарь занят
    expect($inventory->occupiedNow())->toBe(6)
        ->and($inventory->availableNow())->toBe(4);

    // до начала тренировки инвентарь был свободен
    expect($inventory->availableBetween(now()->subDays(2), now()->subDays(1)))->toBe(10);

    // после окончания тренировки инвентарь освободится
    expect($inventory->availableBetween(now()->addDays(1), now()->addDays(2)))->toBe(10);
});

test('cancelled workout does not occupy inventory', function () {
    $inventory = Inventory::factory()->create(['quantity' => 5]);
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => schedulerUser('trainer')->id,
        'room_id' => $room->id,
        'inventory_id' => $inventory->id,
        'inventory_quantity' => 5,
        'workout_start' => now()->subHour(),
        'workout_end' => now()->addHour(),
        'cancelled_at' => now(),
    ]);

    expect($inventory->occupiedNow())->toBe(0)
        ->and($inventory->availableNow())->toBe(5);
});

test('inventory page shows occupied and free amounts', function () {
    $inventory = Inventory::factory()->create(['name' => 'Коврики', 'quantity' => 10]);
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => schedulerUser('trainer')->id,
        'room_id' => $room->id,
        'inventory_id' => $inventory->id,
        'inventory_quantity' => 4,
        'workout_start' => now()->subHour(),
        'workout_end' => now()->addHour(),
    ]);

    $manager = schedulerUser('manager');

    $this->actingAs($manager)->get(route('inventories.index'))
        ->assertOk()
        ->assertSee('Коврики')
        ->assertSee('Свободно сейчас');
});
