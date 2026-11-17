<?php

use App\Models\Role;
use App\Models\User;

test('header shows only the custom pages available to the user role', function () {
    $manager = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'manager'])->id,
    ]);

    $this->actingAs($manager)->get(route('rooms.index'))
        ->assertOk()
        ->assertSee(route('rooms.index'), false)
        ->assertDontSee(route('users.index'), false);
});

test('routes are restricted by role', function (string $role, string $routeName, int $status) {
    $user = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => $role])->id,
    ]);

    $this->actingAs($user)->get(route($routeName))->assertStatus($status);
})->with([
    'admin users' => ['admin', 'users.index', 200],
    'admin rooms' => ['admin', 'rooms.index', 200],
    'manager users' => ['manager', 'users.index', 403],
    'manager workouts' => ['manager', 'workouts.index', 200],
    'trainer trainers' => ['trainer', 'trainers.index', 200],
    'trainer rooms' => ['trainer', 'rooms.index', 403],
    'trainer workouts' => ['trainer', 'workouts.index', 403],
    'user records' => ['user', 'records.index', 200],
    'user trainers' => ['user', 'trainers.index', 403],
    'user profile' => ['user', 'profile.edit', 200],
]);

test('admin can open user create and edit forms with role options', function () {
    $admin = User::factory()->create([
        'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
    ]);

    $this->actingAs($admin)->get(route('users.create'))->assertOk()->assertSee('admin');
    $this->actingAs($admin)->get(route('users.edit', $admin))->assertOk()->assertSee('admin');
});

test('root redirects guests to login and authenticated users to the start page', function () {
    $this->get('/')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
});
