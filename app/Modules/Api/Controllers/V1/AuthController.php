<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\JwtService;
use App\Modules\Api\Libraries\TenantContext;
use CodeIgniter\HTTP\Exceptions\HTTPException;

class AuthController extends BaseController
{
    public function login()
    {
        //$a= $this->request->getJSON(true); print_r($a); die("sdf"); 
        try {
            $data = $this->request->getJSON(true);
        } catch (HTTPException $e) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'message' => 'Invalid JSON request body',
            ]);
        }

        $email = is_array($data) ? ($data['email'] ?? null) : null;
        $password = is_array($data) ? ($data['password'] ?? null) : null;
        
        if (empty($email) || empty($password)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false,
                'message' => 'Email and password required',
            ]);
        }

        $db = db_connect();
        $user = $db->table('cd_users')
            ->where('email', $email)
            ->where('status', 'active')
            ->get()
            ->getRow();

        if ($user === null || ! password_verify($password, $user->password_hash)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => false,
                'message' => 'Invalid credentials',
            ]);
        }

        $jwt = new JwtService();
        $accessToken = $jwt->generateAccessToken((array) $user, null);
        $refreshToken = $jwt->generateRefreshToken();

        // Store refresh token hash
        $db->table('cd_refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $jwt->getRefreshTtl()),
        ]);

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'user' => [
                    'id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant_id' => $user->tenant_id,
                ],
            ],
        ]);
    }

    public function refresh()
    {
        $refreshToken = $this->request->getJsonVar('refresh_token');
        if (empty($refreshToken)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => false,
                'message' => 'Refresh token required',
            ]);
        }

        $db = db_connect();
        $stored = $db->table('cd_refresh_tokens')
            ->where('token_hash', hash('sha256', $refreshToken))
            ->where('revoked_at IS NULL')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()
            ->getRow();

        if ($stored === null) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => false,
                'message' => 'Invalid refresh token',
            ]);
        }

        $user = $db->table('cd_users')->where('id', $stored->user_id)->get()->getRow();
        $jwt = new JwtService();
        $newAccess = $jwt->generateAccessToken((array) $user);

        // Rotate refresh token
        $db->table('cd_refresh_tokens')->where('id', $stored->id)->update(['revoked_at' => date('Y-m-d H:i:s')]);
        $newRefresh = $jwt->generateRefreshToken();
        $db->table('cd_refresh_tokens')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $newRefresh),
            'expires_at' => date('Y-m-d H:i:s', time() + $jwt->getRefreshTtl()),
        ]);

        return $this->response->setJSON([
            'status' => true,
            'data' => ['access_token' => $newAccess, 'refresh_token' => $newRefresh],
        ]);
    }

    public function logout()
    {
        $refreshToken = $this->request->getJsonVar('refresh_token');
        if ($refreshToken) {
            db_connect()->table('cd_refresh_tokens')
                ->where('token_hash', hash('sha256', $refreshToken))
                ->update(['revoked_at' => date('Y-m-d H:i:s')]);
        }
        return $this->response->setJSON(['status' => true, 'message' => 'Logged out']);
    }

    public function me()
    {
        $user = $this->request->user;
        return $this->response->setJSON([
            'status' => true,
            'data' => [
                'id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant_id' => $user->tenant_id,
            ],
        ]);
    }

    /** PUT /api/v1/auth/profile — { name?, email? } */
    public function updateProfile()
    {
        $user = $this->request->user;
        $body = $this->request->getJSON(true) ?? [];

        $update = [];
        if (! empty($body['name']) && is_string($body['name'])) {
            $update['name'] = trim($body['name']);
        }
        if (! empty($body['email']) && filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            $update['email'] = trim($body['email']);
        }

        if (! $update) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'Nothing to update (name/email required)']);
        }

        $db = db_connect();

        // Prevent duplicate emails
        if (isset($update['email'])) {
            $dup = $db->table('cd_users')
                ->where('email', $update['email'])->where('id !=', $user->id)
                ->countAllResults();
            if ($dup) {
                return $this->response->setStatusCode(409)
                    ->setJSON(['status' => false, 'message' => 'Email already in use']);
            }
        }

        $db->table('cd_users')->where('id', $user->id)->update($update);
        $fresh = $db->table('cd_users')->where('id', $user->id)->get()->getRow();

        return $this->response->setJSON([
            'status' => true, 'message' => 'Profile updated',
            'data' => ['id' => $fresh->public_id, 'name' => $fresh->name, 'email' => $fresh->email, 'tenant_id' => $fresh->tenant_id],
        ]);
    }

    /** POST /api/v1/auth/change-password — { current_password, new_password } */
    public function changePassword()
    {
        $user = $this->request->user;
        $body = $this->request->getJSON(true) ?? [];

        $current = $body['current_password'] ?? '';
        $new     = $body['new_password'] ?? '';

        if ($current === '' || strlen($new) < 6) {
            return $this->response->setStatusCode(422)
                ->setJSON(['status' => false, 'message' => 'Current password required; new password must be at least 6 characters']);
        }

        $db   = db_connect();
        $row  = $db->table('cd_users')->where('id', $user->id)->get()->getRow();

        if (! $row || ! password_verify($current, $row->password_hash)) {
            return $this->response->setStatusCode(401)
                ->setJSON(['status' => false, 'message' => 'Current password is incorrect']);
        }

        $db->table('cd_users')->where('id', $user->id)
            ->update(['password_hash' => password_hash($new, PASSWORD_DEFAULT)]);

        // Revoke all refresh tokens so other sessions must re-login
        $db->table('cd_refresh_tokens')
            ->where('user_id', $user->id)
            ->where('revoked_at IS NULL')
            ->update(['revoked_at' => date('Y-m-d H:i:s')]);

        return $this->response->setJSON(['status' => true, 'message' => 'Password changed successfully']);
    }
}