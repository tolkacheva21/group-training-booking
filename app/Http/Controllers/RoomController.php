<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoomController extends Controller
{
    // READ all rooms
    public function index(): View {
        $rooms = Room::all();

        return view('rooms.index', compact('rooms'));
    }

    // READ single room
    public function show(Room $room): View {
        return view('rooms.show', compact('room'));
    }

    public function create(): View {
        return view('rooms.create');
    }

    // CREATE room
    public function store(Request $request): RedirectResponse {
        $roomValid = $request -> validate([
            'number' => ['required', 'integer', 'unique:rooms,number'],
            'capacity' => ['required', 'integer'],
        ]);

        $room = Room::create($roomValid);

        return redirect() -> route('rooms.index') -> with('success','Комната успешно создана');
    }

    public function edit(Room $room): View {
        return view('rooms.edit', compact('room'));
    }

    // UPDATE room
    public function update(Request $request, Room $room): RedirectResponse {
        $roomValid = $request -> validate([
            'number' => ['sometimes', 'integer', Rule::unique('rooms', 'number')->ignore($room->id)],
            'capacity' => ['sometimes', 'integer'],
        ]);

        $room -> update($roomValid);

        return redirect() -> route('rooms.index') -> with('success','Данные комнаты успешно изменены');
    }

    // DELETE room
    public function destroy(Room $room): RedirectResponse {
        $room -> delete();

        return redirect() -> route('rooms.index') -> with('success','Комната успешно удалена');
    }
}
