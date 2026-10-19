@extends('app')

@section('content')
    <div class="card">
        <div class="top-line">
            <h1>Пользователи</h1>

            <p><a href="{{ route('users.create') }}" class="button">+ Добавить пользователя</a></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Телефон</th>
                    <th>Админ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone_number }}</td>
                        <td>{{ $user->is_admin ? 'Да' : 'Нет' }}</td>
                        <td>
                            <a href="{{ route('users.show', $user) }}" class="button">Просмотр</a>
                            <a href="{{ route('users.edit', $user) }}" class="button button-edit">Изменить</a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                onsubmit="return confirm('Удалить пользователя?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Пользователей пока нет</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
