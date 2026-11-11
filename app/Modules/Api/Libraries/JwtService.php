<?php

namespace App\Modules\Api\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Services;

class JwtService
{
    private string $secret;
    private string $algo = 'HS256';
    private int $accessTtl = 3600;      // 1 hour
    private int $refreshTtl = 604800;   // 7 days

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'change-me-in-env');
    }

    public function generateAccessToken(array $user, ?int $branchId = null): string
    {
        $payload = [
            'iss' => 'coachdesk',
            'sub' => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'branch_id' => $branchId,
            'email' => $user['email'],
            'iat' => time(),
            'exp' => time() + $this->accessTtl,
        ];
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    public function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    public function validateAccessToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getRefreshTtl(): int
    {
        return $this->refreshTtl;
    }
}