<?php

namespace App\Models;

use Database\Factories\LetterAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterAttachment extends Model
{
    /** @use HasFactory<LetterAttachmentFactory> */
    use HasFactory;

    protected $fillable = ['letter_id', 'file_name', 'file_path', 'file_size', 'mime_type'];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
