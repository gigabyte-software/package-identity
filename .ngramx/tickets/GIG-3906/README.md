# GIG-3906: Reusable login codes until expiry in laravel-identity package

## Summary

Move Earl Kendrick’s Safe Links / link-preview hotfix into `gigabyte/laravel-identity`: short-lived login codes from `sendLoginCode()` stay valid until `expires_at` instead of being deleted on first successful verify.

## Requirements

- Config `code_reusable_until_expiry` (`IDENTITY_CODE_REUSABLE_UNTIL_EXPIRY`, default `true`).
- Update `consumeLoginCode()` when flag enabled for short-lived codes only.
- Long-lived codes (e.g. invites) remain single-use via lifetime heuristic.
- Package tests for double verify, expiry, flag off, long-lived single-use.
- Tag and release minor version (follow-up after merge).

## Changes

- Added `code_reusable_until_expiry` to published config and README env table.
- `IdentityService::consumeLoginCode()` skips deletion for short-lived codes when reuse is enabled; long-lived rows still single-use via lifetime heuristic.
- `LoginCode` allows mass-assigning `created_at` for app-created invite rows.
- Extended `AuthTest` for double verify, magic token reuse, flag off, and long-lived single-use.
- Adjusted magic-token sibling test to run with reuse disabled (prior single-use behaviour).
