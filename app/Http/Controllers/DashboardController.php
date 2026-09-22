<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterDisposition;
use App\Models\Notification;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'letters' => Letter::with(['senderDivision', 'category'])->latest()->paginate(10),
            'counts' => [
                'total' => Letter::count(),
                'waiting' => Letter::whereIn('status', ['waiting_verification', 'waiting_reply'])->count(),
                'completed' => Letter::whereIn('status', ['completed', 'closed'])->count(),
                'unread' => Letter::whereNull('opened_at')->count(),
            ],
            'pendingDispositions' => LetterDisposition::where('is_replied', false)->where('type', 'disposition')->count(),
            'notifications' => Notification::latest()->limit(5)->get(),
        ]);
    }
}
