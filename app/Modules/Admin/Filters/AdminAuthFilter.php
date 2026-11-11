<?php

namespace App\Modules\Admin\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


// Inline fallback — ensures the function exists inside the filter
if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        $uid = session()->get('user_id');
        if (! $uid) return false;
        $user = db_connect()->table('cd_users')->where('id', $uid)->get()->getRow();
        return $user !== null && $user->tenant_id === null;
    }
}
if (! function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}


class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('user_id')) {
            return redirect()->to('/admin/login')->with('error', 'Please log in.');
        }

        $url = uri_string();

        // If super admin tries to access a tenant-only page, redirect to dashboard
        if (is_super_admin()) {
            $tenantOnlyPrefixes = [
                'admin/students',
                'admin/courses',
                'admin/batches',
                'admin/teachers',
                'admin/attendance',
                'admin/fees',
                'admin/exams',
                'admin/settings'
            ];
            foreach ($tenantOnlyPrefixes as $p) {
                if (str_starts_with($url, $p)) {
                    return redirect()->to('/admin/dashboard')
                        ->with('info', 'Super Admin has no tenant context. Use Tenants below.');
                }
            }
            return;   // Super admin can access everything else (dashboard, tenants, plans)
        }

        // Permission check
        if (! empty($arguments)) {
            $userId = session()->get('user_id');
            $db = db_connect();
            foreach ($arguments as $perm) {
                $has = $db->table('cd_user_roles ur')
                    ->join('cd_role_permissions rp', 'rp.role_id = ur.role_id')
                    ->join('cd_permissions p', 'p.id = rp.permission_id')
                    ->where('ur.user_id', $userId)
                    ->where('p.permission_key', $perm)
                    ->countAllResults();
                if ($has > 0) return; // one match = allow
            }
            return redirect()->to('/admin/dashboard')
                ->with('error', 'You do not have permission for that action.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
