<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table            = 'tenants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;

    protected $allowedFields = [
        'public_id', 'name', 'code', 'email', 'phone', 'website',
        'logo_path', 'address_line1', 'address_line2', 'city', 'state',
        'pincode', 'country', 'status', 'trial_ends_at', 'timezone',
        'currency', 'locale',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'name'  => 'required|min_length[2]|max_length[150]',
        'code'  => 'required|alpha_dash|min_length[2]|max_length[30]|is_unique[tenants.code,id,{id}]',
        'email' => 'required|valid_email|max_length[150]',
        'status'=> 'in_list[trial,active,suspended,expired,cancelled]',
    ];
    protected $validationMessages = [
        'code' => ['is_unique' => 'This tenant code is already taken.'],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generatePublicId'];
    protected $beforeUpdate   = [];

    protected function generatePublicId(array $data): array
    {
        if (empty($data['data']['public_id'])) {
            $data['data']['public_id'] = $this->uuid4();
        }
        return $data;
    }

    /* -------- Helpers -------- */

    public function findByPublicId(string $publicId): ?array
    {
        return $this->where('public_id', $publicId)->first();
    }

    public function findByCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }

    public function activeTenants(): array
    {
        return $this->where('status', 'active')->findAll();
    }

    public function trialTenants(): array
    {
        return $this->where('status', 'trial')
                    ->where('trial_ends_at >=', date('Y-m-d H:i:s'))
                    ->findAll();
    }

    public function expiringTrials(int $days = 3): array
    {
        return $this->where('status', 'trial')
                    ->where('trial_ends_at <=', date('Y-m-d H:i:s', strtotime("+{$days} days")))
                    ->where('trial_ends_at >=', date('Y-m-d H:i:s'))
                    ->findAll();
    }

    protected function uuid4(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}