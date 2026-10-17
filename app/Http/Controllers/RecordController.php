<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordController extends Controller
{
    public function index()
    {
        $records = Record::with(['user','workout.trainer', 'workout.room',])->get();

        return view('records.index', compact('records'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        $workouts = Workout::with(['trainer', 'room'])
            ->where('workout_start', '>', now())
            ->orderBy('workout_start')
            ->get();

        return view('records.create', compact('users', 'workouts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'workout_id' => ['required', 'integer', 'exists:workouts,id',],
        ], [
            'user_id.required' => 'Выберите пользователя.',
            'user_id.exists' => 'Пользователь не найден.',
            'workout_id.required' => 'Выберите тренировку.',
            'workout_id.exists' => 'Тренировка не найдена.',
        ]);

        DB::transaction(function () use ($data) {
            $workout = Workout::with('room') ->findOrFail($data['workout_id']);

            if ($workout->workout_start->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Тренировка уже началась.',
                ]);
            }

            $alreadyExists = Record::where('workout_id', $workout->id)
                ->where('user_id', $data['user_id'])
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Этот пользователь уже записан '
                        . 'на выбранную тренировку.',
                ]);
            }

            $participants = $workout->records()->count();

            if ($participants >= $workout->room->capacity) {
                throw ValidationException::withMessages([
                    'workout_id' => 'На тренировке нет свободных мест.',
                ]);
            }

            Record::create($data);

            $workout->current_participants = $participants + 1;
            $workout->save();
        });

        return redirect()->route('records.index');
    }

    public function show(Record $record)
    {
        $record->load([ 'user', 'workout.trainer', 'workout.room',]);

        return view('records.show', compact('record'));
    }

    public function destroy(Record $record)
    {
        DB::transaction(function () use ($record) {
            $workout = Workout::findOrFail($record->workout_id);

            $record->delete();

            $workout->current_participants = $workout->records()->count();
            $workout->save();
        });

        return redirect()->route('records.index');
    }
}