<?php

return [
    /**
     * Working days as Carbon weekday integers (0 = Sunday, 6 = Saturday).
     * Default: Monday through Friday.
     */
    'working_days' => [1, 2, 3, 4, 5],

    /**
     * Daily working window in 24h format. SLA timers are paused outside this
     * window and resumed at start_hour the next working day.
     */
    'start_hour' => 9,
    'end_hour'   => 18,

    /**
     * Application timezone for SLA calculations. Defaults to APP_TIMEZONE
     * (Asia/Kuala_Lumpur in production for NRH).
     */
    'timezone' => env('BUSINESS_HOURS_TZ', env('APP_TIMEZONE', 'Asia/Kuala_Lumpur')),
];
