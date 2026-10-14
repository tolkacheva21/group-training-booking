@extends('app')

@section('title', $user->name)

@section('content')
    <h1>{{ $user->name }}</h1>

    <dl>
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

    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">Изменить</a>
    <a href="{{ route('users.index') }}" class="btn">К списку</a>
@endsection
