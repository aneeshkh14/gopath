# UI/UX audit and improvements — 2.0.3

Base: `claude/blissful-wozniak-6tqprs` at `55b8bbd`. Implementation branch: `codex/ui-ux-audit`.

## Scope and evidence

This is a broad source review with targeted interaction regression tests, **not a claim that every possible session or device has passed end-to-end testing**. The repository contains 89 PHP files, nine external JavaScript files, 30 screen templates plus the portal layout, and 17 admin views.

The existing public homepage and its test-series/sign-in path were inspected in the browser. The homepage's secondary CTA was unreadable in the light theme. Authenticated screens were reviewed from their templates, scripts and handlers. No staging WordPress installation, student/admin credentials, payment sandbox credentials or email delivery environment was available. The modified branch has not been deployed or browser-rendered in WordPress.

## Implemented improvements

| Area | Problem addressed | Result |
| --- | --- | --- |
| Navigation | Competing menu handlers, inaccessible mobile drawer, incorrect custom-test link | One drawer controller, Escape/backdrop close, focus return/trap, inert hidden navigation, correct random-test and year-wise links |
| Discovery | Incomplete search coverage, unclear filter results, inaccessible details modal | Search includes horizontal cards; combined filters show counts, no-match state and reset; details dialog supports keyboard use |
| Home/dashboard | Invisible light-theme CTA, unsupported statistics, motion and wheel hijacking | Theme-aware CTA, honest feature descriptions, manual carousel, no wheel interception; inactive slides excluded from keyboard navigation |
| Authentication | Fragile OTP entry, missing frontend AJAX URL, poor password discoverability | Pasteable six-digit OTP field, explicit verification, duplicate prevention, network recovery, password visibility controls and autocomplete |
| Exam setup | Start enabled without a valid custom selection; configuration failures stuck loading | Consent and topic validation, empty-topic exclusion, string-ID compatibility, readable errors and retry |
| Exam answers | Concurrent writes could overwrite the attempt answer map; offline saves silently failed | Serialized saves, newest-value tracking, local pending recovery, online retry, visible sync status, numeric answer restoration |
| Exam submission | Redirect despite failure; edited text not captured; retries could write to a closed attempt | Flush before submit, require server confirmation, retry the idempotent submission endpoint after an ambiguous response, warn before leaving unsynced work |
| Checkout | Duplicate checkout implementation; stale coupon totals; repeated orders; unclear verification failures | One canonical checkout, coupon revision tracking, order lock while gateway is open, same-payment verification retry and payment-ID guidance |
| Account/support | Missing labels, hidden invalid fields, weak error recovery | Associated labels, reveal invalid profile section, safe error text, retained values, request timeouts, avatar-upload failure feedback |
| Notifications | Duplicate mark-all IDs, panel closed during internal actions | Shared action classes, proper bell button/expanded state, keyboard items, outside/Escape dismissal and a full-list link |
| Learning | Dead class actions, inaccessible skill cards, silent doubt errors | Working schedule/support/class links, keyboard activation, escaped doubt text and actionable request failures |
| Mobile/accessibility | Unbounded dialogs, hidden mobile search, rigid grids and missing focus feedback | Bounded dialogs, visible mobile search, stacked support/pass/profile-stat grids, reduced motion and focus outlines |
| Admin | Stale enrollment loads, save enabled before data arrived, missing error recovery | Ignore another student's stale response, block premature saves, retain selections on failure, dialog naming/focus, scrollable tables, clearer action labels |

## Screen inventory

“Source” below means source inspection, not a successful logged-in browser session. Shared navigation, theme and dialog changes affect multiple screens.

| Screen/view | Review and implementation coverage |
| --- | --- |
| Home | Public browser baseline; CTA contrast, copy and responsive stat wrapping |
| Login | Public sign-in path baseline; OTP/password/error handling regression tests |
| Register | Source; labels, autocomplete, reveal password |
| Forgot password | Source; labels, autocomplete, reveal password; email delivery pending |
| Dashboard main | Source; manual carousel, accessible slide state, search and navigation tests |
| Browse tests | Source; combined search/filter/reset and dialog interaction tests |
| Get pass | Source; responsive plan grid; routes through checkout |
| My purchases | Source; shared navigation/theme; entitlement combinations pending |
| Orders | Source; shared navigation/theme; payment-history data states pending |
| Results | Source; added discoverable sidebar route; live scoring/data states pending |
| Profile | Source; invalid hidden section, duplicate submit and error retention tests |
| Notifications | Source; panel keyboard/dismissal tests; full-list action markup |
| Support | Source; labels, status feedback, mobile grid |
| Policies | Source; accessible before login; back/forward section selection |
| About | Source; shared theme/navigation |
| PYQs | Source; year-wise deep-link selection |
| Rank predictor | Source; explicitly labels estimates illustrative; removes unsupported certainty |
| Skill academy | Source; keyboard cards, counts from available data, removes invented price claims |
| Supercoaching | Source; shared navigation/theme; content/entitlement states pending |
| Lectures | Source; shared navigation/theme; content/entitlement states pending |
| Watch | Source; named doubt input, escaped content, load/post failure feedback |
| Live classes | Source; schedule anchor, working educator link, accurate class-link action |
| Typing test | Source; correct AJAX URL, keyboard-accessible duration controls, save failure feedback |
| Exam instructions | Source; custom configuration, consent, retry regression tests |
| Exam window | Source; save ordering, offline/reload/storage-denied and submission tests |
| Exam result review | Source; live attempt/scoring and long-content states pending |
| Exam series view | Source; shared navigation/theme; paid/free/expired access pending |
| Checkout | Source; coupon races, repeated order prevention, failed verification and gateway errors tested with mocks |
| Payment success | Source, including shortcode-rendered status; plainer confirmation copy |
| Payment failure | Source, including shortcode-rendered status; charged-but-unconfirmed guidance |
| Portal layout | Source; mobile drawer, search, notification and resize DOM interaction tests |

Admin views reviewed: dashboard, categories, questions, tests, courses, lessons, lectures, live classes, students, attempts, reports, payments, coupons, notifications, doubts, violations and settings. Shared dialog/table/focus improvements apply across their existing markup. Targeted tests cover enrollment load/save failures, question retrieval failure and modal focus restoration. CRUD, uploads, email, exports and destructive settings were not exercised against a live database.

## Repeatable verification

Run `npm ci --ignore-scripts`, `npm run lint`, and `npm test` with Node 22 or newer. Development dependencies are not needed by WordPress. CI also runs PHP syntax checks.

- **52 passing DOM interaction tests** using actual frontend scripts, JSDOM and mocked AJAX/fetch/Razorpay responses.
- **89 production PHP files and four test PHP files** parsed by both the PHP parser and the PHP WebAssembly runtime's native `TOKEN_PARSE` tokenizer.
- **Nine external JavaScript files and 25 static inline scripts** parse successfully. All 30 template/admin inline script or JSON blocks also parse after PHP rendering with deterministic fixtures, including the PHP-generated blocks. Shortcode-embedded strings are covered by PHP parsing.
- `git diff --check` passes.
- Asset version bumped to **2.0.2** so browsers request updated scripts/styles.
- Build script excludes test dependencies, CI and audit documents from the installable plugin ZIP.

These tests establish deterministic interaction behavior. They do not establish screen-reader compatibility, CSS layout correctness across real devices, delivery of email/OTP, real payment processing or backend authorization.

## Remaining staging work and known risks

1. Install the branch on staging and run student/admin journeys in light/dark mode at narrow phone, tablet and desktop sizes, with keyboard-only navigation, 200% zoom and a screen reader. Check long Hindi/English content, empty/large datasets and nested exam calculator/instruction/question-paper dialogs.
2. Exercise account creation, OTP expiry/resend, session expiry and password reset with actual email delivery. Verify avatar restrictions and admin impersonation flows.
3. Run Razorpay sandbox success, dismiss, rejection, delayed webhook, free enrollment and charged-but-verification-response-lost scenarios against WordPress. Verification recovery now survives a refresh in the same tab using session storage scoped by account and item. Closing the tab or blocking storage still requires support/server reconciliation. Payment transaction guarantees require transactional (InnoDB) tables; verify the staging schema and WordPress object-cache behavior for pass purchases.
4. **Sectional timer defects found in the first pass are fixed.** Six new regressions cover resume, untimed remainder, section transition, heartbeat, exhausted sections and device-sleep expiry. Validate against real configured exams before release: an untimed section currently consumes the remaining total time, so later sections after it are not reachable by timing policy.
5. Verify results, percentile/rank calculations, paid/free/expired entitlements, media playback, completion tracking, support delivery and all admin CRUD with seeded staging records. The predictor remains illustrative, not a validated statistical forecast.
6. Modern browser `inert`, `focus-visible` and dynamic viewport units are used. Older-browser support and assistive-technology behavior need real-device validation.

Do not treat this audit as an exhaustive test certification. The pull request remains a draft until the staging checks above are completed.

## Second pass — deeper reliability checks

The second pass revisited all external scripts, inline interaction scripts and the existing screen inventory. Additional fixes:

- OTP generation now actually calls WordPress email delivery, reports mail failure, preserves unchecked Remember me, expires consistently after five minutes and invalidates a code after five incorrect attempts.
- Exam clocks use a wall-time anchor, resume the correct section, keep global heartbeats separate from sectional countdowns, and submit at expiry without waiting for a click. Stored WordPress site-local start dates are converted to UTC before duration comparisons.
- Calculator results require explicit transfer into an answer; division by zero is rejected. Auxiliary and security dialogs receive keyboard focus handling. Exiting captures unblurred text and the security exit uses that same save-aware path.
- Payment verification locks the exact order and commits access, order status and coupon use together. Replays do not grant extra attempts again. Missing order mappings no longer fall back to an unrelated pending order. Free enrollment records completion and full-discount coupon usage; a coupon that expires after preview blocks checkout instead of silently charging full price.
- Checkout recovery survives same-tab reload and stays locked after successful verification. Notification failures preserve unread state, and mirrored items share a pending-request lock.
- Admin coupon status controls and lesson preview now work. The payment month selector is generated from recorded months and actually filters results; hardcoded gateway connectivity claims were removed. Stale enrollment loads/saves cannot overwrite another dialog session.
- Learning/support requests guard duplicate actions. Course filters announce empty results. Typing completion saves once and ignores late errors from a previous session. Result charts tolerate library failure, negative scores and reduced-motion preferences.
- Numerical grading rejects nonnumeric strings, and free-text answers no longer inherit MCQ option aliases.

Additional verification commands: `php tests/php-regression.php` runs 19 isolated handler scenarios with WordPress I/O stubbed (no real mail or payments). The CI payment-database job runs `tests/php-mysql.php` against a disposable MySQL 8 InnoDB schema, testing replay, rollback/retry, free/full-discount enrollment and simultaneous callbacks. All four MySQL scenarios passed in GitHub Actions run 34746936327; the UI/PHP job also passed. This verifies actual transaction rollback and parallel-callback behavior against MySQL, while WordPress service functions and payment-provider calls remain isolated.

Browser limitation: the cloud browser explicitly rejected isolated local/data previews under its URL security policy. No alternative browser or network workaround was used. This pass therefore adds executable DOM/PHP/database evidence, not claims of visual certification on every screen size. A staging URL and authenticated test accounts remain necessary for that part of the user's requested coverage.

## Third pass — interrupted sessions and real WordPress rendering

Additional fixes cover timed-section navigation through Next/Previous/palette, delayed heartbeats that rewind the clock, keyboard radio answers, immediate local text-draft recovery, submission editing locks, retained custom-topic selections across subjects, profile/avatar recovery, and malformed checkout responses.

Transaction history now has a real dashboard route, lists tests/courses/passes through the current schema and keeps deleted-item orders visible. Purchasing a course no longer marks an unrelated test with the same numeric ID as owned. Result summaries use the latest answered response per question and the exam engine's grading rules. New submissions snapshot their maximum marks; history uses the recorded percentage. Narrow-screen result/lecture grids and unavailable lecture links were corrected.

Local verification at this checkpoint: 69 DOM cases, 22 PHP handler/data cases and syntax checks pass. The database suite now adds order-history isolation and overlapping item-ID coverage. A new CI job installs disposable WordPress and renders guest, student and admin screens against MySQL, using empty and populated fixtures. Its results will be recorded after execution. These server-rendering checks do not execute a browser or establish device layout correctness.
