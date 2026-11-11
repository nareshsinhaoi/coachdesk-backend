<?php

namespace App\Modules\Admin\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['permission_key', 'module', 'description'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function allGroupedByModule(): array
    {
        $rows = $this->orderBy('module', 'asc')->orderBy('permission_key', 'asc')->findAll();
        $out = [];
        foreach ($rows as $r) $out[$r['module']][] = $r;
        return $out;
    }
}