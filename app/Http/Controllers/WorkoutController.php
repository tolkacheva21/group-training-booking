<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Trainer;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkoutController extends Controller
{
    public function index()
    {
        $workouts = Workout::with(['trainer', 'room'])->get();
        return view('workouts.index', compact('workouts'));
    }

    public function create()
    {
        $trainers = Trainer::orderBy('name')->get();
        $rooms = Room::orderBy('number')->get();

        return view('workouts.create', compact('trainers', 'rooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => [ 'required', 'integer','exists:trainers,id',],
            'room_id' => ['required', 'integer', 'exists:rooms,id',],
            'workout_start' => ['required', 'date',],
            'workout_end' => ['required', 'date', 'after:workout_start',],
        ], [
            'trainer_id.required' => 'Выберите тренера.',
            'trainer_id.exists' => 'Выбранный тренер не найден.',
            'room_id.required' => 'Выберите зал.',
            'room_id.exists' => 'Выбранный зал не найден.',
            'workout_start.required' => 'Укажите начало тренировки.',
            'workout_end.required' => 'Укажите окончание тренировки.',
            'workout_end.after' => 'Окончание должно быть позже начала.',
        ]);

        Workout::create($data);

        return redirect() ->route('workouts.index');
    }

    public function show(Workout $workout)
    {
        $workout->load(['trainer', 'room', 'records.user']);

        return view('workouts.show', compact('workout'));
    }

    public function edit(Workout $workout)
    {
        $trainers = Trainer::orderBy('name')->get();
        $rooms = Room::orderBy('number')->get();

        return view('workouts.edit', compact('workout', 'trainers', 'rooms'));
    }

    public function update(Request $request, Workout $workout)
    {
        $data = $request->validate([
            'trainer_id' => [ 'required', 'integer','exists:trainers,id',],
            'room_id' => ['required', 'integer', 'exists:rooms,id',],
            'workout_start' => ['required', 'date',],
            'workout_end' => ['required', 'date', 'after:workout_start',],
        ], [
            'trainer_id.required' => 'Выберите тренера.',
            'trainer_id.exists' => 'Выбранный тренер не найден.',
            'room_id.required' => 'Выберите зал.',
            'room_id.exists' => 'Выбранный зал не найден.',
            'workout_start.required' => 'Укажите начало тренировки.',
            'workout_end.required' => 'Укажите окончание тренировки.',
            'workout_end.after' => 'Окончание должно быть позже начала.',
        ]);

        DB::transaction(function () use ($workout, $data) {
            $currentWorkout = Workout::whereKey($workout->id) ->firstOrFail();

            $room = Room::findOrFail($data['room_id']);

            $participants = $currentWorkout->records()->count();

            if ($participants > $room->capacity) {
                throw ValidationException::withMessages([
                    'room_id' => 'В выбранном зале недостаточно мест '
                        . 'для уже записанных участников.',
                ]);
            }

            $currentWorkout->fill($data);
            $currentWorkout->current_participants = $participants;
            $currentWorkout->save();
        });

        return redirect() ->route('workouts.index');
    }

    public function destroy(Workout $workout)
    {
        $workout->delete();

        return redirect() ->route('workouts.index');
    }
}