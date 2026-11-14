<?php

namespace ItmoSoft\ProductQuestions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQuestion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_published' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('itmosoft-product-questions.table_prefix', 'itmosoft_').'product_questions';
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }

    public function asker(): MorphTo
    {
        return $this->morphTo();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class, 'question_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('status', 'approved');
    }
}
