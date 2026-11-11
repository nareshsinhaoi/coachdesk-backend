<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class ProfileController extends BaseController
{
    /* ============ VIEW PROFILE ============ */
    public function index()
    {
        $u = current_user();
        if (! $u) return redirect()->to('/admin/login');

        $db = db_connect();
        $roles = $db->table('cd_user_roles ur')
            ->select('r.name, r.slug, ur.branch_id')
            ->join('cd_roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $u->id)
            ->get()->getResultArray();

        return view('App\Modules\Admin\Views\profile\index', [
            'title' => 'My Profile',
            'user'  => $u,
            'roles' => $roles,
        ]);
    }

    /* ============ CHANGE PASSWORD FORM ============ */
    public function changePasswordForm()
    {
        if (! current_user()) return redirect()->to('/admin/login');
        return view('App\Modules\Admin\Views\profile\change_password', [
            'title' => 'Change Password',
        ]);
    }

    /* ============ CHANGE PASSWORD ============ */
    public function changePassword()
    {
        $u = current_user();
        if (! $u) return redirect()->to('/admin/login');

        // Validate input
        $rules = [
            'current_password' => 'required|min_length[6]',
            'new_password'     => 'required|min_length[8]|max_length[100]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Verify current password
        if (! password_verify($this->request->getPost('current_password'), $u->password_hash)) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        // Prevent reuse of same password
        if (password_verify($this->request->getPost('new_password'), $u->password_hash)) {
            return redirect()->back()->with('error', 'New password must be different from current password.');
        }

        $db = db_connect();
        $newPassword = $this->request->getPost('new_password');

        $db->transBegin();

        try {
            // Update password
            $db->table('cd_users')->where('id', $u->id)->update([
                'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            ]);

            // Revoke all refresh tokens (force re-login on other devices)
            $db->table('cd_refresh_tokens')->where('user_id', $u->id)->update([
                'revoked_at' => date('Y-m-d H:i:s'),
            ]);

            // Audit log
            $db->table('cd_activity_logs')->insert([
                'tenant_id'   => $u->tenant_id,
                'user_id'     => $u->id,
                'action'      => 'user.password_changed',
                'module'      => 'users',
                'record_type' => 'user',
                'record_id'   => $u->id,
                'ip_address'  => $this->request->getIPAddress(),
                'user_agent'  => substr($this->request->getUserAgent()->getAgentString(), 0, 250),
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }
            $db->transCommit();

            // Send confirmation email
            $this->sendPasswordChangedEmail($u);

            return redirect()->to('/admin/profile')
                ->with('success', 'Password changed successfully. A confirmation email has been sent to ' . esc($u->email));
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Password change failed for user ' . $u->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to change password. Please try again.');
        }
    }

    /* ============ UPDATE PROFILE (name, phone) ============ */
    public function update()
    {
        $u = current_user();
        if (! $u) return redirect()->to('/admin/login');

        $db = db_connect();
        $db->table('cd_users')->where('id', $u->id)->update([
            'name'  => $this->request->getPost('name') ?: $u->name,
            'phone' => $this->request->getPost('phone') ?: $u->phone,
        ]);

        return redirect()->to('/admin/profile')->with('success', 'Profile updated.');
    }

    /* ============ EMAIL: Password changed confirmation ============ */
    protected function sendPasswordChangedEmail(object $user): void
    {
        if (empty($user->email)) return;

        $html = $this->passwordChangedEmailHtml($user);

        try {
            NexusSendEmail([
                'email'   => $user->email,
                'name'    => $user->name,
                'subject' => 'Your password was changed successfully',
                'message' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Password changed email failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    /* ============ EMAIL HTML: Password changed ============ */
    protected function passwordChangedEmailHtml(object $user): string
    {
        $name = htmlspecialchars($user->name ?? 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $email = htmlspecialchars($user->email ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $when  = date('d M Y, h:i A T');
        $ip    = htmlspecialchars(service('request')->getIPAddress(), ENT_QUOTES, 'UTF-8');
        $ua    = htmlspecialchars(substr(service('request')->getUserAgent()->getAgentString(), 0, 120), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;-webkit-font-smoothing:antialiased;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px 32px 24px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🎓 CoachDesk</div>
              <div style="font-size:13px;color:#e0e7ff;margin-top:6px;">Coaching Institute Management</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Password changed successfully</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                Your password for CoachDesk has been changed successfully. This is a confirmation that the change was made on your account.
              </p>

              <!-- Session details -->
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                <tr>
                  <td style="padding:16px 20px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:13px;">
                      <tr>
                        <td style="padding:4px 0;color:#6b7280;width:120px;">Account</td>
                        <td style="padding:4px 0;color:#111827;font-weight:600;">{$email}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;color:#6b7280;">Changed on</td>
                        <td style="padding:4px 0;color:#111827;">{$when}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;color:#6b7280;">IP address</td>
                        <td style="padding:4px 0;color:#111827;font-family:monospace;">{$ip}</td>
                      </tr>
                      <tr>
                        <td style="padding:4px 0;color:#6b7280;vertical-align:top;">Device</td>
                        <td style="padding:4px 0;color:#111827;font-size:12px;line-height:1.4;">{$ua}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                  <strong>Didn't make this change?</strong> Contact your institute administrator immediately, or email us at <a href="mailto:support@coachdesk.in" style="color:#92400e;text-decoration:underline;">support@coachdesk.in</a>.
                </p>
              </div>

              <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                For your security, all other devices have been signed out. You'll need to log in again on them with your new password.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 32px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#6b7280;line-height:1.5;">
              This is an automated message from CoachDesk. Please do not reply to this email.<br>
              © {$this->copyrightYear()} CoachDesk · All rights reserved.
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

    protected function copyrightYear(): string
    {
        return date('Y');
    }
}