<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $throttler = service('throttler');
        $ip        = $request->getIPAddress();
        $key       = 'login_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $ip);

        // Allow 5 attempts per minute per IP
        if (! $throttler->check($key, 5, MINUTE)) {
            return service('response')->setStatusCode(429)->setBody('Too Many Requests');
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
