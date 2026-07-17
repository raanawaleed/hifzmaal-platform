<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum's EnsureFrontendRequestsAreStateful only bootstraps a
        // session (and treats the request as cookie-authenticatable) for
        // requests whose Origin/Referer matches a SANCTUM_STATEFUL_DOMAINS
        // entry — postJson() etc. send neither by default, so without this
        // every session()-touching endpoint (login, logout, 2FA challenge)
        // would throw "Session store not set on request" in tests despite
        // working fine from a real browser.
        $this->withHeader('Referer', config('app.url'));
    }
}
