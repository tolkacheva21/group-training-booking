<?php

use App\Models\Record;
use App\Models\Role;
use App\Models\Room;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;

function userWithRole(string $role): User
{
    return User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);
}

function workoutFor(User $trainer): Workout
{
    $room = Room::create(['number' => fake()->unique()->numberBetween(1, 999), 'capacity' => 10]);

    return Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay(),
        'workout_end' => now()->addDay()->addHour(),
    ]);
}

test('manager creates a workout for a user with the trainer role', function () {
    $trainer = userWithRole('trainer');
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    $this->actingAs(userWithRole('manager'))->post(route('workouts.store'), [
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->format('Y-m-d H:i'),
        'workout_end' => now()->addDay()->addHour()->format('Y-m-d H:i'),
    ])->assertRedirect(route('workouts.index'));

    expect(Workout::sole()->trainer->is($trainer))->toBeTrue();
});

test('workout cannot be assigned to a user without the trainer role', function () {
    $room = Room::create(['number' => 1, 'capacity' => 10]);

    $this->actingAs(userWithRole('admin'))->post(route('workouts.store'), [
        'trainer_id' => userWithRole('user')->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay()->format('Y-m-d H:i'),
        'workout_end' => now()->addDay()->addHour()->format('Y-m-d H:i'),
    ])->assertSessionHasErrors('trainer_id');

    expect(Workout::count())->toBe(0);
});

test('workout form lists only trainers', function () {
    $trainer = userWithRole('trainer');
    $client = userWithRole('user');

    $this->actingAs(userWithRole('admin'))->get(route('workouts.create'))
        ->assertOk()
        ->assertSee($trainer->name)
        ->assertDontSee($client->name);
});

test('trainer sees only own workouts', function () {
    $trainer = userWithRole('trainer');
    $ownWorkout = workoutFor($trainer);
    $otherWorkout = workoutFor(userWithRole('trainer'));

    $this->actingAs($trainer)->get(route('workouts.index'))
        ->assertOk()
        ->assertSee(route('workouts.show', $ownWorkout), false)
        ->assertDontSee(route('workouts.show', $otherWorkout), false)
        ->assertDontSee(route('workouts.create'), false);

    $this->actingAs($trainer)->get(route('workouts.show', $otherWorkout))->assertForbidden();
});

test('trainer sees records on own workout', function () {
    $trainer = userWithRole('trainer');
    $workout = workoutFor($trainer);
    $client = userWithRole('user');
    Record::create(['workout_id' => $workout->id, 'user_id' => $client->id, 'status_id' => Status::idOf(Status::Pending)]);

    $this->actingAs($trainer)->get(route('workouts.show', $workout))
        ->assertOk()
        ->assertSee($client->name)
        ->assertDontSee(route('workouts.edit', $workout), false);
});

test('trainer cannot manage workouts', function () {
    $trainer = userWithRole('trainer');
    $workout = workoutFor($trainer);

    $this->actingAs($trainer)->get(route('workouts.create'))->assertForbidden();
    $this->actingAs($trainer)->get(route('workouts.edit', $workout))->assertForbidden();
    $this->actingAs($trainer)->delete(route('workouts.destroy', $workout))->assertForbidden();

    expect(Workout::whereKey($workout->id)->exists())->toBeTrue();
});

test('admin users list shows each user role', function () {
    $trainer = userWithRole('trainer');

    $this->actingAs(userWithRole('admin'))->get(route('users.index'))
        ->assertOk()
        ->assertSeeInOrder([$trainer->name, 'trainer']);
});
