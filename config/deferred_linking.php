<?php

return [
    /*
     * Admin key for creating/managing apps.
     * Set DEFERRED_LINKING_ADMIN_KEY in .env
     */
    'admin_key' => env('DEFERRED_LINKING_ADMIN_KEY', ''),

    /*
     * Default domain used when an App has no custom_domain.
     */
    'default_domain' => env('DEFERRED_LINKING_DEFAULT_DOMAIN', 'localhost'),

    /*
     * How long (in hours) a deferred link is valid.
     */
    'deferred_ttl_hours' => (int) env('DEFERRED_LINKING_TTL_HOURS', 24),
];
