<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterComment;
use App\Models\LetterDisposition;
use App\Models\LetterHistory;
use App\Models\LetterRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LetterController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = $this->accessibleLetters()->with('senderDivision');

        if ($request->expectsJson()) {
            $draw = (int) $request->input('draw', 1);
            $total = (clone $query)->count();
            $search = trim((string) $request->input('search.value', ''));
            if ($search !== '') {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('title', 'like', '%'.$search.'%')
                        ->orWhere('number', 'like', '%'.$search.'%')
                        ->orWhere('status', 'like', '%'.$search.'%')
                        ->orWhereHas('senderDivision', fn (Builder $sender) => $sender->where('name', 'like', '%'.$search.'%')->orWhere('division_name', 'like', '%'.$search.'%'));
                });
            }
            $filtered = (clone $query)->count();
            $columns = ['title', 'type', 'status', 'created_at'];
            $orderColumn = $columns[(int) $request->input('order.0.column', 3)] ?? 'created_at';
            $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
            $letters = $query->orderBy($orderColumn, $direction)
                ->skip((int) $request->input('start', 0))
                ->take((int) $request->input('length', 10))
                ->get();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $letters->map(fn (Letter $letter): array => [
                    'title' => $letter->title,
                    'number' => $letter->number ?? 'Tanpa nomor',
                    'type' => ucfirst($letter->type),
                    'status' => str_replace('_', ' ', ucfirst($letter->status)),
                    'sender' => $letter->senderDivision?->division_name ?? $letter->senderDivision?->name ?? '-',
                    'created_at' => $letter->created_at->format('d/m/Y H:i'),
                    'url' => route('letters.show', $letter),
                    'unread' => $this->isUnreadForUser($letter),
                ]),
            ]);
        }

        return view('letters.index');
    }

    public function create(): View
    {
        return view('letters.create', [
            'divisions' => User::where('role', 'divisi')->where('is_active', true)->orderBy('division_name')->get(),
            'defaultDivision' => User::where('role', 'divisi')->where('is_active', true)->where('division_name', 'Divisi Umum')->first(),
            'categories' => LetterCategory::with('children')->whereNull('parent_id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['divisi', 'admin_umum'], true), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:internal,official,external'],
            'target_division_id' => ['nullable', 'exists:users,id'],
            'external_sender' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
        if ($data['type'] === 'official' && $request->user()->role !== 'divisi') {
            abort(403);
        }
        if ($data['type'] === 'external' && ! $request->user()->isAdmin()) {
            abort(403);
        }
        if ($data['type'] === 'official') {
            $defaultDivision = User::where('role', 'divisi')
                ->where('is_active', true)
                ->where('division_name', 'Divisi Umum')
                ->first();

            if (! $defaultDivision) {
                return back()->withErrors(['target_division_id' => 'Divisi Umum belum tersedia. Tambahkan melalui Data Users terlebih dahulu.'])->withInput();
            }

            $data['target_division_id'] = $defaultDivision->id;
        }
        if ($data['type'] === 'internal' && (! $data['target_division_id'] || ! User::where('role', 'divisi')->where('is_active', true)->whereKey($data['target_division_id'])->exists())) {
            return back()->withErrors(['target_division_id' => 'Divisi tujuan wajib dipilih untuk surat internal.'])->withInput();
        }
        $creator = $request->user();
        $data['created_by'] = $creator->id;
        $data['sender_division_id'] = $creator->id;
        $data['status'] = $request->boolean('save_draft') ? 'draft' : ($data['type'] === 'internal' ? 'sent' : 'waiting_verification');
        $attachments = $request->file('attachments', []);
        $letter = DB::transaction(function () use ($data, $creator, $attachments): Letter {
            $letter = Letter::create($data);
            LetterRead::create(['letter_id' => $letter->id, 'user_id' => $creator->id, 'read_at' => now()]);
            LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $creator->id, 'action' => 'created', 'description' => 'Surat dibuat dengan status '.$letter->status, 'occurred_at' => now()]);
            foreach ($attachments as $attachment) {
                $path = $attachment->store('letters');
                $letter->attachments()->create(['file_name' => $attachment->getClientOriginalName(), 'file_path' => $path, 'file_size' => $attachment->getSize(), 'mime_type' => $attachment->getMimeType()]);
            }

            return $letter;
        });

        return redirect()->route('letters.show', $letter)->with('success', 'Surat berhasil disimpan.');
    }

    public function edit(Letter $letter): View
    {
        abort_unless($letter->created_by === auth()->id() && $letter->status === 'draft', 403);

        return view('letters.create', [
            'letter' => $letter,
            'divisions' => User::where('role', 'divisi')->where('is_active', true)->orderBy('division_name')->get(),
            'defaultDivision' => User::where('role', 'divisi')->where('is_active', true)->where('division_name', 'Divisi Umum')->first(),
            'categories' => LetterCategory::with('children')->whereNull('parent_id')->get(),
        ]);
    }

    public function update(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($letter->created_by === $request->user()->id && $letter->status === 'draft', 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:internal,official,external'],
            'target_division_id' => ['nullable', 'exists:users,id'],
            'external_sender' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
        abort_if($data['type'] === 'official' && $request->user()->role !== 'divisi', 403);
        abort_if($data['type'] === 'external' && ! $request->user()->isAdmin(), 403);

        if ($data['type'] === 'official') {
            $defaultDivision = User::where('role', 'divisi')->where('is_active', true)->where('division_name', 'Divisi Umum')->first();
            if (! $defaultDivision) {
                return back()->withErrors(['target_division_id' => 'Divisi Umum belum tersedia.'])->withInput();
            }
            $data['target_division_id'] = $defaultDivision->id;
        }
        if ($data['type'] === 'internal' && (! $data['target_division_id'] || ! User::where('role', 'divisi')->where('is_active', true)->whereKey($data['target_division_id'])->exists())) {
            return back()->withErrors(['target_division_id' => 'Divisi tujuan wajib dipilih untuk surat internal.'])->withInput();
        }

        $data['status'] = $request->boolean('save_draft') ? 'draft' : ($data['type'] === 'internal' ? 'sent' : 'waiting_verification');
        $attachments = $request->file('attachments', []);
        DB::transaction(function () use ($data, $attachments, $letter, $request): void {
            $letter->update($data);
            LetterRead::updateOrCreate(
                ['letter_id' => $letter->id, 'user_id' => $request->user()->id],
                ['read_at' => now()],
            );
            LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'updated', 'description' => 'Draft diperbarui dengan status '.$letter->status, 'occurred_at' => now()]);
            foreach ($attachments as $attachment) {
                $path = $attachment->store('letters');
                $letter->attachments()->create(['file_name' => $attachment->getClientOriginalName(), 'file_path' => $path, 'file_size' => $attachment->getSize(), 'mime_type' => $attachment->getMimeType()]);
            }
        });

        return redirect()->route('letters.show', $letter)->with('success', $request->boolean('save_draft') ? 'Draft berhasil diperbarui.' : 'Draft berhasil dikirim.');
    }

    public function show(Letter $letter): View
    {
        abort_unless($this->accessibleLetters()->whereKey($letter->id)->exists(), 403);
        if (! $letter->opened_at) {
            $letter->update(['opened_at' => now(), 'status' => $letter->status === 'sent' ? 'opened' : $letter->status]);
            LetterHistory::create(['letter_id' => $letter->id, 'user_id' => auth()->id(), 'action' => 'opened', 'description' => 'Surat dibuka', 'occurred_at' => now()]);
        }
        LetterRead::updateOrCreate(
            ['letter_id' => $letter->id, 'user_id' => auth()->id()],
            ['read_at' => now()],
        );
        $letter->dispositions()->where('to_user_id', auth()->id())->where('is_read', false)->update(['is_read' => true]);

        return view('letters.show', ['letter' => $letter->load(['creator', 'senderDivision', 'targetDivision', 'category', 'attachments', 'comments.user', 'dispositions.recipient', 'histories.user']), 'divisions' => User::where('role', 'divisi')->where('is_active', true)->orderBy('division_name')->get()]);
    }

    public function comment(Request $request, Letter $letter): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        LetterComment::create(['letter_id' => $letter->id, 'user_id' => auth()->id(), 'body' => $data['body']]);
        LetterHistory::create(['letter_id' => $letter->id, 'user_id' => auth()->id(), 'action' => 'commented', 'description' => $data['body'], 'occurred_at' => now()]);

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function dispose(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless(auth()->user()->isDirector(), 403);
        $data = $request->validate([
            'to_user_ids' => ['required', 'array', 'min:1'],
            'to_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'note' => ['required', 'string', 'max:5000'],
        ]);
        $recipients = User::whereIn('id', $data['to_user_ids'])->where('role', 'divisi')->where('is_active', true)->get();
        abort_if($recipients->count() !== count($data['to_user_ids']), 422, 'Semua penerima disposisi harus merupakan divisi aktif.');
        DB::transaction(function () use ($data, $letter, $recipients): void {
            foreach ($recipients as $recipient) {
                LetterDisposition::create(['letter_id' => $letter->id, 'from_user_id' => auth()->id(), 'to_user_id' => $recipient->id, 'type' => 'disposition', 'note' => $data['note'], 'disposed_at' => now()]);
                LetterHistory::create(['letter_id' => $letter->id, 'user_id' => auth()->id(), 'action' => 'disposed', 'description' => 'Disposisi ke '.$recipient->email.': '.$data['note'], 'occurred_at' => now()]);
            }
            $letter->update(['status' => 'waiting_reply']);
        });

        return back()->with('success', 'Disposisi berhasil dikirim ke '.$recipients->count().' divisi.');
    }

    public function cc(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($request->user()->isDirector(), 403);
        $data = $request->validate([
            'cc_user_ids' => ['required', 'array', 'min:1'],
            'cc_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);
        $recipients = User::whereIn('id', $data['cc_user_ids'])->where('role', 'divisi')->where('is_active', true)->get();
        abort_if($recipients->count() !== count($data['cc_user_ids']), 422, 'Semua penerima tembusan harus merupakan divisi aktif.');
        DB::transaction(function () use ($data, $letter, $recipients, $request): void {
            foreach ($recipients as $recipient) {
                LetterDisposition::create(['letter_id' => $letter->id, 'from_user_id' => $request->user()->id, 'to_user_id' => $recipient->id, 'type' => 'cc', 'note' => $data['note'], 'disposed_at' => now(), 'is_replied' => true]);
                LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'cc_added', 'description' => 'Tembusan ke '.$recipient->email.': '.($data['note'] ?? 'Tembusan ditambahkan.'), 'occurred_at' => now()]);
            }
        });

        return back()->with('success', 'Tembusan berhasil dikirim ke '.$recipients->count().' divisi.');
    }

    public function close(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isDirector(), 403);
        abort_if($letter->dispositions()->where('type', 'disposition')->where('is_replied', false)->exists(), 422, 'Masih ada disposisi yang belum dibalas.');
        $letter->update(['status' => 'closed']);
        LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'closed', 'description' => 'Surat ditutup dan diarsipkan.', 'occurred_at' => now()]);

        return back()->with('success', 'Surat berhasil ditutup.');
    }

    public function verify(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['number' => ['required', 'string', 'max:100'], 'category_id' => ['required', 'exists:letter_categories,id']]);
        $letter->update(['number' => $data['number'], 'category_id' => $data['category_id'], 'verified_by' => $request->user()->id, 'verified_at' => now(), 'status' => 'sent']);
        LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'verified', 'description' => 'Diverifikasi dengan nomor '.$data['number'], 'occurred_at' => now()]);

        return back()->with('success', 'Surat berhasil diverifikasi dan diteruskan ke Direktur.');
    }

    public function cancel(Request $request, Letter $letter): RedirectResponse
    {
        abort_unless($letter->created_by === $request->user()->id && ! $letter->opened_at && in_array($letter->status, ['draft', 'waiting_verification', 'sent'], true), 403);
        $letter->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'cancelled', 'description' => 'Surat dibatalkan sebelum dibuka.', 'occurred_at' => now()]);

        return back()->with('success', 'Surat berhasil dibatalkan.');
    }

    public function reply(Request $request, Letter $letter, LetterDisposition $disposition): RedirectResponse
    {
        abort_unless($disposition->letter_id === $letter->id && $disposition->to_user_id === $request->user()->id && ! $disposition->is_replied, 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png']]);
        LetterComment::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'body' => $data['body']]);
        foreach ($request->file('attachments', []) as $attachment) {
            $path = $attachment->store('letters');
            $letter->attachments()->create(['file_name' => $attachment->getClientOriginalName(), 'file_path' => $path, 'file_size' => $attachment->getSize(), 'mime_type' => $attachment->getMimeType()]);
        }
        $disposition->update(['is_replied' => true, 'replied_at' => now(), 'is_read' => true]);
        $letter->update(['status' => 'sent']);
        LetterHistory::create(['letter_id' => $letter->id, 'user_id' => $request->user()->id, 'action' => 'replied', 'description' => $data['body'], 'occurred_at' => now()]);

        return back()->with('success', 'Balasan disposisi berhasil dikirim.');
    }

    private function accessibleLetters(): Builder
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return Letter::query();
        }

        return Letter::query()->where(function (Builder $query) use ($user): void {
            if ($user->isDirector()) {
                $query->where(function (Builder $verifiedLetters): void {
                    $verifiedLetters->whereIn('type', ['official', 'external'])->whereNotNull('verified_at');
                })->orWhereHas('dispositions', fn (Builder $dispositions) => $dispositions->where('to_user_id', $user->id));

                return;
            }
            $query->where('created_by', $user->id)
                ->orWhere('sender_division_id', $user->id)
                ->orWhere('target_division_id', $user->id)
                ->orWhereHas('dispositions', fn (Builder $dispositions) => $dispositions->where('to_user_id', $user->id));
        });
    }

    private function isUnreadForUser(Letter $letter): bool
    {
        return ! LetterRead::where('letter_id', $letter->id)->where('user_id', auth()->id())->exists()
            || $letter->dispositions()->where('to_user_id', auth()->id())->where('is_read', false)->exists();
    }
}
