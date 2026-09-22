<?php

namespace App\Models;

use Database\Factories\LetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Letter extends Model
{
    /** @use HasFactory<LetterFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'type', 'number', 'category_id', 'sender_division_id', 'target_division_id', 'created_by', 'verified_by', 'external_sender', 'status', 'revision_note', 'verified_at', 'opened_at', 'cancelled_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'opened_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function senderDivision(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_division_id');
    }

    public function targetDivision(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_division_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterCategory::class, 'category_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LetterAttachment::class);
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(LetterDisposition::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LetterComment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LetterHistory::class)->latest('occurred_at');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(LetterRead::class);
    }
}
