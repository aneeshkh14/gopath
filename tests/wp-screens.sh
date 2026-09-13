#!/usr/bin/env bash
set -uo pipefail
failures=0
for state in empty seeded; do
    if [[ "$state" == seeded ]]; then php tests/wp-screens.php seed || exit 1; fi
    for view in main tests purchases orders live-classes lectures supercoaching skill-academy rank-predictor results pyqs notifications profile watch policies support get-pass typing-test about; do
        php tests/wp-screens.php dashboard "$view" "$state" || failures=$((failures+1))
    done
    for view in dashboard categories questions tests courses lessons lectures live-classes students attempts reports payments coupons notifications doubts violations settings; do
        php tests/wp-screens.php admin "$view" "$state" || failures=$((failures+1))
    done
    for view in test course pass; do php tests/wp-screens.php checkout "$view" "$state" || failures=$((failures+1)); done
    for view in instructions window series custom missing; do php tests/wp-screens.php exam "$view" "$state" || failures=$((failures+1)); done
    for view in main orders purchases exam; do php tests/wp-screens.php layout "$view" "$state" || failures=$((failures+1)); done
    php tests/wp-screens.php result review "$state" || failures=$((failures+1))
done
for view in login register forgot-password otp; do php tests/wp-screens.php auth "$view" || failures=$((failures+1)); done
for view in home payment-success payment-failed; do php tests/wp-screens.php page "$view" || failures=$((failures+1)); done
php tests/wp-screens.php admin-reply doubts seeded || failures=$((failures+1))
php tests/wp-screens.php journey exam seeded || failures=$((failures+1))
php tests/wp-screens.php pass-renewal pass seeded || failures=$((failures+1))
if ((failures)); then echo "$failures WordPress rendering cases failed"; exit 1; fi
echo '108 WordPress rendering and journey cases passed (plus fixture setup).'
