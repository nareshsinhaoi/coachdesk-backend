<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class PlatformController extends BaseController
{
    public function settings()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $db = db_connect();
        $rows = $db->table('cd_platform_settings')->get()->getResultArray();
        $settings = [];
        foreach ($rows as $r) $settings[$r['setting_key']] = json_decode($r['setting_value'], true);
        return view('App\Modules\Admin\Views\platform\settings', ['title' => 'Platform Settings', 'settings' => $settings]);
    }

    public function saveSettings()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $db = db_connect();
        foreach (['signup_enabled', 'support_email', 'trial_days', 'maintenance_mode'] as $key) {
            $val = $this->request->getPost($key);
            $exists = $db->table('cd_platform_settings')->where('setting_key', $key)->get()->getRow();
            if ($exists) $db->table('cd_platform_settings')->where('id', $exists->id)->update(['setting_value' => json_encode($val)]);
            else $db->table('cd_platform_settings')->insert(['setting_key' => $key, 'setting_value' => json_encode($val)]);
        }
        return redirect()->to('/admin/platform-settings')->with('success', 'Settings saved.');
    }

    public function broadcast()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        return view('App\Modules\Admin\Views\platform\broadcast', ['title' => 'Broadcast to All Institutes']);
    }

    public function sendBroadcast()
    {
        if (! is_super_admin()) return redirect()->to('/admin/dashboard');
        $db = db_connect();
        $title = $this->request->getPost('title');
        $message = $this->request->getPost('message');
        if (! $title || ! $message) {
            return redirect()->back()->with('error', 'Title and message required.');
        }

        // Insert notification for every tenant user (excluding super admins)
        $users = $db->table('cd_users')->where('tenant_id IS NOT NULL')->where('status', 'active')->get()->getResultArray();
        foreach ($users as $u) {
            $db->table('cd_notifications')->insert([
                'public_id' => uuid4(),
                'tenant_id' => $u['tenant_id'],
                'user_id'   => $u['id'],
                'title'     => '[Platform] ' . $title,
                'message'   => $message,
            ]);
        }
        return redirect()->to('/admin/broadcast')->with('success', 'Broadcast sent to ' . count($users) . ' users.');
    }
}