@extends('app')

@section('title', 'Редактирование пользователя')

@section('content')
    <h1>Редактирование: {{ $user->name }}</h1>

    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        @include('users.form')
    </form>
@endsection
