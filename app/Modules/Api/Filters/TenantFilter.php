<?php

namespace App\Modules\Api\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Api\Libraries\TenantContext;

class TenantFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // This filter runs AFTER JwtAuthFilter has set the user in request
        $user = $request->user ?? null;

        if ($user === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Unauthenticated@']);
        }

        // Super admin has tenant_id = null (platform level)
        if ($user->tenant_id === null) {
            TenantContext::set(null, null, $user->id);
            return;
        }

        // Regular tenant user: enforce tenant_id from authenticated user
        // NEVER trust tenant_id from request body/headers
        TenantContext::set(
            (int) $user->tenant_id,
            isset($user->branch_id) ? (int) $user->branch_id : null,
            (int) $user->id
        );
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        TenantContext::reset();
    }
}