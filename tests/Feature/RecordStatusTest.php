<?php

use App\Models\Record;
use App\Models\Role;
use App\Models\Room;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;

function statusUser(string $role = 'user'): User
{
    return User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);
}

function statusWorkout(int $capacity = 10, string $start = '+1 day', string $end = '+1 day +1 hour'): Workout
{
    $room = Room::create(['number' => fake()->unique()->numberBetween(1, 999), 'capacity' => $capacity]);

    return Workout::create([
        'trainer_id' => statusUser('trainer')->id,
        'room_id' => $room->id,
        'workout_start' => now()->modify($start),
        'workout_end' => now()->modify($end),
    ]);
}

function statusRecord(Workout $workout, string $status = Status::Pending): Record
{
    return Record::create([
        'workout_id' => $workout->id,
        'user_id' => statusUser()->id,
        'status_id' => Status::idOf($status),
    ]);
}

test('new record by a user is not confirmed and takes no place', function () {
    $workout = statusWorkout();

    $this->actingAs(statusUser())->post(route('records.store'), ['workout_id' => $workout->id])
        ->assertRedirect(route('records.index'));

    expect(Record::sole()->status->name)->toBe(Status::Pending)
        ->and($workout->fresh()->current_participants)->toBe(0);
});

test('manager confirms a record', function () {
    $workout = statusWorkout();
    $record = statusRecord($workout);

    $this->actingAs(statusUser('manager'))->patch(route('records.confirm', $record))
        ->assertRedirect();

    expect($record->fresh()->status->name)->toBe(Status::Confirmed)
        ->and($workout->fresh()->current_participants)->toBe(1);
});

test('manager cannot confirm more records than the room capacity', function () {
    $workout = statusWorkout(capacity: 1);
    statusRecord($workout, Status::Confirmed);
    $record = statusRecord($workout);

    $this->actingAs(statusUser('manager'))->patch(route('records.confirm', $record))
        ->assertSessionHasErrors('record');

    expect($record->fresh()->status->name)->toBe(Status::Pending);
});

test('user cannot book when all places are confirmed', function () {
    $workout = statusWorkout(capacity: 1);
    statusRecord($workout, Status::Confirmed);

    $this->actingAs(statusUser())->post(route('records.store'), ['workout_id' => $workout->id])
        ->assertSessionHasErrors('workout_id');
});

test('only admin and manager can confirm records', function (string $role) {
    $record = statusRecord(statusWorkout());

    $this->actingAs(statusUser($role))->patch(route('records.confirm', $record))->assertForbidden();

    expect($record->fresh()->status->name)->toBe(Status::Pending);
})->with(['user', 'trainer']);

test('confirmed record cannot be confirmed again', function () {
    $record = statusRecord(statusWorkout(), Status::Confirmed);

    $this->actingAs(statusUser('manager'))->patch(route('records.confirm', $record))->assertForbidden();
});

test('finished workouts get completed status for confirmed records only', function () {
    $finished = statusWorkout(start: '-2 hours', end: '-1 hour');
    $confirmed = statusRecord($finished, Status::Confirmed);
    $pending = statusRecord($finished);
    $upcoming = statusRecord(statusWorkout(), Status::Confirmed);

    $this->artisan('records:complete-finished')->assertSuccessful();

    expect($confirmed->fresh()->status->name)->toBe(Status::Completed)
        ->and($pending->fresh()->status->name)->toBe(Status::Pending)
        ->and($upcoming->fresh()->status->name)->toBe(Status::Confirmed);
});

test('manager rejects a pending record', function () {
    $workout = statusWorkout();
    $record = statusRecord($workout);

    $this->actingAs(statusUser('manager'))->patch(route('records.reject', $record))
        ->assertRedirect();

    expect($record->fresh()->status->name)->toBe(Status::Rejected)
        ->and($workout->fresh()->current_participants)->toBe(0);
});

test('rejecting a confirmed record frees the place', function () {
    $workout = statusWorkout(capacity: 1);
    $confirmed = statusRecord($workout, Status::Confirmed);
    $workout->update(['current_participants' => 1]);
    $pending = statusRecord($workout);

    $manager = statusUser('manager');
    $this->actingAs($manager)->patch(route('records.reject', $confirmed));

    expect($workout->fresh()->current_participants)->toBe(0);

    $this->actingAs($manager)->patch(route('records.confirm', $pending))->assertSessionHasNoErrors();

    expect($pending->fresh()->status->name)->toBe(Status::Confirmed);
});

test('only admin and manager can reject records', function (string $role) {
    $record = statusRecord(statusWorkout());

    $this->actingAs(statusUser($role))->patch(route('records.reject', $record))->assertForbidden();

    expect($record->fresh()->status->name)->toBe(Status::Pending);
})->with(['user', 'trainer']);

test('completed or rejected record cannot be rejected', function (string $status) {
    $record = statusRecord(statusWorkout(), $status);

    $this->actingAs(statusUser('manager'))->patch(route('records.reject', $record))->assertForbidden();
})->with([Status::Completed, Status::Rejected]);

test('completed record cannot be cancelled', function () {
    $user = statusUser();
    $record = Record::create([
        'workout_id' => statusWorkout(start: '-2 hours', end: '-1 hour')->id,
        'user_id' => $user->id,
        'status_id' => Status::idOf(Status::Completed),
    ]);

    $this->actingAs($user)->delete(route('records.destroy', $record))->assertForbidden();
});
