<?php

namespace App\Models;

use Database\Factories\LetterDispositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterDisposition extends Model
{
    /** @use HasFactory<LetterDispositionFactory> */
    use HasFactory;

    protected $fillable = ['letter_id', 'from_user_id', 'to_user_id', 'type', 'note', 'is_read', 'is_replied', 'replied_at', 'disposed_at'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'is_replied' => 'boolean', 'disposed_at' => 'datetime', 'replied_at' => 'datetime'];
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
