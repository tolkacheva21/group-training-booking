@extends('app')

@section('content')
    <div class="card">
        <h1>Запись на тренировку</h1>

        @if ($users->isEmpty())
            <p>В базе пока нет пользователей.</p>
        @elseif ($workouts->isEmpty())
            <p>Нет доступных для выбора будущих тренировок.</p>
        @else
            <form action="{{ route('records.store') }}" method="POST">
                @csrf

                <label for="user_id">
                    Пользователь — временный выбор для проверки
                </label>

                <select id="user_id" name="user_id" required>
                    <option value="">Выберите пользователя</option>

                    @foreach ($users as $user)
                        <option value="{{ $user->id }}"
                            @selected(old('user_id') == $user->id)>
                            {{ $user->name }} — {{ $user->email }}
                        </option>
                    @endforeach
                </select>

                <label for="workout_id">Тренировка</label>

                <select id="workout_id" name="workout_id" required>
                    <option value="">Выберите тренировку</option>

                    @foreach ($workouts as $workout)
                        <option value="{{ $workout->id }}"
                            @selected(old('workout_id', request('workout_id')) == $workout->id)>
                            {{ $workout->workout_start->format('d.m.Y H:i') }}
                            — {{ $workout->trainer->name }}
                            — зал №{{ $workout->room->number }}
                            — занято {{ $workout->current_participants }}/{{ $workout->room->capacity }}
                        </option>
                    @endforeach
                </select>

                <div class="form-actions">
                    <button type="submit">Записаться</button>

                    <a class="button" href="{{ route('records.index') }}">
                        Отмена
                    </a>
                </div>
            </form>
        @endif
    </div>
@endsection