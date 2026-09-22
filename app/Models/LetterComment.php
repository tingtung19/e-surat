<?php

namespace App\Models;

use Database\Factories\LetterCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterComment extends Model
{
    /** @use HasFactory<LetterCommentFactory> */
    use HasFactory;

    protected $fillable = ['letter_id', 'user_id', 'body'];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
