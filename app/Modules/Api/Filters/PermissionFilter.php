<?php

namespace App\Modules\Api\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Modules\Api\Services\PermissionService;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        //print_r($request->user); die;
        $user = $request->user ?? null;
        if ($user === null) {
            return service('response')->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Unauthenticated (permission filter)'. $request->user]);
        }

        // Super admin bypasses all permission checks
        if ($user->tenant_id === null) {
            return;
        }

        if (empty($arguments)) {
            return; // No permission required
        }

        if (! PermissionService::hasAny((int) $user->id, $arguments)) {
            return service('response')->setStatusCode(403)->setJSON([
                'status' => false,
                'message' => 'Insufficient permissions',
                'required' => $arguments,
            ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}