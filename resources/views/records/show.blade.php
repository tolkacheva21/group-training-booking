@extends('app')

@section('content')
    <div class="card">
        <h1>Запись №{{ $record->id }}</h1>

        <p>Статус: {{ $record->status->title }}</p>

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
            @can('view', $record->workout)
                <a class="button"
                   href="{{ route('workouts.show', $record->workout) }}">
                    Открыть тренировку
                </a>
            @endcan

            <a class="button" href="{{ route('records.index') }}">
                К списку записей
            </a>

            @can('confirm', $record)
                <form class="inline"
                      action="{{ route('records.confirm', $record) }}"
                      method="POST">
                    @csrf
                    @method('PATCH')

                    <button type="submit">Подтвердить</button>
                </form>
            @endcan

            @can('reject', $record)
                <form class="inline"
                      action="{{ route('records.reject', $record) }}"
                      method="POST">
                    @csrf
                    @method('PATCH')

                    <button class="button-danger"
                            type="submit"
                            onclick="return confirm('Отклонить запись?')">
                        Отклонить
                    </button>
                </form>
            @endcan

            @can('delete', $record)
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
            @endcan
        </div>
    </div>
@endsection