<?php

namespace ItmoSoft\ProductQuestions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ItmoSoft\ProductQuestions\Models\ProductAnswer;
use ItmoSoft\ProductQuestions\Models\ProductQuestion;

class ProductQuestionManager
{
    protected array $allowedStatuses;
    protected string $defaultStatus;

    public function __construct(protected array $config)
    {
        $allowedStatuses = $config['allowed_statuses'] ?? ['pending', 'approved', 'rejected'];

        if (! is_array($allowedStatuses) || $allowedStatuses === []) {
            throw new InvalidArgumentException('The allowed_statuses configuration must be a non-empty array.');
        }

        foreach ($allowedStatuses as $status) {
            if (
                ! is_string($status)
                || $status === ''
                || trim($status) !== $status
                || mb_strlen($status) > 30
            ) {
                throw new InvalidArgumentException('Each allowed moderation status must be a non-empty string of at most 30 characters.');
            }
        }

        if (count(array_unique($allowedStatuses)) !== count($allowedStatuses)) {
            throw new InvalidArgumentException('The allowed_statuses configuration cannot contain duplicates.');
        }

        if (! in_array('approved', $allowedStatuses, true)) {
            throw new InvalidArgumentException('The allowed_statuses configuration must include the approved status.');
        }

        $defaultStatus = $config['default_status'] ?? 'pending';

        if (! is_string($defaultStatus) || ! in_array($defaultStatus, $allowedStatuses, true)) {
            throw new InvalidArgumentException('The default_status must be included in allowed_statuses.');
        }

        $this->allowedStatuses = $allowedStatuses;
        $this->defaultStatus = $defaultStatus;
    }

    public function ask(
        Model $product,
        string $question,
        ?Model $asker = null,
        array $metadata = []
    ): ProductQuestion {
        $this->assertPersistedModel($product, 'Product');
        if ($asker !== null) {
            $this->assertPersistedModel($asker, 'Asker');
        }

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
        $record->status = $this->defaultStatus;
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
        $this->assertPersistedModel($question, 'Question');
        if ($responder !== null) {
            $this->assertPersistedModel($responder, 'Responder');
        }

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
        $record->status = $this->defaultStatus;
        $record->is_published = false;
        $record->metadata = $metadata;
        $record->save();

        return $record->refresh();
    }

    public function moderateQuestion(ProductQuestion $question, string $status): ProductQuestion
    {
        $this->assertPersistedModel($question, 'Question');
        $this->assertAllowedStatus($status);

        $question->forceFill([
            'status' => $status,
            'is_published' => $status === 'approved',
        ])->save();

        return $question->refresh();
    }

    public function moderateAnswer(ProductAnswer $answer, string $status): ProductAnswer
    {
        $this->assertPersistedModel($answer, 'Answer');
        $this->assertAllowedStatus($status);

        $answer->forceFill([
            'status' => $status,
            'is_published' => $status === 'approved',
        ])->save();

        return $answer->refresh();
    }

    public function publishedForProduct(Model $product)
    {
        $this->assertPersistedModel($product, 'Product');

        return ProductQuestion::query()
            ->published()
            ->whereMorphedTo('product', $product)
            ->with(['answers' => fn ($query) => $query->where('is_published', true)->where('status', 'approved')])
            ->latest();
    }

    public function deleteQuestion(ProductQuestion $question): void
    {
        $this->assertPersistedModel($question, 'Question');

        DB::transaction(function () use ($question) {
            $question->answers()->delete();
            $question->delete();
        });
    }

    protected function assertAllowedStatus(string $status): void
    {
        if (! in_array($status, $this->allowedStatuses, true)) {
            throw new InvalidArgumentException("Unsupported moderation status [{$status}].");
        }
    }

    protected function assertPersistedModel(Model $model, string $label): void
    {
        if (! $model->exists || $model->getKey() === null) {
            throw new InvalidArgumentException("{$label} must be persisted before it can be used.");
        }
    }
}
