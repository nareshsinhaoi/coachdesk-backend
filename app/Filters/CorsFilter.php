<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin !== '' && ! in_array($origin, $this->allowedOrigins(), true)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => false, 'message' => 'Origin not allowed']);
        }

        if ($origin !== '') {
            $this->addCorsHeaders(service('response'), $origin);
        }

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = service('response')->setStatusCode(204);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin !== '' && in_array($origin, $this->allowedOrigins(), true)) {
            $this->addCorsHeaders($response, $origin);
        }

        return $response;
    }

    /**
     * @return list<string>
     */
    private function allowedOrigins(): array
    {
        $configuredOrigins = env(
            'CORS_ALLOWED_ORIGINS',
            'http://localhost:3000,http://localhost:4002,http://localhost:5173'
        );

        return array_values(array_filter(array_map('trim', explode(',', $configuredOrigins))));
    }

    private function addCorsHeaders(ResponseInterface $response, string $origin): void
    {
        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, X-Requested-With');
        $response->setHeader('Access-Control-Max-Age', '86400');
        $response->setHeader('Vary', 'Origin');
    }
}
