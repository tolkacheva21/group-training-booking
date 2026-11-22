<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkoutController extends Controller
{
    public function index(Request $request)
    {
        // Тренер видит только тренировки, которые назначены ему
        $workouts = Workout::with(['trainer', 'room'])
            ->when(
                Gate::denies('manage', Workout::class),
                fn ($query) => $query->where('trainer_id', $request->user()->id)
            )
            ->orderBy('workout_start')
            ->get();

        return view('workouts.index', compact('workouts'));
    }

    public function create()
    {
        $trainers = User::trainers()->orderBy('name')->get();
        $rooms = Room::orderBy('number')->get();
        $inventories = Inventory::orderBy('name')->get();

        return view('workouts.create', compact('trainers', 'rooms', 'inventories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($data) {
            $this->ensureInventoryAvailable($data);

            Workout::create($data);
        });

        return redirect()->route('workouts.index');
    }

    public function show(Workout $workout)
    {
        Gate::authorize('view', $workout);

        $workout->load(['trainer', 'room', 'inventory', 'records.user', 'records.status']);

        return view('workouts.show', compact('workout'));
    }

    public function edit(Workout $workout)
    {
        $trainers = User::trainers()->orderBy('name')->get();
        $rooms = Room::orderBy('number')->get();
        $inventories = Inventory::orderBy('name')->get();

        return view('workouts.edit', compact('workout', 'trainers', 'rooms', 'inventories'));
    }

    public function update(Request $request, Workout $workout)
    {
        $data = $request->validate($this->rules(), $this->messages());

        DB::transaction(function () use ($workout, $data) {
            $currentWorkout = Workout::whereKey($workout->id)->lockForUpdate()->firstOrFail();

            $room = Room::findOrFail($data['room_id']);

            $participants = $currentWorkout->occupyingRecords()->count();

            if ($participants > $room->capacity) {
                throw ValidationException::withMessages([
                    'room_id' => 'В выбранном зале недостаточно мест '
                        .'для уже подтверждённых участников.',
                ]);
            }

            $this->ensureInventoryAvailable($data, $currentWorkout->id);

            $currentWorkout->fill($data);
            $currentWorkout->current_participants = $participants;
            $currentWorkout->save();
        });

        return redirect()->route('workouts.index');
    }

    public function destroy(Workout $workout)
    {
        $workout->delete();

        return redirect()->route('workouts.index');
    }

    /**
     * Тренером может быть только пользователь с ролью trainer.
     *
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'trainer_id' => ['required', 'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role_id', Role::where('name', 'trainer')->select('id'))
                ),
            ],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'workout_start' => ['required', 'date'],
            'workout_end' => ['required', 'date', 'after:workout_start'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'inventory_quantity' => ['nullable', 'required_with:inventory_id', 'integer', 'min:1'],
        ];
    }

    /**
     * Проверяет, что выбранного инвентаря хватает на время тренировки
     * с учётом других тренировок, пересекающихся с ней по времени.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensureInventoryAvailable(array &$data, ?int $exceptWorkoutId = null): void
    {
        if (empty($data['inventory_id'])) {
            $data['inventory_id'] = null;
            $data['inventory_quantity'] = null;

            return;
        }

        $inventory = Inventory::whereKey($data['inventory_id'])->lockForUpdate()->firstOrFail();

        $available = $inventory->availableBetween(
            Carbon::parse($data['workout_start']),
            Carbon::parse($data['workout_end']),
            $exceptWorkoutId,
        );

        if ($data['inventory_quantity'] > $available) {
            throw ValidationException::withMessages([
                'inventory_quantity' => "Недостаточно инвентаря «{$inventory->name}»: "
                    ."на это время свободно {$available} из {$inventory->quantity}.",
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'trainer_id.required' => 'Выберите тренера.',
            'trainer_id.exists' => 'Выбранный тренер не найден.',
            'room_id.required' => 'Выберите зал.',
            'room_id.exists' => 'Выбранный зал не найден.',
            'workout_start.required' => 'Укажите начало тренировки.',
            'workout_end.required' => 'Укажите окончание тренировки.',
            'workout_end.after' => 'Окончание должно быть позже начала.',
            'inventory_id.exists' => 'Выбранный инвентарь не найден.',
            'inventory_quantity.required_with' => 'Укажите количество инвентаря.',
            'inventory_quantity.min' => 'Количество инвентаря должно быть не меньше 1.',
        ];
    }
}
