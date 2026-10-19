@extends('app')

@section('content')
    <div class="card">
        <h1>{{ $user->name }}</h1>

        <dl class="details">
            <dt>ID</dt>
            <dd>{{ $user->id }}</dd>

            <dt>Email</dt>
            <dd>{{ $user->email }}</dd>

            <dt>Телефон</dt>
            <dd>{{ $user->phone_number }}</dd>

            <dt>Администратор</dt>
            <dd>{{ $user->is_admin ? 'Да' : 'Нет' }}</dd>

            <dt>Создан</dt>
            <dd>{{ $user->created_at?->format('d.m.Y H:i') }}</dd>
        </dl>

        <div class="actions">
            <a href="{{ route('users.edit', $user) }}" class="button button-edit">Изменить</a>
            <a href="{{ route('users.index') }}" class="button">К списку пользователей</a>
        </div>
    </div>
@endsection
