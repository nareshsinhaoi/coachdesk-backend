<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class UserAdminController extends BaseController
{
    public function resetPassword($id)
    {
        if (! can('users.edit')) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $actor = current_user();
        $userQuery = $db->table('cd_users')
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL');
        if ($actor->tenant_id !== null) {
            $userQuery->where('tenant_id', (int) $actor->tenant_id);
        }
        $user = $userQuery->get()->getRow();
        if (! $user) return redirect()->back()->with('error', 'User not found.');

        $newPass = 'Temp@' . substr(md5(uniqid()), 0, 8);

        $db->transBegin();
        try {
            $db->table('cd_users')->where('id', (int) $id)->update([
                'password_hash' => password_hash($newPass, PASSWORD_BCRYPT),
            ]);
            $db->table('cd_refresh_tokens')->where('user_id', (int) $id)->update([
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => $user->tenant_id,
                'user_id'     => session()->get('user_id'),
                'action'      => 'user.password_reset_by_admin',
                'module'      => 'users',
                'record_type' => 'user',
                'record_id'   => (int) $id,
                'ip_address'  => $this->request->getIPAddress(),
            ]);

            if ($db->transStatus() === false) throw new \RuntimeException('Transaction failed');
            $db->transCommit();

            // Send email with new password
            $this->sendAdminResetEmail($user, $newPass);

            return redirect()->back()->with(
                'success',
                "Password reset. New password: <strong>{$newPass}</strong> — also emailed to <strong>" . esc($user->email) . "</strong>."
            );
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Admin password reset failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Reset failed. Please try again.');
        }
    }

    public function forceLogout($id)
    {
        if (! can('users.edit')) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $actor = current_user();
        $userQuery = $db->table('cd_users')
            ->where('id', (int) $id)
            ->where('deleted_at IS NULL');
        if ($actor->tenant_id !== null) {
            $userQuery->where('tenant_id', (int) $actor->tenant_id);
        }
        $user = $userQuery->get()->getRow();
        if (! $user) return redirect()->back()->with('error', 'User not found.');

        $db->table('cd_refresh_tokens')->where('user_id', (int) $id)->update([
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        // Optional: send notification email
        if (! empty($user->email)) {
            try {
                $html = $this->forceLogoutEmailHtml($user);
                NexusSendEmail([
                    'email'   => $user->email,
                    'name'    => $user->name,
                    'subject' => 'All sessions signed out',
                    'message' => $html,
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Force logout email failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'All sessions revoked.');
    }

    /* ============ EMAIL: Admin reset ============ */
    protected function sendAdminResetEmail(object $user, string $newPass): void
    {
        if (empty($user->email)) return;

        $html = $this->adminResetEmailHtml($user, $newPass);

        try {
            NexusSendEmail([
                'email'   => $user->email,
                'name'    => $user->name,
                'subject' => 'Your password has been reset by an administrator',
                'message' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Admin reset email failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /* ============ EMAIL HTML: Admin reset ============ */
    protected function adminResetEmailHtml(object $user, string $newPass): string
    {
        $name  = htmlspecialchars($user->name ?? 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $email = htmlspecialchars($user->email ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pass  = htmlspecialchars($newPass, ENT_QUOTES, 'UTF-8');
        $year  = date('Y');

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
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px 32px 24px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🎓 CoachDesk</div>
              <div style="font-size:13px;color:#e0e7ff;margin-top:6px;">Coaching Institute Management</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Your password has been reset</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                A CoachDesk administrator has reset the password for your account. Use the temporary password below to sign in, and change it immediately afterward.
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;margin:20px 0;">
                <tr>
                  <td style="padding:20px;text-align:center;">
                    <p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#4338ca;font-weight:600;">Temporary Password</p>
                    <p style="margin:0;font-size:22px;font-family:monospace;font-weight:700;color:#111827;letter-spacing:1px;">{$pass}</p>
                  </td>
                </tr>
              </table>

              <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                  <strong>Important:</strong> You must change this temporary password immediately after your first login. Click <strong>My Profile → Change Password</strong> in the top-right menu.
                </p>
              </div>

              <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                <strong>Account:</strong> {$email}<br>
                For your security, all other devices have been signed out.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#6b7280;line-height:1.5;">
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

    /* ============ EMAIL HTML: Force logout ============ */
    protected function forceLogoutEmailHtml(object $user): string
    {
        $name = htmlspecialchars($user->name ?? 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $year = date('Y');
        $when = date('d M Y, h:i A T');

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
          <tr>
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;">🎓 CoachDesk</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">All sessions signed out</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                All active sessions on your CoachDesk account were signed out at <strong>{$when}</strong> by an administrator. Your password has not been changed — simply log in again to continue.
              </p>
              <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                If you did not expect this, contact your institute administrator.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#6b7280;">
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