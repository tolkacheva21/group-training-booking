@php
    $canManage = auth()->user()->can('manage', App\Models\Workout::class);
    $currentTrainerId = old('trainer_id', $workout->trainer_id ?? auth()->id());
@endphp

@if ($canManage)
    <label for="trainer_id">Тренер</label>

    <select id="trainer_id" name="trainer_id" required>
        <option value="">Выберите тренера</option>

        @foreach ($trainers as $trainer)
            <option value="{{ $trainer->id }}"
                @selected($currentTrainerId == $trainer->id)>
                {{ $trainer->name }}
            </option>
        @endforeach
    </select>
@else
    {{-- Тренер создаёт и редактирует только собственные тренировки --}}
    <input type="hidden" name="trainer_id" value="{{ auth()->id() }}">

    <p class="muted">Тренер: {{ auth()->user()->name }}</p>
@endif

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

<label for="workout_start">Дата и время начала</label>

<input
    id="workout_start"
    type="datetime-local"
    name="workout_start"
    required
    value="{{ old('workout_start', isset($workout) ? \App\Support\DisplayTime::toLocal($workout->workout_start)->format('Y-m-d\TH:i') : '') }}"
>

<label for="workout_end">Дата и время окончания</label>

<input
    id="workout_end"
    type="datetime-local"
    name="workout_end"
    required
    value="{{ old('workout_end', isset($workout) ? \App\Support\DisplayTime::toLocal($workout->workout_end)->format('Y-m-d\TH:i') : '') }}"
>

<label for="price">Стоимость, ₽</label>

<input
    id="price"
    type="number"
    name="price"
    min="0"
    step="0.01"
    value="{{ old('price', $workout->price ?? 0) }}"
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
