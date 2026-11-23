<?php

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Workout;

function scheduleUser(string $role = 'trainer'): User
{
    return User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);
}

function schedulePayload(User $trainer, Room $room, string $start, string $end): array
{
    return [
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTimeFromTimeString($start)->format('Y-m-d H:i'),
        'workout_end' => now()->addDay()->setTimeFromTimeString($end)->format('Y-m-d H:i'),
    ];
}

test('trainer cannot be double-booked for overlapping intervals', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 1, 'capacity' => 10]);
    $otherRoom = Room::create(['number' => 2, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->post(route('workouts.store'), schedulePayload($trainer, $otherRoom, '10:30', '11:30'))
        ->assertSessionHasErrors('trainer_id');

    expect(Workout::count())->toBe(1);
});

test('room cannot be double-booked for overlapping intervals', function () {
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => scheduleUser()->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->post(route('workouts.store'), schedulePayload(scheduleUser(), $room, '10:30', '11:30'))
        ->assertSessionHasErrors('room_id');

    expect(Workout::count())->toBe(1);
});

test('back-to-back workouts do not conflict', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->post(route('workouts.store'), schedulePayload($trainer, $room, '11:00', '12:00'))
        ->assertSessionHasNoErrors();

    expect(Workout::count())->toBe(2);
});

test('cancelled workouts do not occupy trainer or room', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
        'cancelled_at' => now(),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->post(route('workouts.store'), schedulePayload($trainer, $room, '10:30', '11:30'))
        ->assertSessionHasNoErrors();

    expect(Workout::count())->toBe(2);
});

test('editing a workout ignores itself when checking conflicts', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    $workout = Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->put(route('workouts.update', $workout), schedulePayload($trainer, $room, '10:00', '11:00'))
        ->assertSessionHasNoErrors();
});

test('editing a workout into a conflict is rejected', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(10, 0),
        'workout_end' => now()->addDay()->setTime(11, 0),
    ]);

    $second = Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->setTime(13, 0),
        'workout_end' => now()->addDay()->setTime(14, 0),
    ]);

    $this->actingAs(scheduleUser('manager'))
        ->put(route('workouts.update', $second), schedulePayload($trainer, $room, '10:30', '11:30'))
        ->assertSessionHasErrors('trainer_id');
});

test('trainer selects date time and room for own workout', function () {
    $trainer = scheduleUser();
    $room = Room::create(['number' => 7, 'capacity' => 12]);

    $this->actingAs($trainer)->post(route('workouts.store'), [
        'room_id' => $room->id,
        'workout_start' => now()->addDays(2)->setTime(18, 0)->format('Y-m-d H:i'),
        'workout_end' => now()->addDays(2)->setTime(19, 30)->format('Y-m-d H:i'),
        'price' => 800,
    ])->assertRedirect(route('workouts.index'));

    $workout = Workout::sole();
    expect($workout->trainer_id)->toBe($trainer->id)
        ->and($workout->room_id)->toBe($room->id)
        ->and((float) $workout->price)->toBe(800.0);
});

test('workout cannot be assigned to a non-existent room', function () {
    $this->actingAs(scheduleUser('manager'))
        ->post(route('workouts.store'), [
            'trainer_id' => scheduleUser()->id,
            'room_id' => 9999,
            'workout_start' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
            'workout_end' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i'),
        ])
        ->assertSessionHasErrors('room_id');

    expect(Workout::count())->toBe(0);
});

test('workout form offers existing rooms and prefills selected time', function () {
    $room = Room::create(['number' => 5, 'capacity' => 8]);

    $this->actingAs(scheduleUser('manager'))->get(route('workouts.create'))
        ->assertOk()
        ->assertSee('Зал №'.$room->number)
        ->assertSee('datetime-local', false);
});
