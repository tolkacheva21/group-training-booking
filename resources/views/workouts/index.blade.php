@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Тренировки по йоге</h1>

            <a class="button" href="{{ route('workouts.create') }}">
                Добавить тренировку
            </a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Начало</th>
                        <th>Окончание</th>
                        <th>Тренер</th>
                        <th>Зал</th>
                        <th>Участники</th>
                        <th>Действия</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($workouts as $workout)
                        <tr>
                            <td>
                                {{ $workout->workout_start->format('d.m.Y H:i') }}
                            </td>

                            <td>
                                {{ $workout->workout_end->format('d.m.Y H:i') }}
                            </td>

                            <td>{{ $workout->trainer->name }}</td>

                            <td>№{{ $workout->room->number }}</td>

                            <td>
                                {{ $workout->current_participants }}
                                / {{ $workout->room->capacity }}
                            </td>

                            <td>
                                <a class="button"
                                   href="{{ route('workouts.show', $workout) }}">
                                    Открыть
                                </a>

                                <a class="button button-edit"
                                   href="{{ route('workouts.edit', $workout) }}">
                                    Изменить
                                </a>

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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                Тренировок пока нет.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection