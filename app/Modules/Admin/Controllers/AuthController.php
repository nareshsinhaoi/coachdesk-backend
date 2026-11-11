<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';


class AuthController extends BaseController 
{
    public function loginForm()
    {
        if (session()->get('user_id')) return redirect()->to('/admin/dashboard');
        return view('App\Modules\Admin\Views\auth\login');
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $db = db_connect();
        $user = $db->table('cd_users')
            ->where('email', $email)
            ->where('status', 'active')
            ->get()->getRow();

        if (! $user || ! password_verify($password, $user->password_hash)) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        // Determine branch context: if user has branch_admin role, pin a branch
        $branchRow = $db->table('cd_user_roles')
            ->where('user_id', $user->id)
            ->where('branch_id IS NOT NULL')
            ->get()->getRow();

        session()->set([
            'user_id'   => (int) $user->id,
            'tenant_id' => $user->tenant_id ? (int) $user->tenant_id : null,
            'branch_id' => $branchRow->branch_id ?? null,
            'user_name' => $user->name,
            'user_email'=> $user->email,
            'logged_in' => true,
        ]);

        $db->table('cd_users')->where('id', $user->id)
            ->update(['last_login_at' => date('Y-m-d H:i:s')]);

        // Audit
        $db->table('cd_activity_logs')->insert([
            'tenant_id' => $user->tenant_id,
            'user_id'   => $user->id,
            'action'    => 'auth.login',
            'module'    => 'auth',
            'ip_address'=> $this->request->getIPAddress(),
            'user_agent'=> substr($this->request->getUserAgent()->getAgentString(), 0, 250),
        ]);

        return redirect()->to('/admin/dashboard')->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function logout()
    {
        $db = db_connect();
        $uid = session()->get('user_id');
        if ($uid) {
            $db->table('cd_activity_logs')->insert([
                'tenant_id' => session()->get('tenant_id'),
                'user_id'   => $uid,
                'action'    => 'auth.logout',
                'module'    => 'auth',
                'ip_address'=> $this->request->getIPAddress(),
            ]);
        }
        session()->destroy();
        return redirect()->to('/admin/login')->with('success', 'Logged out.');
    }


    public function forgotPasswordForm()
    {
        return view('App\Modules\Admin\Views\auth\forgot_password');
    }

    public function forgotPassword()
    {
        // Always show success to prevent email enumeration
        $successMessage = 'If that email is registered, a password reset link has been sent.';

        $rules = ['email' => 'required|valid_email'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email = $this->request->getPost('email');
        $db    = db_connect();

        $user = $db->table('cd_users')
            ->where('email', $email)
            ->where('status', 'active')
            ->where('deleted_at IS NULL')
            ->get()->getRow();

        if ($user) {
            // Invalidate any prior unused tokens for this user
            $db->table('cd_password_resets')
                ->where('user_id', $user->id)
                ->where('used_at IS NULL')
                ->update(['used_at' => date('Y-m-d H:i:s')]);

            // Generate token
            $rawToken = bin2hex(random_bytes(32));  // 64-char random string
            $hash     = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $db->table('cd_password_resets')->insert([
                'user_id'    => $user->id,
                'token_hash' => $hash,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Audit
            $db->table('cd_activity_logs')->insert([
                'tenant_id'  => $user->tenant_id,
                'user_id'    => $user->id,
                'action'     => 'auth.password_reset_requested',
                'module'     => 'auth',
                'ip_address' => $this->request->getIPAddress(),
            ]);

            // Send email
            $this->sendForgotPasswordEmail($user, $rawToken);
        }

        return redirect()->to('/admin/login')->with('success', $successMessage);
    }

    /* ============================================================
     * RESET PASSWORD — Step 2: Set new password via token
     * ============================================================ */

    public function resetPasswordForm($token = null)
    {
        if (empty($token)) {
            return redirect()->to('/admin/forgot-password')->with('error', 'Invalid or expired reset link.');
        }

        $row = $this->findValidResetToken($token);
        if (! $row) {
            return redirect()->to('/admin/forgot-password')->with('error', 'This reset link is invalid or has expired.');
        }

        return view('App\Modules\Admin\Views\auth\reset_password', ['token' => $token]);
    }

    public function resetPassword($token = null)
    {
        if (empty($token)) {
            return redirect()->to('/admin/forgot-password')->with('error', 'Invalid or expired reset link.');
        }

        $row = $this->findValidResetToken($token);
        if (! $row) {
            return redirect()->to('/admin/forgot-password')->with('error', 'This reset link is invalid or has expired.');
        }

        $rules = [
            'password'         => 'required|min_length[8]|max_length[100]',
            'confirm_password' => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $newPassword = $this->request->getPost('password');
        $db          = db_connect();

        $db->transBegin();
        try {
            // Update password
            $db->table('cd_users')
                ->where('id', $row['user_id'])
                ->update([
                    'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
                ]);

            // Mark token used
            $db->table('cd_password_resets')
                ->where('id', $row['id'])
                ->update(['used_at' => date('Y-m-d H:i:s')]);

            // Revoke all sessions for that user
            $db->table('cd_refresh_tokens')
                ->where('user_id', $row['user_id'])
                ->update(['revoked_at' => date('Y-m-d H:i:s')]);

            // Audit
            $db->table('cd_activity_logs')->insert([
                'tenant_id'  => $row['tenant_id'],
                'user_id'    => $row['user_id'],
                'action'     => 'auth.password_reset_completed',
                'module'     => 'auth',
                'ip_address' => $this->request->getIPAddress(),
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed');
            }
            $db->transCommit();

            // Send confirmation email
            $user = $db->table('cd_users')->where('id', $row['user_id'])->get()->getRow();
            if ($user) {
                $this->sendPasswordResetConfirmation($user);
            }

            return redirect()->to('/admin/login')
                ->with('success', 'Password reset successfully. Please log in with your new password.');

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Password reset failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to reset password. Please try again.');
        }
    }

    /* ============================================================
     * REGISTRATION (public lead capture) — NO DB WRITE
     * Just emails info@modulelabs.in
     * ============================================================ */

    public function registerForm()
    {
        return view('App\Modules\Admin\Views\auth\register');
    }

    public function register()
    {
        $rules = [
            'institute_name' => 'required|min_length[2]|max_length[150]',
            'owner_name'     => 'required|min_length[2]|max_length[150]',
            'email'          => 'required|valid_email',
            'phone'          => 'required|min_length[10]|max_length[15]',
            'city'           => 'permit_empty|max_length[100]',
            'institute_type' => 'permit_empty|max_length[100]',
            'students_range' => 'permit_empty|max_length[50]',
            'message'        => 'permit_empty|max_length[2000]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Collect form data
        $data = [
            'institute_name' => $this->request->getPost('institute_name'),
            'owner_name'     => $this->request->getPost('owner_name'),
            'email'          => $this->request->getPost('email'),
            'phone'          => $this->request->getPost('phone'),
            'city'           => $this->request->getPost('city'),
            'institute_type' => $this->request->getPost('institute_type'),
            'students_range' => $this->request->getPost('students_range'),
            'message'        => $this->request->getPost('message'),
            'ip'             => $this->request->getIPAddress(),
            'user_agent'     => substr($this->request->getUserAgent()->getAgentString(), 0, 250),
            'submitted_at'   => date('d M Y, h:i A T'),
        ];

        // Send to sales team (info@modulelabs.in)
        $html = $this->registrationEmailHtml($data);

        try {
            NexusSendEmail([
                'email'   => 'info@modulelabs.in',
                'name'    => 'CoachDesk Sales',
                'subject' => 'New Signup Request: ' . $data['institute_name'],
                'message' => $html,
                'bcc'     => ['info@modulelabs.in'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registration email failed: ' . $e->getMessage());
        }

        // Optional: send confirmation to the prospect
        try {
            NexusSendEmail([
                'email'   => $data['email'],
                'name'    => $data['owner_name'],
                'subject' => 'We received your CoachDesk signup request',
                'message' => $this->registrationConfirmationHtml($data),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Registration confirmation email failed: ' . $e->getMessage());
        }

        return redirect()->to('/admin/register')
            ->with('success', 'Thank you! Your request has been sent to our team. We will contact you within 24 hours.');
    }

    /* ============================================================
     * PRIVATE HELPERS
     * ============================================================ */

    /**
     * Find a valid, unused, non-expired reset token row.
     * Returns the row + user_id + tenant_id, or null.
     */
    protected function findValidResetToken(string $rawToken)
    {
        $hash = hash('sha256', $rawToken);
        $row = db_connect()->table('cd_password_resets')
            ->where('token_hash', $hash)
            ->where('used_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }

        // Attach user's tenant_id for downstream use
        $user = db_connect()->table('cd_users')->where('id', $row['user_id'])->get()->getRow();
        $row['tenant_id'] = $user ? $user->tenant_id : null;

        return $row;
    }

    /* ============================================================
     * EMAIL: Forgot password reset link
     * ============================================================ */
    protected function sendForgotPasswordEmail(object $user, string $rawToken): void
    {
        if (empty($user->email)) {
            return;
        }

        $resetUrl = site_url('admin/reset-password/' . $rawToken);
        $html     = $this->forgotPasswordEmailHtml($user, $resetUrl);

        try {
            NexusSendEmail([
                'email'   => $user->email,
                'name'    => $user->name,
                'subject' => 'Reset your CoachDesk password',
                'message' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Forgot password email failed for user ' . $user->id . ': ' . $e->getMessage());
        }
    }

    protected function forgotPasswordEmailHtml(object $user, string $resetUrl): string
    {
        $name  = htmlspecialchars($user->name ?? 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url   = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
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
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;letter-spacing:-0.5px;">🎓 CoachDesk</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Reset your password</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                We received a request to reset your CoachDesk password. Click the button below to set a new password.
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                <tr>
                  <td style="background-color:#4f46e5;border-radius:8px;">
                    <a href="{$url}" style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Reset My Password →
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 16px;font-size:13px;color:#6b7280;">
                Or copy this link into your browser:<br>
                <a href="{$url}" style="color:#4f46e5;word-break:break-all;">{$url}</a>
              </p>

              <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                  <strong>This link expires in 30 minutes.</strong> If you didn't request this, you can safely ignore this email — your password will not be changed.
                </p>
              </div>

              <p style="margin:20px 0 0;font-size:13px;color:#6b7280;">
                For security reasons, we sign you out from all other devices when you reset your password.
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

    /* ============================================================
     * EMAIL: Reset confirmation
     * ============================================================ */
    protected function sendPasswordResetConfirmation(object $user): void
    {
        if (empty($user->email)) {
            return;
        }

        $name = htmlspecialchars($user->name ?? 'there', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $when = date('d M Y, h:i A T');
        $ip   = htmlspecialchars($this->request->getIPAddress(), ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        $html = <<<HTML
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
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Your password has been reset</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Hi {$name},</p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                Your CoachDesk password was successfully reset on <strong>{$when}</strong> from IP <span style="font-family:monospace;">{$ip}</span>.
              </p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                You can now log in with your new password.
              </p>
              <div style="background-color:#fef3c7;border-left:4px solid #f59e0b;padding:14px 16px;border-radius:6px;margin:20px 0;">
                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                  <strong>Didn't reset your password?</strong> Contact us immediately at <a href="mailto:support@coachdesk.in" style="color:#92400e;">support@coachdesk.in</a>.
                </p>
              </div>
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

        try {
            NexusSendEmail([
                'email'   => $user->email,
                'name'    => $user->name,
                'subject' => 'Your password was reset',
                'message' => $html,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Reset confirmation email failed: ' . $e->getMessage());
        }
    }

    /* ============================================================
     * EMAIL: Registration → sent to sales team
     * ============================================================ */
    protected function registrationEmailHtml(array $data): string
    {
        $esc = function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $year = date('Y');

        $institute = $esc($data['institute_name']);
        $owner     = $esc($data['owner_name']);
        $email     = $esc($data['email']);
        $phone     = $esc($data['phone']);
        $city      = $esc($data['city'] ?: '—');
        $type      = $esc($data['institute_type'] ?: '—');
        $students  = $esc($data['students_range'] ?: '—');
        $message   = $esc($data['message'] ?: '—');
        $ip        = $esc($data['ip']);
        $ua        = $esc($data['user_agent']);
        $when      = $esc($data['submitted_at']);

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
          <tr>
            <td style="background:#059669;padding:24px 32px;">
              <div style="font-size:20px;font-weight:700;color:#ffffff;">📥 New Signup Request</div>
              <div style="font-size:13px;color:#d1fae5;margin-top:4px;">A prospect submitted the CoachDesk registration form</div>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;">
              <h2 style="margin:0 0 16px;font-size:18px;color:#111827;">{$institute}</h2>

              <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                  <td style="padding:8px 0;color:#6b7280;width:160px;">Contact Name</td>
                  <td style="padding:8px 0;color:#111827;font-weight:600;">{$owner}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Email</td>
                  <td style="padding:8px 0;"><a href="mailto:{$email}" style="color:#4f46e5;">{$email}</a></td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Phone</td>
                  <td style="padding:8px 0;"><a href="tel:{$phone}" style="color:#4f46e5;">{$phone}</a></td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">City</td>
                  <td style="padding:8px 0;">{$city}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Institute Type</td>
                  <td style="padding:8px 0;">{$type}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Students</td>
                  <td style="padding:8px 0;">{$students}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;vertical-align:top;">Message</td>
                  <td style="padding:8px 0;">{$message}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">Submitted</td>
                  <td style="padding:8px 0;">{$when}</td>
                </tr>
                <tr>
                  <td style="padding:8px 0;color:#6b7280;">IP / Device</td>
                  <td style="padding:8px 0;font-size:12px;color:#6b7280;">{$ip}<br>{$ua}</td>
                </tr>
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
                <tr>
                  <td style="background-color:#4f46e5;border-radius:8px;">
                    <a href="mailto:{$email}" style="display:inline-block;padding:10px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;">
                      Reply to {$owner} →
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px;background-color:#f9fafb;border-top:1px solid #e5e7eb;text-align:center;font-size:12px;color:#6b7280;">
              CoachDesk · Signup Form · {$year}
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

    /* ============================================================
     * EMAIL: Registration → sent to prospect (auto-confirmation)
     * ============================================================ */
    protected function registrationConfirmationHtml(array $data): string
    {
        $name      = htmlspecialchars($data['owner_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $institute = htmlspecialchars($data['institute_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $year      = date('Y');

        return <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;">
          <tr>
            <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px;text-align:center;">
              <div style="font-size:24px;font-weight:700;color:#ffffff;">🎓 CoachDesk</div>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#111827;">Thanks, {$name}!</h1>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                We've received your signup request for <strong>{$institute}</strong>. Our team will contact you within <strong>24 hours</strong> to set up your CoachDesk account.
              </p>
              <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">
                In the meantime, if you have any questions, feel free to reach out at <a href="mailto:info@modulelabs.in" style="color:#4f46e5;">info@modulelabs.in</a>.
              </p>
              <p style="margin:24px 0 0;font-size:15px;color:#374151;">
                — The CoachDesk Team
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