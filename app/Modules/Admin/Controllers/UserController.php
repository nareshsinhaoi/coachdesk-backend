<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class UserController extends BaseController
{
    public function index()
    {
        //die('UserController::index() is disabled. Use the new Users module instead.');
        if (! can('users.view')) return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');

        $tid = tenant_id();
        $db = db_connect();
        $users = $db->table('cd_users')
            ->select('cd_users.*, GROUP_CONCAT(r.name) AS roles')
            ->join('cd_user_roles ur', 'ur.user_id = cd_users.id', 'left')
            ->join('cd_roles r', 'r.id = ur.role_id', 'left')
            ->where('cd_users.tenant_id', $tid)
            ->where('cd_users.deleted_at IS NULL')
            ->groupBy('cd_users.id')
            ->orderBy('cd_users.id', 'desc')
            ->get()->getResultArray();
        //echo $db->getLastQuery(); die;
        return view('App\Modules\Admin\Views\users\index', ['title' => 'Users', 'users' => $users]);
    }

    public function create()
    {
        //die('UserController::create() is disabled. Use the new Users module instead.');
        if (! can('users.create')) return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');

        $db = db_connect();
        $roles = $db->table('cd_roles')
            ->where('tenant_id', tenant_id())
            ->orWhere('tenant_id IS NULL')
            ->where('slug !=', 'super_admin')
            ->orderBy('is_system', 'desc')
            ->get()->getResultArray();

        $branches = $db->table('cd_branches')
            ->where(['tenant_id' => tenant_id(), 'deleted_at' => null])
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\users\form', [
            'title' => 'New User', 'user' => null,
            'roles' => $roles, 'branches' => $branches,
            'userRoles' => [],
        ]);
    }

    public function store()
    {
        if (! can('users.create')) return redirect()->to('/admin/dashboard');

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email',
            'role_id'  => 'required|integer',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $tid = tenant_id();
        $db = db_connect();

        // Check email uniqueness in this tenant
        $exists = $db->table('cd_users')
            ->where(['tenant_id' => $tid, 'email' => $this->request->getPost('email')])
            ->where('deleted_at IS NULL')
            ->countAllResults();
        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'A user with this email already exists in your institute.');
        }

        $tempPass = 'Temp@' . substr(md5(uniqid()), 0, 8);

        $db->transBegin();
        try {
            $db->table('cd_users')->insert([
                'public_id'         => uuid4(),
                'tenant_id'         => $tid,
                'name'              => $this->request->getPost('name'),
                'email'             => $this->request->getPost('email'),
                'phone'             => $this->request->getPost('phone'),
                'password_hash'     => password_hash($tempPass, PASSWORD_BCRYPT),
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status'            => 'active',
            ]);
            $newUserId = $db->insertID();

            $db->table('cd_user_roles')->insert([
                'user_id'   => $newUserId,
                'role_id'   => (int) $this->request->getPost('role_id'),
                'branch_id' => $this->request->getPost('branch_id') ?: null,
            ]);

            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => $tid,
                'user_id'     => session()->get('user_id'),
                'action'      => 'user.created',
                'module'      => 'users',
                'record_type' => 'user',
                'record_id'   => $newUserId,
                'metadata'    => json_encode(['email' => $this->request->getPost('email')]),
                'ip_address'  => $this->request->getIPAddress(),
            ]);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaction failed');
            $db->transCommit();

            // Send welcome email
            $newUser = $db->table('cd_users')->where('id', $newUserId)->get()->getRow();
            $this->sendNewUserEmail($newUser, $tempPass, $this->request->getPost('role_id'));

            return redirect()->to('/admin/users')->with(
                'success',
                "User created. Temporary password: <strong>{$tempPass}</strong> — emailed to <strong>" . esc($newUser->email) . "</strong>."
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'User creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Unable to create user. Please try again.');
        }
    }

    /* ============ EMAIL: New user welcome ============ */
    protected function sendNewUserEmail(object $user, string $tempPass, int $roleId): void
    {
        if (empty($user->email)) return;

        $db = db_connect();
        $tenantName = $db->table('cd_tenants')->where('id', $user->tenant_id)->get()->getRow()->name ?? 'CoachDesk';
        $roleName   = $db->table('cd_roles')->where('id', $roleId)->get()->getRow()->name ?? 'User';

        $html = $this->newUserEmailHtml($user, $tempPass, $roleName, $tenantName);

        try {
            NexusSendEmail([
                'email'   => $user->email,
                'name'    => $user->name,
                'subject' => 'Your ' . $tenantName . ' account is ready',
                'message' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'New user email failed: ' . $e->getMessage());
        }
    }

    protected function newUserEmailHtml(object $user, string $tempPass, string $roleName, string $tenantName): string
    {
        $name   = htmlspecialchars($user->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $email  = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pass   = htmlspecialchars($tempPass, ENT_QUOTES, 'UTF-8');
        $role   = htmlspecialchars($roleName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tenant = htmlspecialchars($tenantName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $year   = date('Y');
        $login  = htmlspecialchars(base_url('admin/login'), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
          <tr>
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🎓 CoachDesk</div>
              <div style="font-size:13px;color:#e0e7ff;margin-top:6px;">{$tenant}</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Welcome to {$tenant}</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                Your account on <strong>{$tenant}</strong> is now ready. You have been added as a <strong>{$role}</strong>.
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                <tr>
                  <td style="padding:20px;">
                    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;font-weight:600;">Your Sign-in Details</p>
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:14px;">
                      <tr>
                        <td style="padding:6px 0;color:#6b7280;width:110px;">Login URL</td>
                        <td style="padding:6px 0;"><a href="{$login}" style="color:#4f46e5;text-decoration:none;">{$login}</a></td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#6b7280;">Email</td>
                        <td style="padding:6px 0;color:#111827;font-weight:600;">{$email}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#6b7280;">Password</td>
                        <td style="padding:6px 0;font-family:monospace;font-weight:700;color:#111827;">{$pass}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                  <strong>Important:</strong> Change your password immediately after first login via <strong>My Profile → Change Password</strong>.
                </p>
              </div>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                <tr>
                  <td style="background-color:#4f46e5;border-radius:8px;">
                    <a href="{$login}" style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Sign in to CoachDesk →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                Need help? Contact your institute administrator or email <a href="mailto:support@coachdesk.in" style="color:#4f46e5;">support@coachdesk.in</a>.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#6b7280;">
              This is an automated message from CoachDesk. Please do not reply to this email.<br>
              © {$year} CoachDesk · All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
    }
}