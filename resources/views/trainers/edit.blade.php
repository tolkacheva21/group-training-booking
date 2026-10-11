@extends('app')

@section('content')
    <div class="card">
        <h1>Редактирование тренера</h1>

        <form action="{{ route('trainers.update', $trainer) }}" method="POST">
            @csrf
            @method('PUT')

            @include('trainers.form')
        </form>
    </div>
@endsection