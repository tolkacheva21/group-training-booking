@extends('app')

@section('content')
    <div class="card">
        <h1>Запись на тренировку</h1>

        <p class="muted">
            Выберите тренировку в списке доступных и нажмите «Записаться» рядом с ней.
        </p>

        <div class="form-actions">
            <a class="button" href="{{ route('records.index') }}">
                К доступным тренировкам
            </a>
        </div>
    </div>
@endsection
