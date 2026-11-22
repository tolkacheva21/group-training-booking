@extends('app')

@section('title', 'Редактирование инвентаря')

@section('content')
    <h1>Редактирование инвентаря «{{ $inventory->name }}»</h1>

    <form action="{{ route('inventories.update', $inventory) }}" method="POST">
        @csrf
        @method('PUT')
        @include('inventories.form')
    </form>
@endsection
