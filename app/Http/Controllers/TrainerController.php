<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trainers = Trainer::all();
        return view('trainers.index', compact('trainers'));
    }

    /**
     * форма создания нового тренера
     */
    public function create()
    {
        return view('trainers.create');
    }

    /**
     * сохранение тренера в базу данных
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:trainers,phone_number'],
            'email' => ['required', 'email', 'max:100', 'unique:trainers,email'],
        ], [
            'name.required' => 'Введите имя тренера.',
            'phone_number.required' => 'Введите номер телефона.',
            'phone_number.unique' => 'Такой номер телефона уже существует.',
            'email.required' => 'Введите e-mail.',
            'email.email' => 'Введите корректный e-mail.',
            'email.unique' => 'Такой e-mail уже существует.',
        ]);

        Trainer::create($data);

        // Перенаправление на страницу со списком тренеров с сообщением об успешном добавлении
        return redirect() ->route('trainers.index');
    }

    /**
     * вывести одного тренера
     */
    public function show(Trainer $trainer)
    {
        return view('trainers.show', compact('trainer'));
    }

    /**
     * форма изменения
     */
    public function edit(Trainer $trainer)
    {
        return view('trainers.edit', compact('trainer'));
    }

    /**
     * сохранение изменений
     */
    public function update(Request $request, Trainer $trainer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:30', 
            Rule::unique('trainers', 'phone_number')->ignore($trainer->id),],
            'email' => ['required', 'email', 'max:100', 
            Rule::unique('trainers', 'email')->ignore($trainer->id),],
        ], [
            'name.required' => 'Введите имя тренера.',
            'phone_number.required' => 'Введите номер телефона.',
            'phone_number.unique' => 'Такой номер телефона уже существует.',
            'email.required' => 'Введите e-mail.',
            'email.email' => 'Введите корректный e-mail.',
            'email.unique' => 'Такой e-mail уже существует.',
        ]);

        $trainer->update($data);

        return redirect() ->route('trainers.index');
    }

    /**
     * удаление тренера
     */
    public function destroy(Trainer $trainer)
    {
        $trainer->delete();

        return redirect() ->route('trainers.index');
    }
}
