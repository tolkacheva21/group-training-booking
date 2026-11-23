<?php

use App\Models\User;

test('login page is fully translated into Russian', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Войти')
        ->assertSee('Пароль')
        ->assertSee('Запомнить меня')
        ->assertSee('Забыли пароль?')
        ->assertSee('Зарегистрироваться')
        ->assertDontSee('Log in')
        ->assertDontSee('Remember me');
});

test('registration page is fully translated into Russian', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Имя')
        ->assertSee('Номер телефона')
        ->assertSee('Подтвердите пароль')
        ->assertSee('Уже зарегистрированы?')
        ->assertDontSee('Phone Number')
        ->assertDontSee('Already registered?');
});

test('profile page is fully translated into Russian', function () {
    $this->actingAs(User::factory()->create())->get('/profile')
        ->assertOk()
        ->assertSee('Профиль')
        ->assertSee('Информация профиля')
        ->assertSee('Изменение пароля')
        ->assertSee('Удалить учётную запись')
        ->assertDontSee('Profile Information')
        ->assertDontSee('Delete Account');
});

test('login validation error is translated into Russian', function () {
    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors(['email' => 'Неверный адрес электронной почты или пароль.']);
});
