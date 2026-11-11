<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class PlanController extends BaseController
{
    public function index()
    {
        if (! is_super_admin()) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $plans = $db->query("
            SELECT p.*,
                   (SELECT COUNT(*) FROM cd_tenant_subscriptions
                    WHERE plan_id = p.id AND status = 'active') AS active_count
            FROM cd_subscription_plans p
            ORDER BY p.sort_order ASC, p.price ASC
        ")->getResultArray();

        return view('\App\Modules\Admin\Views\plans\index', [
            'title' => 'Subscription Plans',
            'plans' => $plans,
        ]);
    }

    public function create()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        return view('App\Modules\Admin\Views\plans\form', ['title' => 'New Plan', 'plan' => null]);
    }

    public function store()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $db = db_connect();
        $db->table('cd_subscription_plans')->insert([
            'public_id'     => uuid4(),
            'name'          => $this->request->getPost('name'),
            'price'         => (float) $this->request->getPost('price'),
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'max_students'  => (int) $this->request->getPost('max_students'),
            'max_staff'     => (int) $this->request->getPost('max_staff'),
            'max_branches'  => (int) $this->request->getPost('max_branches'),
            'features_json' => json_encode([]),
            'is_active'     => 1,
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?? 0),
        ]);
        return redirect()->to('/admin/plans')->with('success', 'Plan created.');
    }

    public function edit($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $plan = db_connect()->table('cd_subscription_plans')->where('id', (int) $id)->get()->getRowArray();
        if (! $plan) return redirect()->to('/admin/plans')->with('error', 'Plan not found.');
        return view('App\Modules\Admin\Views\plans\form', ['title' => 'Edit Plan', 'plan' => $plan]);
    }

    public function update($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        db_connect()->table('cd_subscription_plans')->where('id', (int) $id)->update([
            'name'          => $this->request->getPost('name'),
            'price'         => (float) $this->request->getPost('price'),
            'billing_cycle' => $this->request->getPost('billing_cycle'),
            'max_students'  => (int) $this->request->getPost('max_students'),
            'max_staff'     => (int) $this->request->getPost('max_staff'),
            'max_branches'  => (int) $this->request->getPost('max_branches'),
            'is_active'     => (int) $this->request->getPost('is_active'),
            'sort_order'    => (int) $this->request->getPost('sort_order'),
        ]);
        return redirect()->to('/admin/plans')->with('success', 'Plan updated.');
    }

    public function toggle($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $db = db_connect();
        $plan = $db->table('cd_subscription_plans')->where('id', (int) $id)->get()->getRow();
        if (! $plan) return redirect()->to('/admin/plans')->with('error', 'Plan not found.');
        $db->table('cd_subscription_plans')->where('id', (int) $id)->update(['is_active' => $plan->is_active ? 0 : 1]);
        return redirect()->to('/admin/plans')->with('success', 'Plan ' . ($plan->is_active ? 'deactivated' : 'activated') . '.');
    }

    public function delete($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $db = db_connect();
        $active = $db->table('cd_tenant_subscriptions')->where(['plan_id' => (int) $id, 'status' => 'active'])->countAllResults();
        if ($active > 0) {
            return redirect()->to('/admin/plans')->with('error', 'Cannot delete: ' . $active . ' active subscriptions use this plan.');
        }
        $db->table('cd_subscription_plans')->where('id', (int) $id)->delete();
        return redirect()->to('/admin/plans')->with('success', 'Plan deleted.');
    }
}
