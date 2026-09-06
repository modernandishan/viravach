<?php

namespace App\Services\WordPress;

/**
 * Why a connection test did not reach an authenticated WordPress site. Each
 * case maps to its own translated message on the settings page, so the owner
 * is told what to fix rather than "something went wrong".
 */
enum WordPressConnectionFailureReason: string
{
    /** The site URL, WordPress username or application password is still empty. */
    case IncompleteSettings = 'incomplete_settings';

    /** The host answered, but /wp-json/ is not a WordPress REST API root. */
    case NotWordPress = 'not_wordpress';

    /** The host could not be reached at all (DNS, TLS, timeout, refused). */
    case Unreachable = 'unreachable';

    /** WordPress rejected the username / application password pair. */
    case AuthenticationFailed = 'authentication_failed';

    /** A WordPress site answered, but with an unexpected error response. */
    case RequestFailed = 'request_failed';
}
