<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class TenantController extends BaseController
{
    public function index()
    {
        if (! is_super_admin()) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $tenants = $db->query("
            SELECT t.*,
                   (SELECT COUNT(*) FROM cd_students WHERE tenant_id = t.id AND deleted_at IS NULL) AS student_count,
                   (SELECT COUNT(*) FROM cd_branches WHERE tenant_id = t.id AND deleted_at IS NULL) AS branch_count,
                   (SELECT COUNT(*) FROM cd_users    WHERE tenant_id = t.id AND deleted_at IS NULL) AS user_count,
                   (SELECT p.name FROM cd_tenant_subscriptions ts
                    JOIN cd_subscription_plans p ON p.id = ts.plan_id
                    WHERE ts.tenant_id = t.id
                    ORDER BY ts.id DESC LIMIT 1) AS current_plan,
                   (SELECT ts.end_date FROM cd_tenant_subscriptions ts
                    WHERE ts.tenant_id = t.id
                    ORDER BY ts.id DESC LIMIT 1) AS plan_expires
            FROM cd_tenants t
            WHERE t.deleted_at IS NULL
            ORDER BY t.id DESC
        ")->getResultArray();

        return view('App\Modules\Admin\Views\tenants\index', [
            'title'   => 'Institutes',
            'tenants' => $tenants,
        ]);
    }

    /* ============ CREATE FORM ============ */
    public function create()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $plans = db_connect()->table('cd_subscription_plans')
            ->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();

        return view('App\Modules\Admin\Views\tenants\form', [
            'title' => 'New Institute',
            'tenant' => null,
            'plans' => $plans,
            'currentPlanId' => null,
        ]);
    }

    /* ============ STORE (transaction) ============ */
    public function store()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'code'     => 'required|alpha_dash|min_length[2]|max_length[30]|is_unique[cd_tenants.code]',
            'email'    => 'required|valid_email',
            'owner_name'  => 'required|min_length[2]',
            'owner_email' => 'required|valid_email',
            'plan_id'  => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $db = db_connect();
        $db->transBegin();

        try {
            // 1. Create tenant
            $db->table('cd_tenants')->insert([
                'public_id'     => uuid4(),
                'name'          => $this->request->getPost('name'), 
                'code'          => $this->request->getPost('code'),
                'email'         => $this->request->getPost('email'),
                'phone'         => $this->request->getPost('phone'),
                'website'       => $this->request->getPost('website'),
                'address_line1' => $this->request->getPost('address_line1'),
                'city'          => $this->request->getPost('city'),
                'state'         => $this->request->getPost('state'),
                'pincode'       => $this->request->getPost('pincode'),
                'country'       => 'India',
                'timezone'      => 'Asia/Kolkata',
                'currency'      => 'INR',
                'locale'        => 'en-IN',
                'status'        => $this->request->getPost('status') ?: 'trial',
                'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+14 days')),
            ]);
            $tenantId = $db->insertID();

            // 2. Create default branch (main branch)
            $db->table('cd_branches')->insert([
                'public_id'   => uuid4(),
                'tenant_id'   => $tenantId,
                'name'        => 'Main Branch',
                'branch_code' => 'MAIN',
                'city'        => $this->request->getPost('city'),
                'state'       => $this->request->getPost('state'),
                'pincode'     => $this->request->getPost('pincode'),
                'status'      => 'active',
            ]);

            // 3. Create owner user
            $ownerPassword = 'Temp@' . substr(md5(uniqid()), 0, 8); // random temp password
            $db->table('cd_users')->insert([
                'public_id'         => uuid4(),
                'tenant_id'         => $tenantId,
                'name'              => $this->request->getPost('owner_name'),
                'email'             => $this->request->getPost('owner_email'),
                'phone'             => $this->request->getPost('owner_phone'),
                'password_hash'     => password_hash($ownerPassword, PASSWORD_BCRYPT),
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status'            => 'active',
            ]);
            $ownerUserId = $db->insertID();

            // 4. Assign owner role
            $ownerRoleId = $db->table('cd_roles')->where('slug', 'owner')->get()->getRow()->id;
            $db->table('cd_user_roles')->insert([
                'user_id' => $ownerUserId,
                'role_id' => $ownerRoleId,
                'branch_id' => null,
            ]);

            // 5. Create subscription
            $planId = (int) $this->request->getPost('plan_id');
            $plan   = $db->table('cd_subscription_plans')->where('id', $planId)->get()->getRow();
            if (! $plan) throw new \RuntimeException('Plan not found');

            $isTrial = ($this->request->getPost('status') === 'trial');
            $start = date('Y-m-d'); 
            $billingCycle = strtolower(trim($plan->billing_cycle));
            switch ($billingCycle) {
                case 'quarterly':
                    $end = date('Y-m-d', strtotime('+3 months'));
                    break;
                case 'half-yearly':
                    $end = date('Y-m-d', strtotime('+6 months'));
                    break;
                case 'yearly':
                    $end = date('Y-m-d', strtotime('+1 year'));
                    break;
                case 'monthly':
                default:
                    $end = date('Y-m-d', strtotime('+1 month'));
                    break;
            }

            $db->table('cd_tenant_subscriptions')->insert([
                'public_id'  => uuid4(),
                'tenant_id'  => $tenantId,
                'plan_id'    => $planId,
                'start_date' => $start,
                'end_date'   => $isTrial ? date('Y-m-d', strtotime('+14 days')) : $end,
                'status'     => $isTrial ? 'trial' : 'active',
                'auto_renew' => 1,
            ]);

            // 6. Audit log
            $db->table('cd_activity_logs')->insert([
                'tenant_id'  => $tenantId,
                'user_id'    => session()->get('user_id'),
                'action'     => 'tenant.created',
                'module'     => 'tenants',
                'record_type'=> 'tenant',
                'record_id'  => $tenantId,
                'metadata'   => json_encode(['owner_email' => $this->request->getPost('owner_email')]),
                'ip_address' => $this->request->getIPAddress(),
            ]);

            $db->transCommit();
            /** EMAIL (NexusSendEmail) */
            $ownerName  = (string) $this->request->getPost('owner_name');
            $ownerEmail = (string) $this->request->getPost('owner_email');
            $tenantName = (string) $this->request->getPost('name');

            $escapeEmailValue = static function (string $value): string {
                return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            };

            $html = '<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:24px;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:600px;margin:0 auto;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
        <div style="padding:24px;background-color:#1d4ed8;color:#ffffff;">
            <h1 style="margin:0;font-size:22px;line-height:1.3;">Welcome to ' . $escapeEmailValue($tenantName) . '</h1>
        </div>
        <div style="padding:24px;font-size:15px;line-height:1.6;">
            <p style="margin:0 0 16px;">Hello ' . $escapeEmailValue($ownerName) . ',</p>
            <p style="margin:0 0 16px;">Thank you for joining us. Your account for <strong>' . $escapeEmailValue($tenantName) . '</strong> is ready.</p>
            <p style="margin:0 0 8px;"><strong>Your sign-in details</strong></p>
            <div style="padding:16px;background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;overflow-wrap:anywhere;">
                <p style="margin:0 0 8px;"><strong>Username:</strong> ' . $escapeEmailValue($ownerEmail) . '</p>
                <p style="margin:0;"><strong>Temporary password:</strong> ' . $escapeEmailValue($ownerPassword) . '</p>
            </div>
            <p style="margin:16px 0 0;color:#b45309;"><strong>For your security, change your temporary password after your first sign-in.</strong></p>
            <p style="margin:16px 0 0;">Thank you,<br>Support Team</p>
        </div>
    </div>
</body>
</html>';

            $emailData = [
                'email'   => $ownerEmail,
                'name'    => $ownerName,
                'subject' => 'Your new tenant account is ready',
                'message' => $html,
            ];

            try {
                if (NexusSendEmail($emailData) !== true) {
                    log_message('error', 'Tenant owner welcome email could not be sent for tenant ID ' . $tenantId . '.');
                }
            } catch (\Throwable $emailError) {
                log_message('error', 'Tenant owner welcome email could not be sent for tenant ID ' . $tenantId . '.');
            }

            /** email end */

            return redirect()->to('/admin/tenants/'.$tenantId)->with(
                'success',
                "Institute created. Owner: {$this->request->getPost('owner_email')} | Temp password: <strong>{$ownerPassword}</strong> — share securely, ask them to change on first login."
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Create failed: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        if (! is_super_admin()) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $tenant = $db->table('cd_tenants')->where('id', (int) $id)->get()->getRowArray();
        if (! $tenant) return redirect()->to('/admin/tenants')->with('error', 'Institute not found.');

        $branches = $db->table('cd_branches')
            ->where('tenant_id', (int) $id)->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $users = $db->table('cd_users u')
            ->select('u.id, u.name, u.email, u.status, u.last_login_at, GROUP_CONCAT(r.slug) AS roles')
            ->join('cd_user_roles ur', 'ur.user_id = u.id', 'left')
            ->join('cd_roles r', 'r.id = ur.role_id', 'left')
            ->where('u.tenant_id', (int) $id)
            ->where('u.deleted_at IS NULL')
            ->groupBy('u.id')
            ->get()->getResultArray();

        $subscription = $db->table('cd_tenant_subscriptions ts')
            ->select('ts.*, p.name AS plan_name, p.price')
            ->join('cd_subscription_plans p', 'p.id = ts.plan_id')
            ->where('ts.tenant_id', (int) $id)
            ->orderBy('ts.id', 'desc')
            ->get()->getRowArray();

        $stats = [
            'students' => $db->table('cd_students')->where(['tenant_id' => (int) $id, 'deleted_at' => null])->countAllResults(),
            'branches' => count($branches),
            'users'    => count($users),
            'courses'  => $db->table('cd_courses')->where(['tenant_id' => (int) $id, 'deleted_at' => null])->countAllResults(),
        ];

        return view('App\Modules\Admin\Views\tenants\show', [
            'title'        => $tenant['name'],
            'tenant'       => $tenant,
            'branches'     => $branches,
            'users'        => $users,
            'subscription' => $subscription,
            'stats'        => $stats,
        ]);
    }

    /* ============ EDIT FORM ============ */
    
    public function edit($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $db = db_connect();
        $tenant = $db->table('cd_tenants')->where('id', (int) $id)->get()->getRowArray();
        if (! $tenant) return redirect()->to('/admin/tenants')->with('error', 'Institute not found.');

        $plans = $db->table('cd_subscription_plans')->where('is_active', 1)->orderBy('sort_order')->get()->getResultArray();
        $currentSub = $db->table('cd_tenant_subscriptions')->where('tenant_id', (int) $id)
            ->orderBy('id', 'desc')->get()->getRowArray();

        return view('App\Modules\Admin\Views\tenants\form', [
            'title' => 'Edit Institute',
            'tenant' => $tenant,
            'plans' => $plans,
            'currentPlanId' => $currentSub->plan_id ?? null,
        ]);
    }

    /* ============ UPDATE ============ */

    public function update($id)
    {
        if (!is_super_admin()) {
            return redirect()->to('/admin/dashboard');
        }

        $db = db_connect();
        $id = (int) $id;

        // Check tenant
        $tenant = $db->table('cd_tenants')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (!$tenant) {
            return redirect()->to('/admin/tenants')
                ->with('error', 'Institute not found.');
        }

        // Get submitted plan
        $newPlanId = (int) $this->request->getPost('plan_id');

        // Get current subscription
        $currentSub = $db->table('cd_tenant_subscriptions')
            ->where('tenant_id', $id)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        // Check whether plan has changed
        $planChanged = $newPlanId > 0 &&
            (!$currentSub || (int) $currentSub['plan_id'] !== $newPlanId);

        // Validate new plan before starting changes
        $plan = null;

        if ($planChanged) {
            $plan = $db->table('cd_subscription_plans')
                ->where('id', $newPlanId)
                ->get()
                ->getRowArray();

            if (!$plan) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Selected subscription plan not found.');
            }
        }

        $db->transBegin();

        try {

            // Update tenant
            $db->table('cd_tenants')
                ->where('id', $id)
                ->update([
                    'name'          => $this->request->getPost('name'),
                    'email'         => $this->request->getPost('email'),
                    'phone'         => $this->request->getPost('phone'),
                    'website'       => $this->request->getPost('website'),
                    'address_line1' => $this->request->getPost('address_line1'),
                    'city'          => $this->request->getPost('city'),
                    'state'         => $this->request->getPost('state'),
                    'pincode'       => $this->request->getPost('pincode'),
                    'status'        => $this->request->getPost('status'),
                ]);

            // If plan changed, create new subscription
            if ($planChanged) {

                // Cancel previous subscription
                if ($currentSub) {
                    $db->table('cd_tenant_subscriptions')
                        ->where('id', $currentSub['id'])
                        ->update([
                            'status' => 'cancelled'
                        ]);
                }

                // Calculate subscription end date
                // $startDate = date('Y-m-d');

                // $endDate = match (strtolower($plan['billing_cycle'])) {
                //     'quarterly' => date('Y-m-d', strtotime('+3 months')),
                //     'yearly'    => date('Y-m-d', strtotime('+1 year')),
                //     'monthly'   => date('Y-m-d', strtotime('+1 month')),
                //     default     => date('Y-m-d', strtotime('+1 month')),
                // };
                $startDate = date('Y-m-d');
                $billingCycle = strtolower(trim($plan['billing_cycle'] ?? 'monthly'));

                switch ($billingCycle) {
                    case 'quarterly':
                        $endDate = date('Y-m-d', strtotime('+3 months'));
                        break;

                    case 'half-yearly':
                        $endDate = date('Y-m-d', strtotime('+6 months'));
                        break;

                    case 'yearly':
                        $endDate = date('Y-m-d', strtotime('+1 year'));
                        break;

                    case 'monthly':
                    default:
                        $endDate = date('Y-m-d', strtotime('+1 month'));
                        break;
                }

                // Create new subscription
                $db->table('cd_tenant_subscriptions')->insert([
                    'public_id'  => uuid4(),
                    'tenant_id'  => $id,
                    'plan_id'    => $newPlanId,
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'status'     => 'active',
                    'auto_renew' => 1,
                ]);
            }

            // Activity log
            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => $id,
                'user_id'     => session()->get('user_id'),
                'action'      => 'tenant.updated',
                'module'      => 'tenants',
                'record_type' => 'tenant',
                'record_id'   => $id,
                'ip_address'  => $this->request->getIPAddress(),
            ]);

            // Check transaction
            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();

            return redirect()->to('/admin/tenants/' . $id)
                ->with('success', 'Institute updated.');
        } catch (\Throwable $e) {

            $db->transRollback();

            log_message('error', 'Tenant update failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to update institute. Please try again.');
        }
    }


    /* ============ SUSPEND / ACTIVATE ============ */
    
    public function status($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $newStatus = $this->request->getPost('status');
        if (! in_array($newStatus, ['active', 'suspended', 'expired', 'cancelled'])) {
            return redirect()->back()->with('error', 'Invalid status.');
        }

        $db = db_connect();
        $db->table('cd_tenants')->where('id', (int) $id)->update(['status' => $newStatus]);

        // Force-logout all users of this tenant if suspended
        if ($newStatus === 'suspended') {
            $userIds = $db->table('cd_users')->select('id')->where('tenant_id', (int) $id)->get()->getResultArray();
            $ids = array_column($userIds, 'id');
            if ($ids) {
                $db->table('cd_refresh_tokens')->whereIn('user_id', $ids)->update(['revoked_at' => date('Y-m-d H:i:s')]);
            }
        }

        $db->table('cd_activity_logs')->insert([
            'tenant_id' => (int) $id,
            'user_id'   => session()->get('user_id'),
            'action'    => 'tenant.status_changed',
            'module'    => 'tenants',
            'record_type' => 'tenant',
            'record_id' => (int) $id,
            'metadata'  => json_encode(['new_status' => $newStatus]),
            'ip_address' => $this->request->getIPAddress(),
        ]);

        return redirect()->to('/admin/tenants/' . $id)
            ->with('success', "Institute marked as {$newStatus}.");
    }

    /* ============ SOFT DELETE ============ */
    
    public function delete($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $db = db_connect();
        $db->table('cd_tenants')->where('id', (int) $id)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'status'     => 'cancelled',
        ]);

        $db->table('cd_activity_logs')->insert([
            'tenant_id' => (int) $id,
            'user_id'   => session()->get('user_id'),
            'action'    => 'tenant.deleted',
            'module'    => 'tenants',
            'record_type' => 'tenant',
            'record_id' => (int) $id,
            'ip_address' => $this->request->getIPAddress(),
        ]);

        return redirect()->to('/admin/tenants')->with('success', 'Institute deleted.');
    }
}
