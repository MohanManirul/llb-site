<?php

return [

    /*
     * Study material PDFs live on their own disk, separate from
     * `filesystems.default`. They are private: nothing links to them directly,
     * every read goes through the counted download route. Point this at `s3`
     * to move storage without touching a single URL in the API contract.
     */
    'material_disk' => env('LLB_MATERIAL_DISK', 'local'),

    'max_pdf_kb' => (int) env('LLB_MAX_PDF_KB', 51200),

    'locales' => ['bn', 'en'],

    'fallback_locale' => 'bn',
];
