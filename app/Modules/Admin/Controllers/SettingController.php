<?php
namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
require_once __DIR__ . '/../Helpers/admin_helper.php';

class SettingController extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $tenant = $db->table('cd_tenants')->where('id', tenant_id())->get()->getRowArray();
        $settings = $db->table('cd_settings')->where('tenant_id', tenant_id())->get()->getResultArray();
        $settingsMap = [];
        foreach ($settings as $s) $settingsMap[$s['setting_key']] = json_decode($s['setting_value'], true);

        return view('App\Modules\Admin\Views\settings\index', [
            'title' => 'Settings', 'tenant' => $tenant, 'settings' => $settingsMap,
        ]);
    }

    public function save()
    {
        $db = db_connect();
        $db->table('cd_tenants')->where('id', tenant_id())->update([
            'name'    => $this->request->getPost('name'),
            'phone'   => $this->request->getPost('phone'),
            'email'   => $this->request->getPost('email'),
            'address_line1' => $this->request->getPost('address_line1'),
            'city'    => $this->request->getPost('city'),
            'state'   => $this->request->getPost('state'),
        ]);

        $branding = [
            'primary_color'  => $this->request->getPost('primary_color') ?: '#4f46e5',
            'receipt_footer' => $this->request->getPost('receipt_footer'),
        ];
        $this->upsertSetting('branding', $branding);

        return redirect()->to('/admin/settings')->with('success', 'Settings saved.');
    }

    protected function upsertSetting(string $key, array $value)
    {
        $db = db_connect();
        $exists = $db->table('cd_settings')
            ->where(['tenant_id' => tenant_id(), 'setting_key' => $key])->get()->getRow();
        if ($exists) {
            $db->table('cd_settings')->where('id', $exists->id)
                ->update(['setting_value' => json_encode($value)]);
        } else {
            $db->table('cd_settings')->insert([
                'tenant_id'     => tenant_id(),
                'setting_key'   => $key,
                'setting_value' => json_encode($value),
            ]);
        }
    }
}