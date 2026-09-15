<?php

namespace App\Http\Controllers;

use App\Models\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformNotificationController extends Controller
{
    public function markRead(Request $request, PlatformNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back();
    }

    public function acknowledge(Request $request, PlatformNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
        abort_unless($notification->requires_acknowledgement, 422);

        $notification->update([
            'read_at' => $notification->read_at ?? now(),
            'acknowledged_at' => $notification->acknowledged_at ?? now(),
        ]);

        return back()->with('success', 'Notification acknowledged.');
    }
}
