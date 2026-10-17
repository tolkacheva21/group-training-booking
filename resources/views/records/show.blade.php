@extends('app')

@section('content')
    <div class="card">
        <h1>Запись №{{ $record->id }}</h1>

        <h2>Пользователь</h2>

        <p>Имя: {{ $record->user->name }}</p>
        <p>E-mail: {{ $record->user->email }}</p>

        <h2>Тренировка по йоге</h2>

        <p>Тренер: {{ $record->workout->trainer->name }}</p>

        <p>Зал: №{{ $record->workout->room->number }}</p>

        <p>
            Начало:
            {{ $record->workout->workout_start->format('d.m.Y H:i') }}
        </p>

        <p>
            Окончание:
            {{ $record->workout->workout_end->format('d.m.Y H:i') }}
        </p>

        <div class="form-actions">
            <a class="button"
               href="{{ route('workouts.show', $record->workout) }}">
                Открыть тренировку
            </a>

            <a class="button" href="{{ route('records.index') }}">
                К списку записей
            </a>

            <form class="inline"
                  action="{{ route('records.destroy', $record) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button class="button-danger"
                        type="submit"
                        onclick="return confirm('Отменить запись на тренировку?')">
                    Отменить запись
                </button>
            </form>
        </div>
    </div>
@endsection