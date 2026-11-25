# ITMOSOFT Laravel Product Questions

Product questions and answers with moderation and customer attribution for Laravel 12 and 13.

## Features

- Ask questions against any persisted Eloquent product model.
- Optional polymorphic asker/customer association.
- Add answers with optional polymorphic responder association.
- Pending, approved and rejected moderation states.
- Published question query scoped to a product.
- Approved answers eager-loaded with published questions.
- Metadata support.
- Transactional question deletion with answer cleanup.
- Configurable table prefix.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require itmosoft/laravel-product-questions
php artisan vendor:publish --tag=itmosoft-product-questions-config
php artisan migrate
```

By default, migrations create `itmosoft_product_questions` and `itmosoft_product_answers`. Configure `table_prefix` before migrating if needed.

## Ask a product question

```php
use ItmoSoft\ProductQuestions\ProductQuestionManager;

$manager = app(ProductQuestionManager::class);

$question = $manager->ask(
    product: $product,
    question: 'Does this product include a warranty?',
    asker: auth()->user(),
);
```

The product and optional asker must already be persisted. Questions are pending and unpublished by default.

## Add an answer

```php
$answer = $manager->answer(
    question: $question,
    answer: 'Yes, it includes a one-year warranty.',
    responder: auth()->user(),
);
```

The question and optional responder must already be persisted. Answers are also pending and unpublished by default.

## Moderate content

```php
$manager->moderateQuestion($question, 'approved');
$manager->moderateAnswer($answer, 'approved');

// Other default supported statuses: pending, rejected
```

The `allowed_statuses` configuration must be a non-empty array of unique, non-empty strings no longer than 30 characters. It must include `approved`, and `default_status` must be one of the allowed values.

## Display approved Q&A

```php
$questions = $manager->publishedForProduct($product)->paginate(20);
```

The query eager-loads only approved, published answers.

## Important scope and production notes

- The package provides persistence and moderation primitives, not public routes, controllers, admin screens, notifications, spam detection, rate limiting, or frontend components.
- Product, asker and responder are polymorphic Eloquent models. Integrate with your product/customer/user models in the host application.
- Enforce authorization, request validation, abuse prevention, tenant scoping and content sanitization in your application.
- Deleting a question deletes its answers in a transaction. Configure database foreign keys appropriately for your target driver.
- The package uses text fields for content; escape output in your views to prevent cross-site scripting.

## Testing

```bash
composer install
composer validate --strict
composer test
```

## License

MIT. See [LICENSE](LICENSE).
