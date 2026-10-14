@extends('app')

@section('title', 'Комната №' . $room->number)

@section('content')
    <h1>Комната №{{ $room->number }}</h1>

    <dl>
        <dt>ID</dt>
        <dd>{{ $room->id }}</dd>

        <dt>Номер</dt>
        <dd>{{ $room->number }}</dd>

        <dt>Вместимость</dt>
        <dd>{{ $room->capacity }}</dd>
    </dl>

    <a href="{{ route('rooms.edit', $room) }}" class="btn btn-primary">Изменить</a>
    <a href="{{ route('rooms.index') }}" class="btn">К списку</a>
@endsection
