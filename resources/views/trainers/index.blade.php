@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Тренеры</h1>

            <a class="button" href="{{ route('trainers.create') }}">
                + Добавить тренера
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Телефон</th>
                    <th>E-mail</th>
                    <th>Действия</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($trainers as $trainer)
                    <tr>
                        <td>{{ $trainer->name }}</td>
                        <td>{{ $trainer->phone_number }}</td>
                        <td>{{ $trainer->email }}</td>
                        <td>
                            <a class="button"
                               href="{{ route('trainers.show', $trainer) }}">
                                Открыть
                            </a>

                            <a class="button button-edit"
                               href="{{ route('trainers.edit', $trainer) }}">
                                Изменить
                            </a>

                            <form class="inline"
                                  action="{{ route('trainers.destroy', $trainer) }}"
                                  method="POST">
                                @csrf
                                @method('DELETE')

                                <button class="button-danger"
                                        type="submit"
                                        onclick="return confirm('Удалить тренера?')">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            Тренеров ещё нет. Добавьте первого тренера.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection