<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Session cookie isolation.
 *
 * The back-office deliberately does not share a session with the public
 * site: the public site renders user-submitted company content, so an XSS
 * there must not yield a valid admin session. ConfigureSessionForHost
 * enforces this, and it is easy to undo by accident when reordering
 * middleware — hence this guard.
 */
class SessionIsolationTest extends TestCase
{
    public function test_the_admin_host_gets_its_own_host_only_cookie(): void
    {
        $this->get('https://'.config('domains.admin').'/');

        $this->assertStringEndsWith('_admin', config('session.cookie'));
        $this->assertNull(config('session.domain'));
    }

    public function test_the_public_site_and_dashboard_share_one_cookie(): void
    {
        $this->get('https://'.config('domains.app').'/profile');

        $this->assertStringEndsNotWith('_admin', config('session.cookie'));
        $this->assertSame(config('domains.cookie'), config('session.domain'));
    }
}
