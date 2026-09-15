<?php

namespace App\Http\Controllers;

use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function store(Request $request, LeaveService $service): RedirectResponse
    {
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'units' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->submit($request->user(), $data);
        return back()->with('success', 'Leave request submitted to HR for review.');
    }
}
