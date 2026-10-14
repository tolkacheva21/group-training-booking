<div class="field">
    <label for="number">Номер комнаты</label>
    <input type="number" id="number" name="number" value="{{ old('number', $room->number ?? '') }}" required>
    @error('number') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="capacity">Вместимость</label>
    <input type="number" id="capacity" name="capacity" min="1" value="{{ old('capacity', $room->capacity ?? '') }}" required>
    @error('capacity') <div class="error">{{ $message }}</div> @enderror
</div>

<button type="submit" class="btn btn-primary">Сохранить</button>
<a href="{{ route('rooms.index') }}" class="btn">Отмена</a>
