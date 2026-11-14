<?php

namespace ItmoSoft\ProductQuestions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ItmoSoft\ProductQuestions\Models\ProductAnswer;
use ItmoSoft\ProductQuestions\Models\ProductQuestion;

class ProductQuestionManager
{
    public function __construct(protected array $config)
    {
    }

    public function ask(
        Model $product,
        string $question,
        ?Model $asker = null,
        array $metadata = []
    ): ProductQuestion {
        $question = trim($question);
        if ($question === '') {
            throw new InvalidArgumentException('Question text cannot be empty.');
        }

        $record = new ProductQuestion();
        $record->product()->associate($product);
        if ($asker !== null) {
            $record->asker()->associate($asker);
        }
        $record->question = $question;
        $record->status = $this->config['default_status'] ?? 'pending';
        $record->is_published = false;
        $record->metadata = $metadata;
        $record->save();

        return $record->refresh();
    }

    public function answer(
        ProductQuestion $question,
        string $answer,
        ?Model $responder = null,
        array $metadata = []
    ): ProductAnswer {
        $answer = trim($answer);
        if ($answer === '') {
            throw new InvalidArgumentException('Answer text cannot be empty.');
        }

        $record = new ProductAnswer();
        $record->question()->associate($question);
        if ($responder !== null) {
            $record->responder()->associate($responder);
        }
        $record->answer = $answer;
        $record->status = $this->config['default_status'] ?? 'pending';
        $record->is_published = false;
        $record->metadata = $metadata;
        $record->save();

        return $record->refresh();
    }

    public function moderateQuestion(ProductQuestion $question, string $status): ProductQuestion
    {
        $this->assertAllowedStatus($status);
        $question->forceFill([
            'status' => $status,
            'is_published' => $status === 'approved',
        ])->save();

        return $question->refresh();
    }

    public function moderateAnswer(ProductAnswer $answer, string $status): ProductAnswer
    {
        $this->assertAllowedStatus($status);
        $answer->forceFill([
            'status' => $status,
            'is_published' => $status === 'approved',
        ])->save();

        return $answer->refresh();
    }

    public function publishedForProduct(Model $product)
    {
        return ProductQuestion::query()
            ->published()
            ->whereMorphedTo('product', $product)
            ->with(['answers' => fn ($query) => $query->where('is_published', true)->where('status', 'approved')])
            ->latest();
    }

    public function deleteQuestion(ProductQuestion $question): void
    {
        DB::transaction(function () use ($question) {
            $question->answers()->delete();
            $question->delete();
        });
    }

    protected function assertAllowedStatus(string $status): void
    {
        $allowed = $this->config['allowed_statuses'] ?? ['pending', 'approved', 'rejected'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException("Unsupported moderation status [{$status}].");
        }
    }
}
