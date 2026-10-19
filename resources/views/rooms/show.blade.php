@extends('app')

@section('content')
    <div class="card">
        <h1>Комната №{{ $room->number }}</h1>

        <dl class="details">
            <dt>ID</dt>
            <dd>{{ $room->id }}</dd>

            <dt>Номер</dt>
            <dd>{{ $room->number }}</dd>

            <dt>Вместимость</dt>
            <dd>{{ $room->capacity }}</dd>
        </dl>

        <div class="actions">
            <a href="{{ route('rooms.edit', $room) }}" class="button button-edit">Изменить</a>
            <a href="{{ route('rooms.index') }}" class="button">К списку комнат</a>
        </div>
    </div>
@endsection
