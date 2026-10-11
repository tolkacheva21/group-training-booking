<label for="name">Имя тренера</label>
<input
    id="name"
    type="text"
    name="name"
    required
    value="{{ old('name', $trainer->name ?? '') }}"
>

<label for="phone_number">Телефон</label>
<input
    id="phone_number"
    type="text"
    name="phone_number"
    required
    placeholder="+7 999 123-45-67"
    value="{{ old('phone_number', $trainer->phone_number ?? '') }}"
>

<label for="email">E-mail</label>
<input
    id="email"
    type="email"
    name="email"
    required
    placeholder="trainer@example.com"
    value="{{ old('email', $trainer->email ?? '') }}"
>

<br><br>

<button type="submit">Сохранить</button>

<a class="button" href="{{ route('trainers.index') }}">
    Отмена
</a>