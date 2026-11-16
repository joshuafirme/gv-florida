<?php

return [
    'kiosk_pending_payment_expiry_minutes' => max(
        (int) env('KIOSK_PENDING_PAYMENT_EXPIRY_MINUTES', 15),
        1
    ),
];
