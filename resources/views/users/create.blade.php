@extends('app')

@section('title', 'Новый пользователь')

@section('content')
    <h1>Новый пользователь</h1>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        @include('users.form')
    </form>
@endsection
