@extends('app')

@section('content')
    <div class="card">
        <h1>Добавление тренировки</h1>

        <form action="{{ route('workouts.store') }}" method="POST">
            @csrf

            @include('workouts.form')
        </form>
    </div>
@endsection