<?php

namespace App\Modules\Api\Controllers\V1;

use App\Controllers\BaseController;
use App\Modules\Api\Libraries\TenantContext;

class SettingsApiController extends BaseController
{
    /** GET /api/v1/settings — institute profile + key/value settings */
    public function index()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db = db_connect();

        $tenant = $db->table('cd_tenants')
            ->select('id, public_id, name, code, email, phone, website, address_line1, address_line2, city, state, pincode, country, timezone, currency, status')
            ->where('id', $tenantId)->get()->getRowArray();

        $rows     = $db->table('cd_settings')->where('tenant_id', $tenantId)->get()->getResultArray();
        $settings = [];
        foreach ($rows as $r) {
            $decoded = json_decode($r['setting_value'], true);
            $settings[$r['setting_key']] = is_array($decoded) ? $decoded : $r['setting_value'];
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Settings retrieved',
            'data'   => ['tenant' => $tenant, 'settings' => $settings],
        ]);
    }

    /** PUT /api/v1/settings — { tenant: {...}, settings: { branding: {...} } } */
    public function save()
    {
        $tenantId = TenantContext::getTenantId();
        if ($tenantId === null) {
            return $this->response->setStatusCode(400)
                ->setJSON(['status' => false, 'message' => 'Tenant context required']);
        }

        $db   = db_connect();
        $body = $this->request->getJSON(true) ?? [];

        // --- Institute profile ---
        $tenantFields = ['name', 'phone', 'email', 'website', 'address_line1', 'address_line2', 'city', 'state', 'pincode', 'country', 'timezone', 'currency'];
        $tenantUpdate = [];
        if (! empty($body['tenant']) && is_array($body['tenant'])) {
            foreach ($tenantFields as $f) {
                if (array_key_exists($f, $body['tenant'])) {
                    $tenantUpdate[$f] = ($body['tenant'][$f] === '' ? null : $body['tenant'][$f]);
                }
            }
            if (! empty($tenantUpdate['email']) && ! filter_var($tenantUpdate['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->response->setStatusCode(422)
                    ->setJSON(['status' => false, 'message' => 'Invalid institute email']);
            }
            // name is NOT NULL — don't blank it
            if (isset($tenantUpdate['name']) && ($tenantUpdate['name'] === null || $tenantUpdate['name'] === '')) {
                unset($tenantUpdate['name']);
            }
            if ($tenantUpdate) {
                $db->table('cd_tenants')->where('id', $tenantId)->update($tenantUpdate);
            }
        }

        // --- Key/value settings (branding etc.) ---
        if (! empty($body['settings']) && is_array($body['settings'])) {
            foreach ($body['settings'] as $key => $value) {
                if (! is_string($key) || strlen($key) > 100) continue;
                $encoded = is_array($value) ? json_encode($value) : (string) $value;

                $exists = $db->table('cd_settings')
                    ->where(['tenant_id' => $tenantId, 'setting_key' => $key])->get()->getRow();

                if ($exists) {
                    $db->table('cd_settings')->where('id', $exists->id)
                        ->update(['setting_value' => $encoded, 'updated_at' => date('Y-m-d H:i:s')]);
                } else {
                    $db->table('cd_settings')->insert([
                        'tenant_id'     => $tenantId,
                        'setting_key'   => $key,
                        'setting_value' => $encoded,
                    ]);
                }
            }
        }

        $tenant = $db->table('cd_tenants')->where('id', $tenantId)->get()->getRowArray();
        $rows   = $db->table('cd_settings')->where('tenant_id', $tenantId)->get()->getResultArray();
        $settings = [];
        foreach ($rows as $r) {
            $decoded = json_decode($r['setting_value'], true);
            $settings[$r['setting_key']] = is_array($decoded) ? $decoded : $r['setting_value'];
        }

        return $this->response->setJSON([
            'status' => true,
            'message' => 'Settings saved',
            'data'   => ['tenant' => $tenant, 'settings' => $settings],
        ]);
    }
}
