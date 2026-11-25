<?php

namespace ItmoSoft\ProductQuestions\Tests;

use InvalidArgumentException;
use ItmoSoft\ProductQuestions\Models\ProductAnswer;
use ItmoSoft\ProductQuestions\ProductQuestionManager;
use ItmoSoft\ProductQuestions\Tests\Models\Product;
use ItmoSoft\ProductQuestions\Tests\Models\User;

class ProductQuestionManagerTest extends TestCase
{
    public function test_ask_creates_pending_question_with_product_and_asker(): void
    {
        $product = Product::create(['name' => 'Desk']);
        $user = User::create(['name' => 'Customer']);

        $question = app(ProductQuestionManager::class)->ask($product, 'Is assembly included?', $user, ['source' => 'pdp']);

        $this->assertSame('pending', $question->status);
        $this->assertFalse($question->is_published);
        $this->assertSame($product->getKey(), $question->product->getKey());
        $this->assertSame($user->getKey(), $question->asker->getKey());
        $this->assertSame(['source' => 'pdp'], $question->metadata);
    }

    public function test_answer_can_be_added_and_moderated(): void
    {
        $product = Product::create(['name' => 'Chair']);
        $manager = app(ProductQuestionManager::class);
        $question = $manager->ask($product, 'What is the weight limit?');
        $answer = $manager->answer($question, 'Up to 120 kg.');

        $this->assertInstanceOf(ProductAnswer::class, $answer);
        $this->assertSame($question->getKey(), $answer->question_id);

        $manager->moderateQuestion($question, 'approved');
        $manager->moderateAnswer($answer, 'approved');

        $this->assertTrue($question->fresh()->is_published);
        $this->assertTrue($answer->fresh()->is_published);
    }

    public function test_published_for_product_returns_only_approved_questions(): void
    {
        $product = Product::create(['name' => 'Lamp']);
        $manager = app(ProductQuestionManager::class);
        $approved = $manager->ask($product, 'Does it include a bulb?');
        $manager->moderateQuestion($approved, 'approved');
        $manager->ask($product, 'Can I get a discount?');

        $this->assertCount(1, $manager->publishedForProduct($product)->get());
    }

    public function test_delete_question_removes_its_answers(): void
    {
        $product = Product::create(['name' => 'Table']);
        $manager = app(ProductQuestionManager::class);
        $question = $manager->ask($product, 'Is it waterproof?');
        $manager->answer($question, 'Yes.');

        $manager->deleteQuestion($question);

        $this->assertDatabaseCount('itmosoft_product_questions', 0);
        $this->assertDatabaseCount('itmosoft_product_answers', 0);
    }

    public function test_ask_rejects_an_unsaved_product(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product must be persisted');

        app(ProductQuestionManager::class)->ask(new Product(), 'Is assembly included?');
    }

    public function test_answer_rejects_an_unsaved_question(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Question must be persisted');

        app(ProductQuestionManager::class)->answer(
            new \ItmoSoft\ProductQuestions\Models\ProductQuestion(),
            'An answer.'
        );
    }

    public function test_ask_rejects_an_unsaved_asker(): void
    {
        $product = Product::create(['name' => 'Desk']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asker must be persisted');

        app(ProductQuestionManager::class)->ask($product, 'Is assembly included?', new User());
    }

    public function test_manager_rejects_a_default_status_not_in_the_allowlist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('default_status must be included');

        new ProductQuestionManager([
            'default_status' => 'draft',
            'allowed_statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function test_manager_rejects_invalid_allowed_status_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty array');

        new ProductQuestionManager([
            'default_status' => 'pending',
            'allowed_statuses' => 'pending,approved',
        ]);
    }

    public function test_manager_rejects_status_values_longer_than_database_column(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at most 30 characters');

        new ProductQuestionManager([
            'default_status' => 'pending',
            'allowed_statuses' => ['pending', 'approved', str_repeat('x', 31)],
        ]);
    }

    public function test_moderation_rejects_a_status_outside_the_allowlist(): void
    {
        $product = Product::create(['name' => 'Desk']);
        $manager = app(ProductQuestionManager::class);
        $question = $manager->ask($product, 'Is assembly included?');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported moderation status [archived]');

        $manager->moderateQuestion($question, 'archived');
    }
}
