<?php

declare(strict_types=1);

namespace Profesia\Psr\Middleware\Test\Integration\Assets;

use Google\Client;

class NullClient extends Client
{
    public function verifyIdToken($idToken = null)
    {
        if ($idToken === false) {
            return false;
        }

        return [];
    }
}
