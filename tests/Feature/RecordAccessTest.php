<?php

use App\Models\Record;
use App\Models\Role;
use App\Models\Room;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

function createFutureWorkout(?User $trainer = null): Workout
{
    $trainer ??= User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'trainer'])->id,
    ]);

    $room = Room::create(['number' => fake()->unique()->numberBetween(1, 999), 'capacity' => 10]);

    return Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'workout_start' => now()->addDay(),
        'workout_end' => now()->addDay()->addHour(),
    ]);
}

test('user sees only own records', function () {
    $workout = createFutureWorkout();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Record::create(['workout_id' => $workout->id, 'user_id' => $user->id, 'status_id' => Status::idOf(Status::Pending)]);
    Record::create(['workout_id' => $workout->id, 'user_id' => $otherUser->id, 'status_id' => Status::idOf(Status::Pending)]);

    $this->actingAs($user)->get(route('records.index'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertDontSee($otherUser->name);
});

test('user cannot view or cancel a record of another user', function () {
    $record = Record::create([
        'workout_id' => createFutureWorkout()->id,
        'user_id' => User::factory()->create()->id,
        'status_id' => Status::idOf(Status::Pending),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('records.show', $record))->assertForbidden();
    $this->actingAs($user)->delete(route('records.destroy', $record))->assertForbidden();

    expect(Record::whereKey($record->id)->exists())->toBeTrue();
});

test('user can cancel own record', function () {
    $user = User::factory()->create();
    $record = Record::create(['workout_id' => createFutureWorkout()->id, 'user_id' => $user->id, 'status_id' => Status::idOf(Status::Pending)]);

    $this->actingAs($user)->delete(route('records.destroy', $record))
        ->assertRedirect(route('records.index'));

    expect(Record::whereKey($record->id)->exists())->toBeFalse();
});

test('record is created for the authenticated user regardless of submitted user_id', function () {
    $workout = createFutureWorkout();
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->post(route('records.store'), [
        'workout_id' => $workout->id,
        'user_id' => $otherUser->id,
    ])->assertRedirect();

    expect(Record::where('workout_id', $workout->id)->pluck('user_id')->all())->toBe([$user->id]);
});

test('records page lists available workouts with a booking button next to each', function () {
    $workout = createFutureWorkout();

    $this->actingAs(User::factory()->create())->get(route('records.index'))
        ->assertOk()
        ->assertSee($workout->trainer->name)
        ->assertSee('Записаться')
        ->assertSee('name="workout_id" value="'.$workout->id.'"', false)
        ->assertDontSee('Создать запись');
});

test('manager sees records of all users', function () {
    $workout = createFutureWorkout();
    $client = User::factory()->create();
    Record::create(['workout_id' => $workout->id, 'user_id' => $client->id, 'status_id' => Status::idOf(Status::Pending)]);

    $manager = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'manager'])->id,
    ]);

    $this->actingAs($manager)->get(route('records.index'))
        ->assertOk()
        ->assertSee($client->name);
});

test('trainer cannot book a workout as a visitor', function () {
    $workout = createFutureWorkout();

    $this->actingAs($workout->trainer)->post(route('records.store'), ['workout_id' => $workout->id])
        ->assertForbidden();

    expect($workout->records()->exists())->toBeFalse();
});

test('admin and manager cannot book workouts as visitors', function (string $role) {
    $workout = createFutureWorkout();

    $this->actingAs(userWithRole($role))->post(route('records.store'), ['workout_id' => $workout->id])
        ->assertForbidden();

    expect($workout->records()->exists())->toBeFalse();
})->with(['admin', 'manager']);

test('roles that cannot book do not see the booking button', function (string $role) {
    createFutureWorkout();

    $this->actingAs(userWithRole($role))->get(route('records.index'))
        ->assertOk()
        ->assertDontSee('Записаться')
        ->assertDontSee('Создать запись');
})->with(['admin', 'manager', 'trainer']);

test('trainer can view records of own workout but not of other workouts', function () {
    $workout = createFutureWorkout();
    $ownRecord = Record::create(['workout_id' => $workout->id, 'user_id' => User::factory()->create()->id, 'status_id' => Status::idOf(Status::Pending)]);
    $otherRecord = Record::create(['workout_id' => createFutureWorkout()->id, 'user_id' => User::factory()->create()->id, 'status_id' => Status::idOf(Status::Pending)]);

    $this->actingAs($workout->trainer)->get(route('records.show', $ownRecord))->assertOk();
    $this->actingAs($workout->trainer)->get(route('records.show', $otherRecord))->assertForbidden();
    $this->actingAs($workout->trainer)->delete(route('records.destroy', $ownRecord))->assertForbidden();
});

test('database seeder creates the admin account', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@gmail.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role->name)->toBe('admin')
        ->and(Hash::check('12345678', $admin->password))->toBeTrue();
});

test('login page links to registration', function () {
    $this->get(route('login'))->assertOk()->assertSee(route('register'), false);
});
