<?php

use App\Enums\PaymentStatus;
use App\Models\Record;
use App\Models\Role;
use App\Models\Room;
use App\Models\Status;
use App\Models\User;
use App\Models\Workout;

function paymentUser(string $role = 'user'): User
{
    return User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);
}

function paymentWorkout(float $price = 1500): Workout
{
    $trainer = paymentUser('trainer');
    $room = Room::create(['number' => fake()->unique()->numberBetween(1, 999), 'capacity' => 10]);

    return Workout::create([
        'trainer_id' => $trainer->id,
        'room_id' => $room->id,
        'price' => $price,
        'workout_start' => now()->addDay(),
        'workout_end' => now()->addDay()->addHour(),
    ]);
}

function paymentRecord(Workout $workout, ?User $user = null, string $status = Status::Pending): Record
{
    return Record::create([
        'workout_id' => $workout->id,
        'user_id' => ($user ?? paymentUser())->id,
        'status_id' => Status::idOf($status),
        'amount' => $workout->price,
    ]);
}

test('booking a workout creates an unpaid record', function () {
    $workout = paymentWorkout(1200);
    $user = paymentUser();

    $this->actingAs($user)->post(route('records.store'), ['workout_id' => $workout->id])
        ->assertRedirect();

    $record = Record::sole();
    expect($record->user_id)->toBe($user->id)
        ->and($record->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and((float) $record->amount)->toBe(1200.0);
});

test('user pays for own record', function () {
    $workout = paymentWorkout(900);
    $user = paymentUser();
    $record = paymentRecord($workout, $user);

    $this->actingAs($user)->patch(route('records.pay', $record))
        ->assertRedirect();

    $record->refresh();
    expect($record->payment_status)->toBe(PaymentStatus::Paid)
        ->and((float) $record->amount)->toBe(900.0)
        ->and($record->paid_at)->not->toBeNull();
});

test('paid record cannot be paid again', function () {
    $workout = paymentWorkout();
    $user = paymentUser();
    $record = paymentRecord($workout, $user);

    $this->actingAs($user)->patch(route('records.pay', $record))->assertRedirect();
    $paidAt = $record->fresh()->paid_at;

    $this->actingAs($user)->patch(route('records.pay', $record))
        ->assertSessionHasErrors('payment');

    expect($record->fresh()->paid_at->equalTo($paidAt))->toBeTrue();
});

test('unpaid payment can be cancelled and record stays unpaid', function () {
    $workout = paymentWorkout();
    $user = paymentUser();
    $record = paymentRecord($workout, $user);

    $this->actingAs($user)->patch(route('records.cancel-payment', $record))->assertRedirect();

    expect($record->fresh()->payment_status)->toBe(PaymentStatus::Cancelled);
});

test('user cannot pay for another user record', function () {
    $record = paymentRecord(paymentWorkout(), paymentUser());

    $this->actingAs(paymentUser())->patch(route('records.pay', $record))->assertForbidden();

    expect($record->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('record of a cancelled workout cannot be paid', function () {
    $workout = paymentWorkout();
    $workout->update(['cancelled_at' => now()]);
    $user = paymentUser();
    $record = paymentRecord($workout, $user);

    $this->actingAs($user)->patch(route('records.pay', $record))
        ->assertSessionHasErrors('payment');

    expect($record->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('payment status is shown on the records page', function () {
    $workout = paymentWorkout();
    $user = paymentUser();
    $record = paymentRecord($workout, $user);

    $this->actingAs($user)->get(route('records.index'))
        ->assertOk()
        ->assertSee('Не оплачено');

    $this->actingAs($user)->patch(route('records.pay', $record));

    $this->actingAs($user)->get(route('records.index'))
        ->assertOk()
        ->assertSee('Оплачено');
});
