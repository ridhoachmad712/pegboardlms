<?php

return [
    // Redemption deadline only. An activated lecturer account never expires.
    'code_valid_days' => max(1, (int) env('LECTURER_ACTIVATION_CODE_DAYS', 7)),
    'support_email' => env('LICENSING_SUPPORT_EMAIL'),
];
