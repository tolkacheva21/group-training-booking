<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Status;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        $records = Record::with(['user', 'status', 'workout.trainer', 'workout.room'])
            ->when(
                Gate::denies('viewAll', Record::class),
                fn ($query) => $query->where('user_id', $request->user()->id)
            )
            ->get();

        return view('records.index', compact('records'));
    }

    public function create()
    {
        $workouts = Workout::with(['trainer', 'room'])
            ->where('workout_start', '>', now())
            ->orderBy('workout_start')
            ->get();

        return view('records.create', compact('workouts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'workout_id' => ['required', 'integer', 'exists:workouts,id'],
        ], [
            'workout_id.required' => 'Выберите тренировку.',
            'workout_id.exists' => 'Тренировка не найдена.',
        ]);

        $data['user_id'] = $request->user()->id;

        DB::transaction(function () use ($data) {
            $workout = Workout::with('room')->lockForUpdate()->findOrFail($data['workout_id']);

            if ($workout->workout_start->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Тренировка уже началась.',
                ]);
            }

            if ($workout->trainer_id === $data['user_id']) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Нельзя записаться на собственную тренировку.',
                ]);
            }

            $alreadyExists = Record::where('workout_id', $workout->id)
                ->where('user_id', $data['user_id'])
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'workout_id' => 'Вы уже записаны на выбранную тренировку.',
                ]);
            }

            if ($workout->occupyingRecords()->count() >= $workout->room->capacity) {
                throw ValidationException::withMessages([
                    'workout_id' => 'На тренировке нет свободных мест.',
                ]);
            }

            // Новая запись ждёт подтверждения менеджером и место пока не занимает
            Record::create($data + ['status_id' => Status::idOf(Status::Pending)]);
        });

        return redirect()->route('records.index');
    }

    public function show(Record $record)
    {
        Gate::authorize('view', $record);

        $record->load(['user', 'status', 'workout.trainer', 'workout.room']);

        return view('records.show', compact('record'));
    }

    /**
     * Менеджер подтверждает запись, если в зале остались места.
     */
    public function confirm(Record $record)
    {
        Gate::authorize('confirm', $record);

        DB::transaction(function () use ($record) {
            $workout = Workout::with('room')->lockForUpdate()->findOrFail($record->workout_id);
            $record = Record::with('status')->lockForUpdate()->findOrFail($record->id);

            if (! $record->hasStatus(Status::Pending)) {
                throw ValidationException::withMessages([
                    'record' => 'Подтвердить можно только неподтверждённую запись.',
                ]);
            }

            if ($workout->workout_end->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'record' => 'Тренировка уже закончилась.',
                ]);
            }

            $participants = $workout->occupyingRecords()->count();

            if ($participants >= $workout->room->capacity) {
                throw ValidationException::withMessages([
                    'record' => 'Все места на тренировке уже заняты подтверждёнными записями.',
                ]);
            }

            $record->update(['status_id' => Status::idOf(Status::Confirmed)]);

            $workout->current_participants = $participants + 1;
            $workout->save();
        });

        return back();
    }

    /**
     * Менеджер отклоняет запись: при переполнении или по другой причине. Место освобождается.
     */
    public function reject(Record $record)
    {
        Gate::authorize('reject', $record);

        DB::transaction(function () use ($record) {
            $workout = Workout::lockForUpdate()->findOrFail($record->workout_id);

            if ($workout->workout_end->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'record' => 'Тренировка уже закончилась.',
                ]);
            }

            $record->update(['status_id' => Status::idOf(Status::Rejected)]);

            $workout->current_participants = $workout->occupyingRecords()->count();
            $workout->save();
        });

        return back();
    }

    public function destroy(Record $record)
    {
        Gate::authorize('delete', $record);

        DB::transaction(function () use ($record) {
            $workout = Workout::lockForUpdate()->findOrFail($record->workout_id);

            $record->delete();

            $workout->current_participants = $workout->occupyingRecords()->count();
            $workout->save();
        });

        return redirect()->route('records.index');
    }
}
