<?php

namespace ItmoSoft\ProductQuestions\Tests;

use ItmoSoft\ProductQuestions\Models\ProductAnswer;
use ItmoSoft\ProductQuestions\Models\ProductQuestion;
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
}
