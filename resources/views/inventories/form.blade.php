<div class="field">
    <label for="name">Наименование</label>
    <input type="text" id="name" name="name" value="{{ old('name', $inventory->name ?? '') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="field">
    <label for="quantity">Количество</label>
    <input type="number" id="quantity" name="quantity" min="0" value="{{ old('quantity', $inventory->quantity ?? '') }}" required>
    @error('quantity') <div class="error">{{ $message }}</div> @enderror
</div>

<button type="submit" class="button">Сохранить</button>
<a href="{{ route('inventories.index') }}" class="button">Отмена</a>
