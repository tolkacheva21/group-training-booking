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
                    <th>Всего</th>
                    <th>Занято сейчас</th>
                    <th>Свободно сейчас</th>
                    <th>Ближайшее использование</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventories as $inventory)
                    @php
                        $occupiedNow = $inventory->occupiedNow();
                        $upcoming = $inventory->upcomingWorkouts();
                    @endphp

                    <tr>
                        <td>{{ $inventory->id }}</td>
                        <td>{{ $inventory->name }}</td>
                        <td>{{ $inventory->quantity }}</td>
                        <td>{{ $occupiedNow }}</td>
                        <td>{{ max(0, $inventory->quantity - $occupiedNow) }}</td>
                        <td>
                            @if ($upcoming->isEmpty())
                                Не используется
                            @else
                                <ul class="inventory-usage">
                                    @foreach ($upcoming as $workout)
                                        <li>
                                            {{ \App\Support\DisplayTime::toLocal($workout->workout_start)->format('d.m.Y H:i') }}
                                            — {{ \App\Support\DisplayTime::toLocal($workout->workout_end)->format('H:i') }}
                                            — {{ $workout->inventory_quantity }} шт.
                                            (зал №{{ $workout->room->number }}, {{ $workout->trainer->name }})
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
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
                    <tr><td colspan="7">Инвентаря пока нет</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
