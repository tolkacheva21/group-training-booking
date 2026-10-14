@extends('app')

@section('title', 'Новая комната')

@section('content')
    <h1>Новая комната</h1>

    <form action="{{ route('rooms.store') }}" method="POST">
        @csrf
        @include('rooms.form')
    </form>
@endsection
