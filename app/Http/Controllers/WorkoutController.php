<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\Workout;
use App\Services\WorkoutSchedule;
use App\Support\DisplayTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkoutController extends Controller
{
    public function __construct(private readonly WorkoutSchedule $schedule) {}

    public function index(Request $request): View
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

    public function create(): View
    {
        Gate::authorize('create', Workout::class);

        return view('workouts.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Workout::class);

        $this->forceTrainerForTrainerRole($request);

        $data = $request->validate($this->rules(), $this->messages());

        $data = $this->normalize($data, $request);

        DB::transaction(function () use ($data) {
            $this->ensureScheduleAvailable($data);
            $this->ensureInventoryAvailable($data);

            Workout::create($data);
        });

        return redirect()->route('workouts.index')
            ->with('success', 'Тренировка создана.');
    }

    public function show(Workout $workout): View
    {
        Gate::authorize('view', $workout);

        $workout->load(['trainer', 'room', 'inventory', 'records.user', 'records.status']);

        return view('workouts.show', compact('workout'));
    }

    public function edit(Workout $workout): View
    {
        Gate::authorize('update', $workout);

        return view('workouts.edit', ['workout' => $workout] + $this->formData($workout));
    }

    public function update(Request $request, Workout $workout): RedirectResponse
    {
        Gate::authorize('update', $workout);

        $this->forceTrainerForTrainerRole($request);

        $data = $request->validate($this->rules(), $this->messages());

        $data = $this->normalize($data, $request, $workout);

        DB::transaction(function () use ($workout, $data) {
            $currentWorkout = Workout::whereKey($workout->id)->lockForUpdate()->firstOrFail();

            $this->ensureScheduleAvailable($data, $currentWorkout->id);

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

        return redirect()->route('workouts.index')
            ->with('success', 'Тренировка изменена.');
    }

    public function destroy(Workout $workout): RedirectResponse
    {
        Gate::authorize('delete', $workout);

        $workout->delete();

        return redirect()->route('workouts.index')
            ->with('success', 'Тренировка удалена.');
    }

    /**
     * Данные для формы: тренер видит только себя, администратор и менеджер — всех тренеров.
     *
     * @return array<string, mixed>
     */
    private function formData(?Workout $workout = null): array
    {
        $user = request()->user();

        $trainers = Gate::allows('manage', Workout::class)
            ? User::trainers()->orderBy('name')->get()
            : User::trainers()->whereKey($user->id)->get();

        return [
            'trainers' => $trainers,
            'rooms' => Room::orderBy('number')->get(),
            'inventories' => Inventory::orderBy('name')->get(),
        ];
    }

    /**
     * Тренер не может назначить тренировку другому тренеру — только себе.
     * Значение подставляется до валидации, чтобы правило required не срабатывало.
     */
    private function forceTrainerForTrainerRole(Request $request): void
    {
        if (Gate::denies('manage', Workout::class)) {
            $request->merge(['trainer_id' => $request->user()->id]);
        }
    }

    /**
     * Преобразует локальное время формы в UTC и подставляет тренера/цену.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data, Request $request, ?Workout $workout = null): array
    {
        // Тренер всегда создаёт тренировку для себя
        if (Gate::denies('manage', Workout::class)) {
            $data['trainer_id'] = $request->user()->id;
        }

        $data['workout_start'] = DisplayTime::toUtc($data['workout_start']);
        $data['workout_end'] = DisplayTime::toUtc($data['workout_end']);

        $data['price'] = $data['price'] ?? $workout?->price ?? 0;

        if (empty($data['inventory_id'])) {
            $data['inventory_id'] = null;
            $data['inventory_quantity'] = null;
        }

        return $data;
    }

    /**
     * Проверяет, что тренер и зал свободны в указанный интервал.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensureScheduleAvailable(array $data, ?int $exceptWorkoutId = null): void
    {
        $this->schedule->ensureAvailable(
            Carbon::parse($data['workout_start']),
            Carbon::parse($data['workout_end']),
            (int) $data['trainer_id'],
            (int) $data['room_id'],
            $exceptWorkoutId,
        );
    }

    /**
     * Проверяет, что выбранного инвентаря хватает на время тренировки
     * с учётом других тренировок, пересекающихся с ней по времени.
     *
     * @param  array<string, mixed>  $data
     */
    private function ensureInventoryAvailable(array $data, ?int $exceptWorkoutId = null): void
    {
        if (empty($data['inventory_id'])) {
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
            'price' => ['nullable', 'numeric', 'min:0'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'inventory_quantity' => ['nullable', 'required_with:inventory_id', 'integer', 'min:1'],
        ];
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
            'workout_start.required' => 'Укажите дату и время начала тренировки.',
            'workout_end.required' => 'Укажите дату и время окончания тренировки.',
            'workout_end.after' => 'Окончание должно быть позже начала.',
            'price.numeric' => 'Стоимость должна быть числом.',
            'price.min' => 'Стоимость не может быть отрицательной.',
            'inventory_id.exists' => 'Выбранный инвентарь не найден.',
            'inventory_quantity.required_with' => 'Укажите количество инвентаря.',
            'inventory_quantity.min' => 'Количество инвентаря должно быть не меньше 1.',
        ];
    }
}
