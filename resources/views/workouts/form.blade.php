<label for="trainer_id">Тренер</label>

<select id="trainer_id" name="trainer_id" required>
    <option value="">Выберите тренера</option>

    @foreach ($trainers as $trainer)
        <option value="{{ $trainer->id }}"
            @selected(old('trainer_id', $workout->trainer_id ?? '') == $trainer->id)>
            {{ $trainer->name }}
        </option>
    @endforeach
</select>

<label for="room_id">Зал</label>

<select id="room_id" name="room_id" required>
    <option value="">Выберите зал</option>

    @foreach ($rooms as $room)
        <option value="{{ $room->id }}"
            @selected(old('room_id', $workout->room_id ?? '') == $room->id)>
            Зал №{{ $room->number }} — {{ $room->capacity }} мест
        </option>
    @endforeach
</select>

<label for="workout_start">Начало тренировки</label>

<input
    id="workout_start"
    type="datetime-local"
    name="workout_start"
    required
    value="{{ old('workout_start', isset($workout) ? $workout->workout_start->format('Y-m-d\TH:i') : '') }}"
>

<label for="workout_end">Окончание тренировки</label>

<input
    id="workout_end"
    type="datetime-local"
    name="workout_end"
    required
    value="{{ old('workout_end', isset($workout) ? $workout->workout_end->format('Y-m-d\TH:i') : '') }}"
>

<label for="inventory_id">Инвентарь</label>

<select id="inventory_id" name="inventory_id">
    <option value="">Без инвентаря</option>

    @foreach ($inventories as $inventory)
        <option value="{{ $inventory->id }}"
            @selected(old('inventory_id', $workout->inventory_id ?? '') == $inventory->id)>
            {{ $inventory->name }} — всего {{ $inventory->quantity }} шт.
        </option>
    @endforeach
</select>

<label for="inventory_quantity">Количество инвентаря</label>

<input
    id="inventory_quantity"
    type="number"
    name="inventory_quantity"
    min="1"
    value="{{ old('inventory_quantity', $workout->inventory_quantity ?? '') }}"
>

<div class="form-actions">
    <button type="submit">Сохранить</button>

    <a class="button" href="{{ route('workouts.index') }}">
        Отмена
    </a>
</div>