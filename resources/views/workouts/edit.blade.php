@extends('app')

@section('content')
    <div class="card">
        <h1>Редактирование тренировки</h1>

        <form action="{{ route('workouts.update', $workout) }}" method="POST">
            @csrf
            @method('PUT')

            @include('workouts.form')
        </form>
    </div>
@endsection