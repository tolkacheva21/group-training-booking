@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Тренировки по йоге</h1>

            @can('create', App\Models\Workout::class)
                <a class="button" href="{{ route('workouts.create') }}">
                    Добавить тренировку
                </a>
            @endcan
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Начало</th>
                        <th>Окончание</th>
                        <th>Тренер</th>
                        <th>Зал</th>
                        <th>Стоимость</th>
                        <th>Статус</th>
                        <th>Участники</th>
                        <th>Действия</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($workouts as $workout)
                        <tr>
                            <td>
                                {{ \App\Support\DisplayTime::toLocal($workout->workout_start)->format('d.m.Y H:i') }}
                            </td>

                            <td>
                                {{ \App\Support\DisplayTime::toLocal($workout->workout_end)->format('d.m.Y H:i') }}
                            </td>

                            <td>{{ $workout->trainer->name }}</td>

                            <td>№{{ $workout->room->number }}</td>

                            <td>{{ number_format((float) $workout->price, 2, ',', ' ') }} ₽</td>

                            <td>{{ $workout->statusLabel() }}</td>

                            <td>
                                {{ $workout->current_participants }}
                                / {{ $workout->room->capacity }}
                            </td>

                            <td>
                                <a class="button"
                                   href="{{ route('workouts.show', $workout) }}">
                                    Открыть
                                </a>

                                @can('update', $workout)
                                    <a class="button button-edit"
                                       href="{{ route('workouts.edit', $workout) }}">
                                        Изменить
                                    </a>
                                @endcan

                                @can('delete', $workout)
                                    <form class="inline"
                                          action="{{ route('workouts.destroy', $workout) }}"
                                          method="POST">
                                        @csrf
                                        @method('DELETE')

                                        <button class="button-danger"
                                                type="submit"
                                                onclick="return confirm('Удалить тренировку и все записи на неё?')">
                                            Удалить
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                Тренировок пока нет.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
