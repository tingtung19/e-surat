<?php

namespace App\Models;

use Database\Factories\LetterCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterCategory extends Model
{
    /** @use HasFactory<LetterCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'parent_id', 'number_format', 'description'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'category_id');
    }
}
