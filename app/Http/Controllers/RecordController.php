<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Record;
use App\Models\Status;
use App\Models\Workout;
use App\Services\BookingService;
use App\Services\PaymentSimulator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly PaymentSimulator $payments,
    ) {}

    /**
     * Список записей. Посетитель сразу видит доступные тренировки и свои записи,
     * администратор и менеджер — все записи пользователей.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $canBook = $user->can('book', Record::class);

        $records = Record::with(['user', 'status', 'workout.trainer', 'workout.room'])
            ->when(
                Gate::denies('viewAll', Record::class),
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->latest('id')
            ->get();

        // Доступные для записи тренировки видит только посетитель
        $workouts = $canBook
            ? Workout::with(['trainer', 'room'])
                ->withCount('bookedRecords as booked_count')
                ->notCancelled()
                ->where('workout_start', '>', now())
                ->orderBy('workout_start')
                ->get()
            : collect();

        // Для каждой тренировки — запись текущего пользователя, чтобы показать статус
        $userRecords = $records
            ->where('user_id', $user->id)
            ->keyBy('workout_id');

        return view('records.index', compact('records', 'workouts', 'userRecords', 'canBook'));
    }

    /**
     * Отдельная форма создания записи больше не нужна: запись выполняется
     * кнопкой «Записаться» напротив тренировки в списке.
     */
    public function create(): RedirectResponse
    {
        Gate::authorize('book', Record::class);

        return redirect()->route('records.index');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('book', Record::class);

        $data = $request->validate([
            'workout_id' => ['required', 'integer', 'exists:workouts,id'],
        ], [
            'workout_id.required' => 'Выберите тренировку.',
            'workout_id.exists' => 'Тренировка не найдена.',
        ]);

        $record = $this->bookings->book($request->user(), (int) $data['workout_id']);

        return redirect()
            ->route('records.show', $record)
            ->with('success', 'Вы записаны на тренировку. Осталось оплатить участие.');
    }

    public function show(Record $record): View
    {
        Gate::authorize('view', $record);

        $record->load(['user', 'status', 'workout.trainer', 'workout.room']);

        return view('records.show', compact('record'));
    }

    /**
     * Имитация успешной оплаты записи.
     */
    public function pay(Record $record): RedirectResponse
    {
        Gate::authorize('pay', $record);

        $this->payments->pay($record);

        return back()->with('success', 'Оплата прошла успешно. Запись оплачена.');
    }

    /**
     * Имитация отмены/неуспешной оплаты.
     */
    public function cancelPayment(Record $record): RedirectResponse
    {
        Gate::authorize('pay', $record);

        $this->payments->cancel($record);

        return back()->with('success', 'Оплата отменена. Запись осталась неоплаченной.');
    }

    /**
     * Менеджер подтверждает запись, если в зале остались места.
     */
    public function confirm(Record $record): RedirectResponse
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
    public function reject(Record $record): RedirectResponse
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

            // Отклонённая запись не может оставаться оплаченной — имитируем возврат
            if ($record->isPaid()) {
                $record->update(['payment_status' => PaymentStatus::Cancelled, 'paid_at' => null]);
            }

            $workout->current_participants = $workout->occupyingRecords()->count();
            $workout->save();
        });

        return back();
    }

    public function destroy(Record $record): RedirectResponse
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
