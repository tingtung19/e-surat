<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterDisposition;
use App\Models\LetterRead;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $accessible = Letter::query()->where(function (Builder $query) use ($user): void {
            if ($user->isAdmin()) {
                return;
            }

            if ($user->isDirector()) {
                $query->where(function (Builder $verified): void {
                    $verified->whereIn('type', ['official', 'external'])->whereNotNull('verified_at');
                })->orWhereHas('dispositions', fn (Builder $dispositions) => $dispositions->where('to_user_id', $user->id));

                return;
            }

            $query->where('created_by', $user->id)
                ->orWhere('sender_division_id', $user->id)
                ->orWhere('target_division_id', $user->id)
                ->orWhereHas('dispositions', fn (Builder $dispositions) => $dispositions->where('to_user_id', $user->id));
        });
        $unread = (clone $accessible)->where(function (Builder $query) use ($user): void {
            $query->whereNotExists(fn ($reads) => $reads->selectRaw('1')
                ->from((new LetterRead)->getTable())
                ->whereColumn('letter_reads.letter_id', 'letters.id')
                ->where('letter_reads.user_id', $user->id))
                ->orWhereHas('dispositions', fn (Builder $dispositions) => $dispositions->where('to_user_id', $user->id)->where('is_read', false));
        });

        return view('dashboard', [
            'letters' => (clone $unread)->with(['senderDivision', 'category'])->latest()->limit(10)->get(),
            'counts' => [
                'total' => (clone $accessible)->count(),
                'waiting' => (clone $accessible)->whereIn('status', ['waiting_verification', 'waiting_reply'])->count(),
                'completed' => (clone $accessible)->whereIn('status', ['completed', 'closed'])->count(),
                'unread' => (clone $unread)->count(),
            ],
            'pendingDispositions' => LetterDisposition::where('to_user_id', $user->id)->where('is_replied', false)->where('type', 'disposition')->count(),
            'notifications' => Notification::where('user_id', $user->id)->latest()->limit(5)->get(),
        ]);
    }
}
