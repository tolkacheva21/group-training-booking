@extends('app')

@section('title', 'Редактирование комнаты')

@section('content')
    <h1>Редактирование комнаты №{{ $room->number }}</h1>

    <form action="{{ route('rooms.update', $room) }}" method="POST">
        @csrf
        @method('PUT')
        @include('rooms.form')
    </form>
@endsection
