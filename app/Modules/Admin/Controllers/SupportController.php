<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class SupportController extends BaseController
{
    public function index()
    {
        if (! is_super_admin()) {
            return redirect()->to('/admin/dashboard')->with('error', 'Access denied.');
        }

        $db = db_connect();
        $tickets = $db->query("
            SELECT st.*, t.name AS tenant_name, u.name AS user_name, u.email AS user_email
            FROM cd_support_tickets st
            LEFT JOIN cd_tenants t ON t.id = st.tenant_id
            LEFT JOIN cd_users   u ON u.id = st.user_id
            ORDER BY
              FIELD(st.status, 'open','in_progress','resolved','closed'),
              FIELD(st.priority, 'urgent','high','medium','low'),
              st.id DESC
        ")->getResultArray();

        return view('App\Modules\Admin\Views\support\index', [
            'title'   => 'Support Tickets',
            'tickets' => $tickets,
        ]);
    }

    public function update($id)
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');

        $status = $this->request->getPost('status');
        if (in_array($status, ['open','in_progress','resolved','closed'])) {
            db_connect()->table('cd_support_tickets')->where('id', (int) $id)->update([
                'status' => $status,
            ]);
        }
        return redirect()->to('/admin/support')->with('success', 'Ticket updated.');
    }
}