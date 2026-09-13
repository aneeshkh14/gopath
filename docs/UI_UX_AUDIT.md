# UI/UX audit and improvements — 2.0.3

Base: `claude/blissful-wozniak-6tqprs` at `55b8bbd`. Implementation branch: `codex/ui-ux-audit`.

## Scope and evidence

This is a broad source review with targeted interaction regression tests, **not a claim that every possible session or device has passed end-to-end testing**. The repository contains 89 PHP files, nine external JavaScript files, 30 screen templates plus the portal layout, and 17 admin views.

The existing public homepage and its test-series/sign-in path were inspected in the browser. The homepage's secondary CTA was unreadable in the light theme. Authenticated screens were reviewed from their templates, scripts and handlers. No externally accessible staging WordPress installation, student/admin credentials, payment sandbox credentials or email delivery environment was available. The third pass added a disposable WordPress 7.1/MySQL CI installation with fixture accounts. Student/admin screens and full portal layouts render there without invoking a browser. The modified branch has not been deployed to the user’s site or visually browser-tested.

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
| Orders | New navigation route; real WordPress rendering and MySQL history isolation, pass/course/test/deleted-item records |
| Results | WordPress rendering with completed attempts; consistent grading and latest-response subject summaries |
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
| Portal layout | Full WordPress rendering with empty/populated dashboard, purchases, orders and exam; mobile drawer, search, notification and resize DOM tests |

Admin views reviewed: dashboard, categories, questions, tests, courses, lessons, lectures, live classes, students, attempts, reports, payments, coupons, notifications, doubts, violations and settings. Shared dialog/table/focus improvements apply across their existing markup. Targeted DOM tests cover enrollment load/save failures, question retrieval failure and modal focus restoration. All 17 admin views render against WordPress/MySQL with empty and populated fixtures; the doubt-reply flow also persists a reply. Other admin CRUD, real uploads, email, exports and destructive settings still require staging coverage.

## Repeatable verification

Run `npm ci --ignore-scripts`, `npm run lint`, and `npm test` with Node 22 or newer. Development dependencies are not needed by WordPress. CI also runs PHP syntax checks.

- **69 passing DOM interaction tests** using actual frontend scripts, JSDOM and mocked AJAX/fetch/Razorpay responses.
- **89 production PHP files and five test PHP files** pass PHP parsing; CI also runs native `php -l`.
- **Nine external JavaScript files and 25 static inline scripts** parse successfully. All 30 template/admin inline script or JSON blocks also parse after PHP rendering with deterministic fixtures, including the PHP-generated blocks. Shortcode-embedded strings are covered by PHP parsing.
- `git diff --check` passes.
- Asset version bumped to **2.0.3** so browsers request updated scripts/styles.
- Build script excludes test dependencies, CI and audit documents from the installable plugin ZIP.

These tests establish deterministic interaction behavior. They do not establish screen-reader compatibility, CSS layout correctness across real devices, delivery of email/OTP, real payment processing or backend authorization.

## Remaining staging work and known risks

1. Install the branch on staging and run student/admin journeys in light/dark mode at narrow phone, tablet and desktop sizes, with keyboard-only navigation, 200% zoom and a screen reader. Check long Hindi/English content, empty/large datasets and nested exam calculator/instruction/question-paper dialogs.
2. Exercise account creation, OTP expiry/resend, session expiry and password reset with actual email delivery. Verify avatar restrictions and admin impersonation flows.
3. Run Razorpay sandbox success, dismiss, rejection, delayed webhook and charged-but-verification-response-lost scenarios on staging. Free/full-discount enrollment is covered by MySQL integration; pass renewal uses real WordPress user metadata in CI. Verification recovery now survives a refresh in the same tab using session storage scoped by account and item. Closing the tab or blocking storage still requires support/server reconciliation. Payment transaction guarantees require transactional (InnoDB) tables; verify the staging schema and WordPress object-cache behavior for pass purchases.
4. **Sectional timer defects found in the first pass are fixed.** Regressions cover resume, untimed remainder, section transition, delayed heartbeat, exhausted sections, device-sleep expiry and navigation at section boundaries. Validate against real configured exams before release: an untimed section currently consumes the remaining total time, so later sections after it are not reachable by timing policy.
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

Additional verification commands: `php tests/php-regression.php` runs 19 isolated handler scenarios with WordPress I/O stubbed (no real mail or payments). The CI payment-database job runs `tests/php-mysql.php` against a disposable MySQL 8 InnoDB schema, testing replay, rollback/retry, free/full-discount enrollment and simultaneous callbacks. All four MySQL scenarios passed in GitHub Actions run 34746936327; the UI/PHP job also passed. This verifies actual transaction rollback and parallel-callback behavior against MySQL, while payment-provider calls remain isolated. The third-pass WordPress suite additionally exercises real user metadata and pass renewal.

Browser limitation: the cloud browser explicitly rejected isolated local/data previews under its URL security policy. No alternative browser or network workaround was used. The later WordPress CI job performs server-side PHP rendering only. This pass therefore adds executable DOM/PHP/database evidence, not claims of visual certification on every screen size. A staging URL and authenticated test accounts remain necessary for that part of the user's requested coverage.

## Third pass — interrupted sessions and real WordPress rendering

Additional fixes cover timed-section navigation through Next/Previous/palette, delayed heartbeats that rewind the clock, keyboard radio answers, immediate local text-draft recovery, submission editing locks, retained custom-topic selections across subjects, profile/avatar recovery, and malformed checkout responses.

Transaction history now has a real dashboard route, lists tests/courses/passes through the current schema and keeps deleted-item orders visible. Purchasing a course no longer marks an unrelated test with the same numeric ID as owned. Result summaries use the latest answered response per question and the exam engine's grading rules. New submissions snapshot their maximum marks; history uses the recorded percentage. Narrow-screen result/lecture grids and unavailable lecture links were corrected.

The first real WordPress run exposed 22 failed rendering cases from shared dashboard query fields, a student-page typing-table creation path and an ambiguous admin doubt query. Fixes now select the needed test ID, avoid test-only columns in course queries, create typing history during activation/migration (schema 1.1.9), and use the correct doubt status and reply timestamp fields. Purchase cards use the engine's attempt allowance. Pass renewals preserve existing access time and serialize per-account updates; checkout permits renewal after an earlier purchase.

Verification is now organized into four suites:

| Suite | Cases | Scope |
| --- | ---: | --- |
| Frontend DOM | 69 | Actual scripts with simulated AJAX, gateway and clock events |
| Isolated PHP | 24 | Actual handlers/data logic with WordPress I/O fixtures |
| MySQL integration | 5 | Payment replay, rollback, free enrollment, simultaneous callback and account-scoped order history |
| WordPress 7.1 + MySQL | 109 | 105 server-rendered screen/layout states, exam submission, admin doubt reply, active-pass renewal and simultaneous pass renewals |

The WordPress suite covers all 19 dashboard routes, 17 admin views, three checkout types, exam instructions/window/series/custom/missing-item states, result review, four auth states, three public/status pages and four full portal layouts. Empty and populated cases include missing-item fallbacks; populated cases use fixture students, completed/in-progress exams, courses, lessons, typing history, notifications and orders. They are rendering checks, not 109 browser journeys. Mail and external provider calls are disabled.

Run `php tests/php-regression.php` for isolated PHP tests. The workflow provisions the two disposable databases; `bash tests/wp-screens.sh` requires `GEP_TEST_WORDPRESS_ROOT` and refuses any database name except `gep_wordpress`. WP-CLI installation follows the [official download](https://developer.wordpress.org/cli/commands/core/download/) and [installation](https://developer.wordpress.org/cli/commands/core/install/) commands, with email notification disabled.

CI run [34748491520](https://github.com/aneeshkh14/gopath/actions/runs/34748491520) passed all 108 WordPress cases present at that revision, including full layouts, exam submission, admin replies and renewal. The additional concurrent-renewal case and the 24-case PHP suite are included in the final PR checks. The PR links the final run for its latest revision. These checks do not execute a browser or establish device layout correctness.


## Customer feedback follow-up — 13 September 2026 (v2.0.4)

Written reports: question scrolling fails; the full-size palette change only applies on phones; payment and test creation require further verification. The attached voice note was not transcribed because no audio transcription capability was available.

Implemented:

- A single scrolling owner per layout: the exam viewport on small screens, the question pane inside a bounded desktop grid on laptops. Clear inherited question max-height constraints, preserve full question/options height, and route scroll controls/navigation resets to that owner. This also avoids depending on the outer WordPress document scrolling during fullscreen.
- Full-size question palette on desktop as well as mobile/tablet, with pinned actions, a scrolling grid, accessible dialog focus, explicit close, Escape, background inertness, resize handling, and safe transitions to submission.
- Atomic attempt initialization with an immutable question list. Retries resume the existing random/PYQ paper; a different PYQ selection cannot overwrite a running paper. Validate custom question counts before consuming an attempt.
- Generated PYQ practice saves, heartbeat, grading and review now work without a physical test record. Preserve practice metadata in result snapshots. Serialize concurrent starts, answer writes and grading; reject answers outside the attempt's question list.
- Validate test creation before changing links; save test metadata and links in one transaction. Reject missing IDs, duplicate section questions, impossible sectional timing and unsupported/self-referencing series. Preserve the submitted form on failure. Keep empty drafts visible to admins and unavailable to students. Total marks use section overrides. Removed nonfunctional per-test security controls and the duplicate exam_mode field that overwrote exam presets; link to actual portal security settings.
- Stop checkout before charging when configuration/order persistence fails, a product is unavailable, or a random-test package has no purchased attempts. Honor the free flag even when a previous price remains stored; free random papers use their configured attempt limit and do not require a purchased package.

Validation suite at this revision: 93 frontend DOM/CSS cases, 32 isolated PHP cases, 5 MySQL payment cases, and 125 WordPress cases (105 rendered states plus 20 workflows). The 16 new WordPress workflows include practice lifecycle/retries, random selection validation, concurrent starts/saves/submission, admin create/edit/draft/rollback/series validation, and payment availability/free enrollment. Final CI outcome is linked from the PR.

The viewport cases inspect DOM behavior and stylesheet cascade, not browser-rendered geometry. Actual touch/wheel scrolling, fullscreen, long bilingual passages, tablet rotation, mobile keyboards, 200% browser zoom, screen readers, Razorpay sandbox, external email and authenticated staging remain release checks. The browser's earlier local/data preview security block was not bypassed. Nothing in this follow-up certifies all possible device or payment-provider cases.

## Visual consistency follow-up — v2.0.5

The supplied desktop screenshot was not an acceptable finished dashboard: an oversized promotion dominated the page, the active carousel indicator became a white square, sections lost their gutters, and a narrow test card sat in a mostly empty row. This pass replaces the carousel with a compact study area, a real resume action (including PYQ practice), explicit completed-test counts and wrapping test cards with complete titles. Configurable promotions remain available further down the page. Recent results link directly to their review and use recorded mark totals; score percentage is no longer labelled accuracy.

The screen inventory above was rechecked in source. Shared content gutters now apply consistently, nested wrappers no longer double-pad, and theme dependencies are ordered after route styles. Test browsing, course browsing, purchases, live classes, skills and series grids can shrink below their previous 300–360px minimums. Explicit admin grid hooks collapse forms/reports at narrower widths, including dynamically added test sections. Both payment outcomes receive the same independent shared layout. The public homepage uses concrete preparation copy, a shorter hero and theme-aware surfaces/text.

Verification adds 12 frontend checks (eight viewport stylesheet cascades, two independent payment layouts and two palette contrast checks) and six real WordPress checks (new learner, completed counts, active test, virtual practice, recorded result link/marks and asset order). The suite now contains 105 frontend cases, 32 isolated PHP cases, 5 MySQL payment cases and 131 WordPress cases: **273 total** when all CI jobs pass. The 131 WordPress cases consist of 105 rendered screen/layout states plus 26 targeted workflow/semantic checks. Final CI outcome is recorded in the follow-up PR.

These checks do not render browser geometry. JSDOM checks CSS fallback declarations and supported cascade behavior; it does not resolve every modern CSS function or simulate touch/zoom. The supplied screenshot was inspected directly, but there are no claimed after-screenshots. Authenticated staging at phone/tablet/laptop sizes, actual exam scrolling/fullscreen, assistive technology, and Razorpay sandbox remain necessary before visual/provider sign-off. The browser's explicit local/data preview security block was respected.

## Authenticated live audit follow-up — v2.0.6

A desktop browser audit of the deployed v2.0.5 student portal found real defects that the previous DOM/rendering suite had missed. The dashboard was checked in both themes. Navigation, catalog search/no matches/clear, test-detail dialog and Escape, year/topic browsing, purchases, transactions, results and a result review, profile, notifications, support, policies, about, live classes, lectures, coaching, skills, typing setup, pass selection, checkout item/price, invalid coupon recovery and secure exam entry were inspected. No real payment, support message, profile change or exam answer/submission was made. One fresh PYQ practice attempt was created and exited through the dashboard link, leaving it unfinished. No private account data is included here.

Confirmed fixes:

- Result review uses the attempt's recorded mark total, matching history even for virtual PYQ papers. Score percentage is labelled correctly. Practice/custom sets do not share a misleading highest-score/percentile comparison. A lone learner is not assigned a fabricated 100th percentile. Retake routes through exam access checks, or PYQ selection for virtual papers, rather than checkout for a nonexistent product.
- The full question-palette opener is visible on desktop; the earlier fixture omitted its production class and therefore missed the hiding rule. Secure-entry instructions retain readable text on their light card in dark mode. Fullscreen denial/unsupported browsers receive an explicit error while preserving the security gate and stating that the timer is running. Exam-only sidebar width no longer changes the portal navigation width on result pages.
- Exhausted purchase cards show their actual state and link to results. Already running attempts remain resumable even with no new attempts remaining.
- Active mock-test passes are accepted at exam entry consistently with the catalogue; expiry still denies access. Test limits and custom-test packages remain separate. Pass/course/checkout copy no longer promises unsupported extra attempts, included courses, fabricated asset counts, VIP teachers or round-the-clock support.
- Missing custom templates receive an explanation and PYQ practice alternative. Empty catalog filters also offer PYQs. Labels identify featured tests and the rank estimator accurately; practice size caps are disclosed.

Verification adds four fullscreen failure/retry DOM cases, four recorded-total PHP cases, and seven real WordPress regressions for result totals, practice comparisons/retake, exhausted and running purchases, unavailable custom templates, subscriber pass expiry/access and single-learner percentile. Eight existing CSS cases now include the real palette button class and assert visibility. The suite contains **109 frontend + 36 isolated PHP + 5 MySQL + 138 WordPress = 288 cases** when all CI jobs pass. CI outcome is recorded in the follow-up PR.

Release limitations: the authenticated account has administrator privileges, so student entitlement behavior is validated with subscriber fixtures rather than certified from this account. No actual payment-provider success/cancel flow or mobile/tablet browser geometry was exercised. Desktop fullscreen was unavailable in the audit browser; its gate was not bypassed, so in-exam wheel/touch scrolling and a complete live exam remain unverified. The timer starts before secure initialization and continues if fullscreen fails; feedback now discloses this, but changing the attempt lifecycle requires a separate design and migration review. Published custom templates/content require administrator configuration. These changes are a follow-up candidate, not a claim that every possible screen, device, or provider edge case is production-certified.

## Admin source audit follow-up — v2.0.7

PR #3 was merged into `main` at the user's request. A fresh attempt to open the live dashboard and homepage still returned `502 Bad Gateway — connection closed` before a login form. No admin credentials were entered or stored and no live administrative data was changed. The following findings come from source inspection and isolated tests, not a completed live admin audit.

- Coupon creation previously redirected to “saved” even after a failed database insert. Failed writes and invalid values now keep the modal open, retain entered fields and show a specific error. Server validation rejects empty/duplicate codes, unknown discount types, nonpositive discounts, percentages over 100, invalid limits/dates/statuses. Decimal discounts are supported. Labels are associated with their inputs and the modal close button has an accessible name.
- A selected coupon expiry includes that whole day in the site timezone; checkout expiry compares against the same timezone. This avoids date-only midnight expiry and UTC/site-time disagreement.
- Admin payment history had a hard limit of 50 with no pagination. It now provides counted pages, stable ordering and preserved month filters. Orders for deleted users remain visible. Pass names use pass IDs instead of accidentally looking up an unrelated test with the same ID. Missing products and gateway references have explicit labels, amounts retain paise, and gateway setup distinguishes incomplete configuration from configured keys without claiming provider availability.

New verification: two isolated PHP form-failure cases and eight WordPress admin cases covering valid/invalid/duplicate coupons, retained form data, status changes, expiry in both positive and negative UTC offsets, deleted-user/pass/decimal payment records, and a 51-order paginated month. With the existing suite, the total is **109 frontend + 38 isolated PHP + 5 MySQL + 146 WordPress = 298 cases** when CI passes. The existing admin test creation/edit/draft/rollback and screen rendering cases remain in the suite. Final CI evidence is recorded in the admin follow-up PR.

Live admin layout, actual sign-in and end-to-end admin workflows remain blocked by site availability. No hosting configuration, real coupon/order records, test content, users, payments or messages were changed on the live site. Coupon-code uniqueness is checked during normal creation; simultaneous identical-code submissions still require a database uniqueness migration for an absolute concurrency guarantee.
