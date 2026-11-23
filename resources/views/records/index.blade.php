@extends('app')

@section('content')
    <style>
        .records-grid {
            display: grid;
            gap: 25px;
        }

        .workout-list {
            display: grid;
            gap: 12px;
        }

        .workout-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 16px 18px;
            border: 1px solid #dce3dd;
            border-radius: 6px;
            background: #fafcfb;
        }

        .workout-row .workout-info {
            display: grid;
            gap: 4px;
        }

        .workout-row .workout-title {
            font-weight: bold;
            font-size: 16px;
        }

        .workout-row .workout-meta {
            font-size: 14px;
            color: #4a5a4f;
        }

        .workout-row .workout-action {
            flex: 0 0 auto;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            background: #edf3ee;
            color: #365d46;
        }

        .badge-paid {
            background: #e4f2e6;
            color: #2f6b39;
        }

        .badge-unpaid {
            background: #fdf1dc;
            color: #8a5b12;
        }

        .badge-cancelled {
            background: #fbeeee;
            color: #842d2d;
        }

        .flash {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-left: 4px solid #365d46;
            background: #edf3ee;
            color: #24402f;
        }

        .muted {
            color: #6b7a70;
            font-size: 14px;
        }
    </style>

    <div class="records-grid">
        @if (session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif

        @if ($canBook)
            <div class="card">
                <div class="top-line">
                    <h1>Доступные тренировки</h1>
                </div>

                @if ($workouts->isEmpty())
                    <p class="muted">Сейчас нет доступных для записи тренировок.</p>
                @else
                    <div class="workout-list">
                        @foreach ($workouts as $workout)
                            @php
                                $ownRecord = $userRecords->get($workout->id);
                                $seats = $workout->availableSeats();
                                $start = \App\Support\DisplayTime::toLocal($workout->workout_start);
                                $end = \App\Support\DisplayTime::toLocal($workout->workout_end);
                            @endphp

                            <div class="workout-row">
                                <div class="workout-info">
                                    <span class="workout-title">
                                        Тренировка по йоге
                                        <span class="badge">{{ $workout->statusLabel() }}</span>
                                    </span>

                                    <span class="workout-meta">
                                        {{ $start->format('d.m.Y') }}
                                        · {{ $start->format('H:i') }}–{{ $end->format('H:i') }}
                                        · тренер {{ $workout->trainer->name }}
                                        · зал №{{ $workout->room->number }}
                                    </span>

                                    <span class="workout-meta">
                                        Стоимость: {{ number_format((float) $workout->price, 2, ',', ' ') }} ₽
                                        · свободно мест: {{ $seats }} из {{ $workout->room->capacity }}
                                    </span>
                                </div>

                                <div class="workout-action">
                                    @if ($ownRecord)
                                        <span class="badge {{ $ownRecord->isPaid() ? 'badge-paid' : 'badge-unpaid' }}">
                                            {{ $ownRecord->paymentLabel() }}
                                        </span>
                                        <a class="button" href="{{ route('records.show', $ownRecord) }}">
                                            Моя запись
                                        </a>
                                    @elseif ($seats < 1)
                                        <span class="muted">Мест нет</span>
                                    @else
                                        <form class="inline" action="{{ route('records.store') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="workout_id" value="{{ $workout->id }}">
                                            <button type="submit">Записаться</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="card">
            <div class="top-line">
                <h1>{{ $canBook ? 'Мои записи' : 'Записи на тренировки' }}</h1>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>№ записи</th>
                            @unless ($canBook)
                                <th>Пользователь</th>
                            @endunless
                            <th>Начало тренировки</th>
                            <th>Тренер</th>
                            <th>Зал</th>
                            <th>Статус записи</th>
                            <th>Оплата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($records as $record)
                            <tr>
                                <td>{{ $record->id }}</td>

                                @unless ($canBook)
                                    <td>{{ $record->user->name }}</td>
                                @endunless

                                <td>
                                    {{ \App\Support\DisplayTime::toLocal($record->workout->workout_start)->format('d.m.Y H:i') }}
                                </td>

                                <td>{{ $record->workout->trainer->name }}</td>

                                <td>№{{ $record->workout->room->number }}</td>

                                <td>{{ $record->status->title }}</td>

                                <td>
                                    <span class="badge {{ $record->isPaid() ? 'badge-paid' : ($record->isPaymentCancelled() ? 'badge-cancelled' : 'badge-unpaid') }}">
                                        {{ $record->paymentLabel() }}
                                    </span>
                                </td>

                                <td>
                                    <a class="button"
                                       href="{{ route('records.show', $record) }}">
                                        Открыть
                                    </a>

                                    @can('confirm', $record)
                                        <form class="inline"
                                              action="{{ route('records.confirm', $record) }}"
                                              method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit">Подтвердить</button>
                                        </form>
                                    @endcan

                                    @can('reject', $record)
                                        <form class="inline"
                                              action="{{ route('records.reject', $record) }}"
                                              method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <button class="button-danger"
                                                    type="submit"
                                                    onclick="return confirm('Отклонить запись?')">
                                                Отклонить
                                            </button>
                                        </form>
                                    @endcan

                                    @can('delete', $record)
                                        <form class="inline"
                                              action="{{ route('records.destroy', $record) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button class="button-danger"
                                                    type="submit"
                                                    onclick="return confirm('Отменить запись на тренировку?')">
                                                Отменить запись
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canBook ? 7 : 8 }}">
                                    Записей пока нет.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
