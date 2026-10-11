@extends('app')

@section('content')
    <div class="card">
        <h1>Добавление тренера</h1>

        <form action="{{ route('trainers.store') }}" method="POST">
            @csrf

            @include('trainers.form')
        </form>
    </div>
@endsection