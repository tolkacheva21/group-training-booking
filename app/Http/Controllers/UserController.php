<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    // READ all users
    public function index(): View {
        $users = User::all();

        return view('users.index', compact('users'));
    }

    // READ single user
    public function show(User $user): View {
        return view('users.show', compact('user'));
    }

    public function create(): View {
        return view('users.create');
    }

    // CREATE user
    public function store(Request $request): RedirectResponse {
        $userValid = $request -> validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:15', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($userValid);

        return redirect() -> route('users.index') -> with('success','Пользователь успешно создан');
    }

    public function edit(User $user): View {
        return view('users.edit', compact('user'));
    }

    // UPDATE user
    public function update(Request $request, User $user): RedirectResponse {
        $userValid = $request -> validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['required', 'string', 'max:15', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // если пароль не введён, не трогаем старый
        if (empty($userValid['password'])) {
            unset($userValid['password']);
        }

        $user -> update($userValid);

        return redirect() -> route('users.index') -> with('success','Данные пользователя успешно изменены');
    }

    // DELETE user
    public function destroy(User $user): RedirectResponse {
        $user -> delete();

        return redirect() -> route('users.index') -> with('success','Пользователь успешно удален');
    }
}
