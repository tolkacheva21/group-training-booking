@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Инвентарь</h1>

            <p><a href="{{ route('inventories.create') }}" class="button">+ Добавить инвентарь</a></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Наименование</th>
                    <th>Количество</th>
                    <th>Занято сейчас</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventories as $inventory)
                    <tr>
                        <td>{{ $inventory->id }}</td>
                        <td>{{ $inventory->name }}</td>
                        <td>{{ $inventory->quantity }}</td>
                        <td>{{ $inventory->occupiedNow() }}</td>
                        <td>
                            <a href="{{ route('inventories.edit', $inventory) }}" class="button button-edit">Изменить</a>
                            <form action="{{ route('inventories.destroy', $inventory) }}" method="POST" class="inline"
                                onsubmit="return confirm('Удалить инвентарь?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Инвентаря пока нет</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
