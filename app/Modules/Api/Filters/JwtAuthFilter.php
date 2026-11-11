<?php

namespace App\Modules\Api\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Api\Libraries\JwtService;

class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (! preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Missing Bearer token']);
        }

        $jwtService = new JwtService();
        $payload = $jwtService->validateAccessToken($matches[1]);

        if ($payload === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Invalid or expired token']);
        }

        // Load full user record for downstream filters
        $db = db_connect();
        $user = $db->table('cd_users')
            ->where('id', $payload->sub)
            ->where('status', 'active')
            ->get()
            ->getRow();

        if ($user === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'User not found or inactive']);
        }

        // Attach user to request for TenantFilter
        $request->user = $user;
        $request->user->branch_id = $payload->branch_id ?? null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}