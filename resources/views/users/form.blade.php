<div class="field">
    <label for="name">Имя</label>
    <input type="text" id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    @error('email') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="phone_number">Телефон</label>
    <input type="text" id="phone_number" name="phone_number"
           value="{{ old('phone_number', $user->phone_number ?? '') }}" required>
    @error('phone_number') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="password">Пароль {{ isset($user) ? '(оставьте пустым, чтобы не менять)' : '' }}</label>
    <input type="password" id="password" name="password" {{ isset($user) ? '' : 'required' }}>
    @error('password') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="password_confirmation">Повторите пароль</label>
    <input type="password" id="password_confirmation" name="password_confirmation">
</div>

<button type="submit" class="button">Сохранить</button>
<a href="{{ route('users.index') }}" class="button">Отмена</a>
