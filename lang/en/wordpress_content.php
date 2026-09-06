<?php

return [

    'page_title' => 'WordPress content',

    'generate_section_title' => 'Generate an article',

    'guard_not_connected' => 'The WordPress connection has not been proven to work yet.',
    'guard_go_to_settings' => 'Go to Settings to test the connection',
    'guard_generation_disabled' => 'Content generation is currently turned off. Please try again later.',
    'guard_quota_exhausted' => 'This month\'s allowance is used up.',
    'guard_already_running' => 'An article is already being generated for this company.',
    'guard_no_topic_available' => 'No trending topic could be found right now. Please try again later.',

    'quota_used' => ':used of :limit used this month',
    'quota_resets_at' => 'Resets :date',

    'language_label' => 'Language',
    'mode_label' => 'Topic mode',

    'generate_button' => 'Generate article',
    'generating' => 'Generating...',
    'generation_queued' => 'The article has been queued. It will appear below once it is published.',

    'history_title' => 'Generated articles',
    'history_empty' => 'No articles have been generated yet.',

    'status_queued' => 'Queued',
    'status_generating' => 'Generating',
    'status_published' => 'Published',
    'status_failed' => 'Failed',

    'publish_failed_incomplete_settings' => 'The WordPress settings are incomplete.',
    'publish_failed_unreachable' => 'The site could not be reached at the time of publishing.',
    'publish_failed_authentication_failed' => 'WordPress rejected the stored credentials.',
    'publish_failed_not_permitted' => 'The WordPress account is not allowed to publish posts or upload files.',
    'publish_failed_media_upload_failed' => 'The featured image could not be uploaded to WordPress.',
    'publish_failed_post_creation_failed' => 'WordPress refused to create the post.',

    'trend_failed_not_configured' => 'Trending topics are not available on this installation.',
    'trend_failed_no_seed_topic' => 'The company needs a category or a name before a trending topic can be found.',
    'trend_failed_request_failed' => 'The trending topics service could not be reached.',
    'trend_failed_no_candidates' => 'No trending topics were found for this company\'s field right now.',

];
