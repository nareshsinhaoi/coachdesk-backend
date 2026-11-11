<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Services\DashboardService;

class DashboardController extends BaseController
{
    public function index()
    {
        $service = new DashboardService();
        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Dashboard stats retrieved',
            'data'    => $service->stats(),
        ]);
    }
}