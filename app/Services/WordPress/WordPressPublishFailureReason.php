<?php

namespace App\Services\WordPress;

/**
 * Why an article could not be published to the owner's WordPress site.
 * Distinct cases so the dashboard can say what to fix, rather than showing
 * one generic publishing error.
 */
enum WordPressPublishFailureReason: string
{
    /** Site URL, username or application password is missing. */
    case IncompleteSettings = 'incomplete_settings';

    /** The site could not be reached (DNS, TLS, timeout, refused). */
    case Unreachable = 'unreachable';

    /** WordPress rejected the username / application password pair. */
    case AuthenticationFailed = 'authentication_failed';

    /** Authenticated, but the account may not create posts or upload files. */
    case NotPermitted = 'not_permitted';

    /** The featured image could not be uploaded to the media library. */
    case MediaUploadFailed = 'media_upload_failed';

    /** WordPress refused or mishandled the post itself. */
    case PostCreationFailed = 'post_creation_failed';
}
