<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $inventories = Inventory::with(['workouts.trainer', 'workouts.room'])
            ->orderBy('name')
            ->get();

        return view('inventories.index', compact('inventories'));
    }

    public function create(): View
    {
        return view('inventories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), $this->messages());

        Inventory::create($data);

        return redirect()->route('inventories.index')->with('success', 'Инвентарь добавлен');
    }

    public function edit(Inventory $inventory): View
    {
        return view('inventories.edit', compact('inventory'));
    }

    public function update(Request $request, Inventory $inventory): RedirectResponse
    {
        $data = $request->validate($this->rules($inventory), $this->messages());

        // Максимум одновременно занятого инвентаря на будущих (не отменённых) тренировках
        $maxOccupied = $inventory->workouts()
            ->notCancelled()
            ->where('workout_end', '>', now())
            ->get()
            ->map(fn ($workout) => $inventory->occupiedBetween($workout->workout_start, $workout->workout_end))
            ->max() ?? 0;

        if ($data['quantity'] < $maxOccupied) {
            throw ValidationException::withMessages([
                'quantity' => "Нельзя указать меньше {$maxOccupied}: столько занято на предстоящих тренировках.",
            ]);
        }

        $inventory->update($data);

        return redirect()->route('inventories.index')->with('success', 'Инвентарь изменён');
    }

    public function destroy(Inventory $inventory): RedirectResponse
    {
        if ($inventory->workouts()->notCancelled()->where('workout_end', '>', now())->exists()) {
            return redirect()->route('inventories.index')
                ->withErrors(['inventory' => "Инвентарь «{$inventory->name}» назначен на предстоящие тренировки."]);
        }

        $inventory->delete();

        return redirect()->route('inventories.index')->with('success', 'Инвентарь удалён');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Inventory $inventory = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('inventories', 'name')->ignore($inventory?->id)],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'Укажите наименование.',
            'name.unique' => 'Инвентарь с таким наименованием уже есть.',
            'quantity.required' => 'Укажите количество.',
            'quantity.min' => 'Количество не может быть отрицательным.',
        ];
    }
}
