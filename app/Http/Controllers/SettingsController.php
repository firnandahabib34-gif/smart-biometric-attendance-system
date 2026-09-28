<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = collect(request()->user()->only([
            'campus_name',
            'attendance_start',
            'attendance_end',
        ]));

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:120'],
            'attendance_start' => ['required', 'date_format:H:i'],
            'attendance_end' => ['required', 'date_format:H:i', 'after:attendance_start'],
        ]);

        $request->user()->update($validated);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
