@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Комнаты</h1>

            <p><a href="{{ route('rooms.create') }}" class="button">+ Добавить комнату</a></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Номер</th>
                    <th>Вместимость</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr>
                        <td>{{ $room->id }}</td>
                        <td>{{ $room->number }}</td>
                        <td>{{ $room->capacity }}</td>
                        <td>
                            <a href="{{ route('rooms.show', $room) }}" class="button">Просмотр</a>
                            <a href="{{ route('rooms.edit', $room) }}" class="button button-edit">Изменить</a>
                            <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="inline"
                                onsubmit="return confirm('Удалить комнату?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Комнат пока нет</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
