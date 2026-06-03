<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        // Only rate-limit credential submissions — never the GET that just
        // renders the login form (otherwise refreshing the page burns the quota).
        if (strtoupper($request->getMethod()) !== 'POST') {
            return null;
        }

        $throttler = service('throttler');
        $ip        = $request->getIPAddress();
        $key       = 'login_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $ip);

        // Allow 5 login attempts per minute per IP; block further attempts.
        if ($throttler->check($key, 5, MINUTE) === false) {
            $retryAfter = $throttler->getTokenTime();

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setBody('Muitas tentativas de login. Tente novamente em ' . $retryAfter . ' segundo(s).');
        }

        return null;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ): void {
        // No post-processing required
    }
}
