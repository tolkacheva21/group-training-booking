@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Тренировка №{{ $workout->id }} — йога</h1>

            <a class="button button-edit"
               href="{{ route('workouts.edit', $workout) }}">
                Изменить
            </a>
        </div>

        <p>Тренер: {{ $workout->trainer->name }}</p>

        <p>Зал: №{{ $workout->room->number }}</p>

        <p>
            Начало:
            {{ $workout->workout_start->format('d.m.Y H:i') }}
        </p>

        <p>
            Окончание:
            {{ $workout->workout_end->format('d.m.Y H:i') }}
        </p>

        <p>
            Участники:
            {{ $workout->records->count() }}
            / {{ $workout->room->capacity }}
        </p>

        <h2>Записи на тренировку</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Действие</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($workout->records as $record)
                        <tr>
                            <td>{{ $record->user->name }}</td>

                            <td>
                                <a class="button"
                                   href="{{ route('records.show', $record) }}">
                                    Открыть запись
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                На тренировку пока никто не записан.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            @if ($workout->workout_start->isFuture())
                @if ($workout->records->count() < $workout->room->capacity)
                    <a class="button"
                       href="{{ route('records.create', ['workout_id' => $workout->id]) }}">
                        Записаться
                    </a>
                @else
                    <p>Свободных мест нет.</p>
                @endif
            @else
                <p>Запись на эту тренировку закрыта.</p>
            @endif

            <a class="button" href="{{ route('workouts.index') }}">
                К списку тренировок
            </a>
        </div>
    </div>
@endsection