<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class BearerTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $expected = getenv('encryption.key');
        $header   = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $header, $m) || $m[1] !== $expected) {
            log_message('warning', '[api.auth.401] ip={ip} uri={uri}', [
                'ip'  => $request->getIPAddress(),
                'uri' => $request->getUri()->getPath(),
            ]);
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['data' => null, 'meta' => (object) [], 'errors' => 'Unauthorized']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void {}
}
