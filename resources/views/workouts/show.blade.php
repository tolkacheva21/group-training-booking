@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Тренировка №{{ $workout->id }} — йога</h1>

            @can('manage', App\Models\Workout::class)
                <a class="button button-edit"
                   href="{{ route('workouts.edit', $workout) }}">
                    Изменить
                </a>
            @endcan
        </div>

        <p>Тренер: {{ $workout->trainer->name }}</p>

        <p>Зал: №{{ $workout->room->number }}</p>

        <p>
            Инвентарь:
            @if ($workout->inventory)
                {{ $workout->inventory->name }} — {{ $workout->inventory_quantity }} шт.
            @else
                не нужен
            @endif
        </p>

        <p>
            Начало:
            {{ $workout->workout_start->format('d.m.Y H:i') }}
        </p>

        <p>
            Окончание:
            {{ $workout->workout_end->format('d.m.Y H:i') }}
        </p>

        <p>
            Подтверждено:
            {{ $workout->current_participants }}
            / {{ $workout->room->capacity }}
        </p>

        <h2>Записи на тренировку</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>E-mail</th>
                        <th>Телефон</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($workout->records as $record)
                        <tr>
                            <td>{{ $record->user->name }}</td>

                            <td>{{ $record->user->email }}</td>

                            <td>{{ $record->user->phone_number }}</td>

                            <td>{{ $record->status->title }}</td>

                            <td>
                                <a class="button"
                                   href="{{ route('records.show', $record) }}">
                                    Открыть запись
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                На тренировку пока никто не записан.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            @if ($workout->workout_start->isFuture())
                @if ($workout->current_participants < $workout->room->capacity)
                    @can('manage', App\Models\Workout::class)
                        <a class="button"
                           href="{{ route('records.create', ['workout_id' => $workout->id]) }}">
                            Записаться
                        </a>
                    @endcan
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