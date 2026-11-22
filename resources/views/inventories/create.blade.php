@extends('app')

@section('title', 'Новый инвентарь')

@section('content')
    <h1>Новый инвентарь</h1>

    <form action="{{ route('inventories.store') }}" method="POST">
        @csrf
        @include('inventories.form')
    </form>
@endsection
