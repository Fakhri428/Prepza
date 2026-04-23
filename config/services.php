<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'timeout' => (int) env('GROQ_TIMEOUT', 30),
    ],

    'service_a' => [
        'base_url' => env('SERVICE_A_BASE_URL', 'http://localhost:8000'),
        'token' => env('SERVICE_A_TOKEN'),
        'timeout' => (int) env('SERVICE_A_TIMEOUT', 20),
        'retry_times' => (int) env('SERVICE_A_RETRY_TIMES', 2),
        'retry_sleep_ms' => (int) env('SERVICE_A_RETRY_SLEEP_MS', 250),
        'fetch_statuses' => env('SERVICE_A_FETCH_STATUSES', 'queued,waiting,processing'),
        'send_queue_status' => (bool) env('SERVICE_A_SEND_QUEUE_STATUS', false),
        'enforce_priority_sequence' => (bool) env('SERVICE_A_ENFORCE_PRIORITY_SEQUENCE', true),
        'max_processing_slots' => (int) env('SERVICE_A_MAX_PROCESSING_SLOTS', 1),
        'auto_done_enabled' => (bool) env('SERVICE_A_AUTO_DONE_ENABLED', true),
        'auto_done_minutes' => (int) env('SERVICE_A_AUTO_DONE_MINUTES', 20),
        'note_max_length' => (int) env('SERVICE_A_NOTE_MAX_LENGTH', 500),

        'busy_threshold' => (int) env('SERVICE_A_BUSY_THRESHOLD', 5),
        'overload_threshold' => (int) env('SERVICE_A_OVERLOAD_THRESHOLD', 10),
        'batch_window_minutes' => (int) env('SERVICE_A_BATCH_WINDOW_MINUTES', 5),
        'target_sla_minutes' => (int) env('SERVICE_A_TARGET_SLA_MINUTES', 25),

        'complexity_penalty_multiplier' => (float) env('SERVICE_A_COMPLEXITY_PENALTY_MULTIPLIER', 2.6),
        'base_score_anchor' => (float) env('SERVICE_A_BASE_SCORE_ANCHOR', 12),
        'priority_high_threshold' => (float) env('SERVICE_A_PRIORITY_HIGH_THRESHOLD', 9),
        'priority_medium_threshold' => (float) env('SERVICE_A_PRIORITY_MEDIUM_THRESHOLD', 7),

        'aging_boost_per_5m' => (float) env('SERVICE_A_AGING_BOOST_PER_5M', 0.35),
        'aging_boost_cap' => (float) env('SERVICE_A_AGING_BOOST_CAP', 4),

        'batch_boost_per_match' => (float) env('SERVICE_A_BATCH_BOOST_PER_MATCH', 0.45),
        'batch_boost_cap' => (float) env('SERVICE_A_BATCH_BOOST_CAP', 1.8),

        'deadline_boost_late' => (float) env('SERVICE_A_DEADLINE_BOOST_LATE', 3),
        'deadline_boost_near' => (float) env('SERVICE_A_DEADLINE_BOOST_NEAR', 2),
        'deadline_boost_near_overload' => (float) env('SERVICE_A_DEADLINE_BOOST_NEAR_OVERLOAD', 2.4),
        'deadline_boost_warning' => (float) env('SERVICE_A_DEADLINE_BOOST_WARNING', 1.2),

        'jitter_steps' => (int) env('SERVICE_A_JITTER_STEPS', 11),
        'jitter_scale' => (float) env('SERVICE_A_JITTER_SCALE', 0.01),

        'trend_min_repeat' => (int) env('SERVICE_A_TREND_MIN_REPEAT', 4),
        'trend_min_repeat_gender' => (int) env('SERVICE_A_TREND_MIN_REPEAT_GENDER', 2),
        'trend_expire_minutes' => (int) env('SERVICE_A_TREND_EXPIRE_MINUTES', 180),
        'trend_placeholder_image' => env('SERVICE_A_TREND_PLACEHOLDER_IMAGE', 'https://placehold.co/1200x630/png'),
    ],

];
