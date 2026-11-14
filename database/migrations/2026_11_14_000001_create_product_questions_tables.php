<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('itmosoft-product-questions.table_prefix', 'itmosoft_');

        Schema::create($prefix.'product_questions', function (Blueprint $table) {
            $table->id();
            $table->morphs('product');
            $table->nullableMorphs('asker');
            $table->text('question');
            $table->string('status', 30)->default('pending')->index();
            $table->boolean('is_published')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_type', 'product_id', 'is_published'], 'product_questions_product_publish_idx');
        });

        Schema::create($prefix.'product_answers', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('question_id')->constrained($prefix.'product_questions')->cascadeOnDelete();
            $table->nullableMorphs('responder');
            $table->text('answer');
            $table->string('status', 30)->default('pending')->index();
            $table->boolean('is_published')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $prefix = config('itmosoft-product-questions.table_prefix', 'itmosoft_');
        Schema::dropIfExists($prefix.'product_answers');
        Schema::dropIfExists($prefix.'product_questions');
    }
};
