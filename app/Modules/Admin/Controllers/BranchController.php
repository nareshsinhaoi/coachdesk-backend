<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class BranchController extends BaseController
{
    public function index()
    {
        $branches = db_connect()->table('cd_branches')
            ->where('tenant_id', tenant_id())->where('deleted_at IS NULL')
            ->get()->getResultArray();
        return view('App\Modules\Admin\Views\branches\index', ['title' => 'Branches', 'branches' => $branches]);
    }
}