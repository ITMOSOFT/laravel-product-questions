# Changelog

## [1.0.1] - 2026-11-25

- Validate moderation status configuration when the manager is constructed.
- Require the default status to be present in the allowed status list.
- Reject duplicate, empty, malformed, or overlong status values.
- Require the `approved` status so content can be published consistently.
- Reject unsaved product, asker, question, and responder models before persistence.
- Add regression tests for invalid configuration, moderation status, and unsaved models.
- Clarify persisted-model and configuration requirements in the README.

## [1.0.0] - 2026-11-14

- Initial release.
- Added polymorphic product questions and customer attribution.
- Added polymorphic answers and responder attribution.
- Added pending, approved and rejected moderation states.
- Added published Q&A query and transactional question deletion.
- Added Laravel 12/13 compatibility workflow and PHPUnit tests.
