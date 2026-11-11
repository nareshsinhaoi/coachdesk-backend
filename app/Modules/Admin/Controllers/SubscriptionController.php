<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

require_once __DIR__ . '/../Helpers/admin_helper.php';

class SubscriptionController extends BaseController
{
    public function index()
    {
        if (! is_super_admin()) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $subs = $db->query("
            SELECT ts.*, t.name AS tenant_name, t.code AS tenant_code,
                   p.name AS plan_name, p.price
            FROM cd_tenant_subscriptions ts
            JOIN cd_tenants t          ON t.id = ts.tenant_id
            JOIN cd_subscription_plans p ON p.id = ts.plan_id
            ORDER BY ts.id DESC
        ")->getResultArray();

        $payments = $db->query("
            SELECT sp.*, t.name AS tenant_name
            FROM cd_subscription_payments sp
            JOIN cd_tenants t ON t.id = sp.tenant_id
            ORDER BY sp.id DESC
            LIMIT 20
        ")->getResultArray();

        return view('App\Modules\Admin\Views\subscriptions\index', [
            'title'    => 'Subscriptions',
            'subs'     => $subs,
            'payments' => $payments,
        ]);
    }


    public function renew($id)
    {
        if (!is_super_admin()) {
            return redirect()->to('/admin/dashboard');
        }

        $db = db_connect();
        $id = (int) $id;

        // Get subscription
        $sub = $db->table('cd_tenant_subscriptions')
            ->where('id', $id)
            ->get()
            ->getRow();

        if (!$sub) {
            return redirect()->to('/admin/subscriptions')
                ->with('error', 'Subscription not found.');
        }

        // Get subscription plan
        $plan = $db->table('cd_subscription_plans')
            ->where('id', $sub->plan_id)
            ->get()
            ->getRow();

        if (!$plan) {
            return redirect()->to('/admin/subscriptions')
                ->with('error', 'Subscription plan not found.');
        }

        // Calculate new end date
        $billingCycle = strtolower(trim($plan->billing_cycle));

        // Continue from existing end date
        $baseDate = $sub->end_date;

        // If subscription has already expired, renew from today
        if (strtotime($baseDate) < strtotime(date('Y-m-d'))) {
            $baseDate = date('Y-m-d');
        }

        switch ($billingCycle) {

            case 'quarterly':
                $newEnd = date('Y-m-d', strtotime($baseDate . ' +3 months'));
                break;

            case 'half-yearly':
                $newEnd = date('Y-m-d', strtotime($baseDate . ' +6 months'));
                break;

            case 'yearly':
                $newEnd = date('Y-m-d', strtotime($baseDate . ' +1 year'));
                break;

            case 'monthly':
            default:
                $newEnd = date('Y-m-d', strtotime($baseDate . ' +1 month'));
                break;
        }

        // Update subscription
        $db->table('cd_tenant_subscriptions')
            ->where('id', $id)
            ->update([
                'end_date' => $newEnd,
                'status'   => 'active',
            ]);

        return redirect()->to('/admin/subscriptions')
            ->with('success', 'Subscription renewed until ' . $newEnd);
    }


    public function cancel($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        db_connect()->table('cd_tenant_subscriptions')->where('id', (int) $id)
            ->update(['status' => 'cancelled', 'auto_renew' => 0]);
        return redirect()->to('/admin/subscriptions')->with('success', 'Subscription cancelled.');
    }
}
