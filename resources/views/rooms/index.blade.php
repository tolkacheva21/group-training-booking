@extends('app')

@section('title', 'Список комнат')

@section('content')
    <h1>Комнаты</h1>

    <p><a href="{{ route('rooms.create') }}" class="btn btn-primary">Добавить комнату</a></p>

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
                        <a href="{{ route('rooms.show', $room) }}" class="btn">Просмотр</a>
                        <a href="{{ route('rooms.edit', $room) }}" class="btn">Изменить</a>
                        <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="inline"
                              onsubmit="return confirm('Удалить комнату?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Удалить</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Комнат пока нет</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
