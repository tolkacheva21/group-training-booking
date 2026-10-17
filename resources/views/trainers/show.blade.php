@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>{{ $trainer->name }}</h1>

            <a class="button button-edit"
               href="{{ route('trainers.edit', $trainer) }}">
                Изменить
            </a>
        </div>

        <p><strong>Телефон:</strong> {{ $trainer->phone_number }}</p>
        <p><strong>E-mail:</strong> {{ $trainer->email }}</p>

        <br>

        <a class="button" href="{{ route('trainers.index') }}">
            К списку тренеров
        </a>
    </div>
@endsection