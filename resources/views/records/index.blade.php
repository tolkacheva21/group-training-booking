@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Записи на тренировки</h1>

            <a class="button" href="{{ route('records.create') }}">
                Создать запись
            </a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>№ записи</th>
                        <th>Пользователь</th>
                        <th>Начало тренировки</th>
                        <th>Тренер</th>
                        <th>Зал</th>
                        <th>Действия</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($records as $record)
                        <tr>
                            <td>{{ $record->id }}</td>

                            <td>{{ $record->user->name }}</td>

                            <td>
                                {{ $record->workout->workout_start->format('d.m.Y H:i') }}
                            </td>

                            <td>{{ $record->workout->trainer->name }}</td>

                            <td>№{{ $record->workout->room->number }}</td>

                            <td>
                                <a class="button"
                                   href="{{ route('records.show', $record) }}">
                                    Открыть
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                Записей пока нет.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection