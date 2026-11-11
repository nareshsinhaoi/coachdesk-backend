<?php
// // app/Database/Seeds/ExtendedPermissionsSeeder.php

namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class ExtendedPermissionsSeeder extends Seeder
{
    public function run()
    {
        $perms = [
            ['permission_key' => 'students.delete',   'module' => 'students'],
            ['permission_key' => 'courses.view',      'module' => 'courses'],
            ['permission_key' => 'courses.manage',    'module' => 'courses'],
            ['permission_key' => 'subjects.view',     'module' => 'subjects'],
            ['permission_key' => 'subjects.manage',   'module' => 'subjects'],
            ['permission_key' => 'batches.view',      'module' => 'batches'],
            ['permission_key' => 'batches.manage',    'module' => 'batches'],
        ];
        $db = $this->db;
        foreach ($perms as $p) {
            $exists = $db->table('cd_permissions')->where('permission_key', $p['permission_key'])->countAllResults();
            if (! $exists) $db->table('cd_permissions')->insert($p);
        }
        // Grant all new perms to Owner role
        $owner = $db->table('cd_roles')->where('slug', 'owner')->get()->getRow();
        foreach ($perms as $p) {
            $perm = $db->table('cd_permissions')->where('permission_key', $p['permission_key'])->get()->getRow();
            $exists = $db->table('cd_role_permissions')->where(['role_id' => $owner->id, 'permission_id' => $perm->id])->countAllResults();
            if (! $exists) $db->table('cd_role_permissions')->insert(['role_id' => $owner->id, 'permission_id' => $perm->id]);
        }
        echo "Extended permissions added.\n";
    }
}