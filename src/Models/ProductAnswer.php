<?php

namespace ItmoSoft\ProductQuestions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnswer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_published' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('itmosoft-product-questions.table_prefix', 'itmosoft_').'product_answers';
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'question_id');
    }

    public function responder(): MorphTo
    {
        return $this->morphTo();
    }
}
