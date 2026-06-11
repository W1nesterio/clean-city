<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('organization');

        return response()->json([
            'user' => $user,
            'stats' => $this->workerStats($user),
            'completed_tickets' => $this->completedTickets($user),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['nullable', 'image', 'max:3072'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
            $user->save();
        }

        return response()->json([
            'message' => 'Профиль обновлён',
            'user' => $user->load('organization'),
            'stats' => $this->workerStats($user),
            'completed_tickets' => $this->completedTickets($user),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Текущий пароль указан неверно'], 422);
        }

        $user->update(['password' => Hash::make($data['new_password'])]);

        return response()->json(['message' => 'Пароль успешно изменён']);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->avatar_path = null;
        $user->save();

        return response()->json([
            'message' => 'Фото профиля удалено',
            'user' => $user->load('organization'),
            'stats' => $this->workerStats($user),
            'completed_tickets' => $this->completedTickets($user),
        ]);
    }

    private function workerStats($user): array
    {
        if ($user->role !== 'worker') {
            return [
                'new' => 0,
                'accepted' => 0,
                'in_progress' => 0,
                'active' => 0,
                'completed' => 0,
                'total' => 0,
            ];
        }

        $base = Ticket::query()->where('assigned_worker_id', $user->id);

        $new = (clone $base)->where('status', 'assigned')->count();
        $accepted = (clone $base)->where('status', 'accepted')->count();
        $inProgress = (clone $base)->where('status', 'in_progress')->count();
        $completed = (clone $base)->where('status', 'completed')->count();
        $active = (clone $base)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])->count();
        $total = (clone $base)->count();

        return [
            'new' => $new,
            'accepted' => $accepted,
            'in_progress' => $inProgress,
            'active' => $active,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    private function completedTickets($user)
    {
        if ($user->role !== 'worker') {
            return [];
        }

        return Ticket::with(['category', 'photos'])
            ->where('assigned_worker_id', $user->id)
            ->where('status', 'completed')
            ->latest('closed_at')
            ->limit(10)
            ->get();
    }
}
