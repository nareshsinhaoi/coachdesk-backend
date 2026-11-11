<?php

if (! function_exists('current_user')) {
    function current_user(): ?object {
        static $u = null;
        if ($u !== null) return $u;
        $id = session()->get('user_id');
        if (! $id) return null;
        $u = db_connect()->table('cd_users')->where('id', $id)->get()->getRow();
        return $u;
    }
}

// if (! function_exists('tenant_id')) {
//     function tenant_id(): ?int {
//         $u = current_user();
//         return $u ? (int) $u->tenant_id : null;
//     }
// }

if (! function_exists('tenant_id')) {
    function tenant_id(): ?int {
        $u = current_user();
        if (! $u) return null;
        if ($u->tenant_id === null) return null;   // super admin → null (means "all")

        return (int) $u->tenant_id;
    }
}

if (! function_exists('branch_id')) {
    function branch_id(): ?int {
        return session()->get('branch_id');
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool {
        $u = current_user();
        return $u !== null && $u->tenant_id === null;
    }
}

if (! function_exists('can')) {
    function can(string $perm): bool {
        static $cache = [];
        if (isset($cache[$perm])) return $cache[$perm];
        $u = current_user();
        if (! $u) return $cache[$perm] = false;
        if ($u->tenant_id === null) return $cache[$perm] = true; // super admin
        $db = db_connect();
        // user-level override wins
        $override = $db->table('cd_user_permissions up')
            ->join('cd_permissions p', 'p.id = up.permission_id')
            ->where(['up.user_id' => $u->id, 'p.permission_key' => $perm])
            ->get()->getRow();
        if ($override) return $cache[$perm] = ($override->effect === 'allow');
        // role perms
        return $cache[$perm] = $db->table('cd_user_roles ur')
            ->join('cd_role_permissions rp', 'rp.role_id = ur.role_id')
            ->join('cd_permissions p', 'p.id = rp.permission_id')
            ->where(['ur.user_id' => $u->id, 'p.permission_key' => $perm])
            ->countAllResults() > 0;
    }
}

if (! function_exists('tenant_scope')) {
    function tenant_scope($builder) {
        $tid = tenant_id();
        if ($tid !== null) $builder->where('tenant_id', $tid);
        return $builder;
    }
}

if (! function_exists('flash_alert')) {
    function flash_alert(): string {
        $out = '';
        foreach (['success', 'error', 'info', 'warning'] as $type) {
            $msg = session()->getFlashdata($type);
            if ($msg) {
                $out .= '<div class="alert alert-' . $type . '">' . esc($msg) . '</div>';
            }
        }
        return $out;
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string {
        $p = explode(' ', trim($name));
        return strtoupper(substr($p[0] ?? '', 0, 1) . substr($p[1] ?? '', 0, 1));
    }
}

if (! function_exists('money')) {
    function money($amount, $currency = '₹'): string {
        return $currency . number_format((float) $amount, 2);
    }
}

if (! function_exists('uuid4')) {
    function uuid4(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }
}

if (! function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (! function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

if (! function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}



/** Email helper functions  */


/**
 * Send email using SendInBlue API
 * 
 * @param array $inputData Email data (email, name, subject, message, template_id, params, bcc)
 * @return bool True if sent successfully
 */
function get_sendinblue_api_key()
{
    static $key = null;
    static $loaded = false;

    if ($loaded) {
        return $key;
    }
    $loaded = true;

    $key = getenv('SENDINBLUE_API_KEY');
    if ($key !== false && trim($key) !== '') {
        $key = trim($key);
        return $key;
    }

    if (isset($_ENV['SENDINBLUE_API_KEY']) && trim($_ENV['SENDINBLUE_API_KEY']) !== '') {
        $key = trim($_ENV['SENDINBLUE_API_KEY']);
        return $key;
    }

    if (isset($_SERVER['SENDINBLUE_API_KEY']) && trim($_SERVER['SENDINBLUE_API_KEY']) !== '') {
        $key = trim($_SERVER['SENDINBLUE_API_KEY']);
        return $key;
    }

    $envFile = dirname(APPPATH) . DIRECTORY_SEPARATOR . '.env';
    if (is_readable($envFile)) {
        $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        if (isset($env['SENDINBLUE_API_KEY']) && trim($env['SENDINBLUE_API_KEY']) !== '') {
            $key = trim($env['SENDINBLUE_API_KEY']);
            return $key;
        }
    }

    $key = null;
    return null;
}

function NexusSendEmail($inputData)
{
    // $key = getenv('SENDINBLUE_API_KEY');
    // $sendinBlueKey = $key;
    $sendinBlueKey = get_sendinblue_api_key();

    $email   = isset($inputData['email']) ? trim($inputData['email']) : '';
    $name    = isset($inputData['name']) && trim($inputData['name']) != '' ? trim($inputData['name']) : null;
    $message = isset($inputData['message']) ? trim($inputData['message']) : '';
    $subject = isset($inputData['subject']) ? trim($inputData['subject']) : '';
    $template_id = isset($inputData['template_id']) ? $inputData['template_id'] : null;
    $params = isset($inputData['params']) ? $inputData['params'] : [];

    // BCC email addresses
    $bcc_emails = isset($inputData['bcc']) ? $inputData['bcc'] : [];

    // Default BCC emails
    if (empty($bcc_emails)) {
        $bcc_emails = [
            'nareshsinha3@gmail.com',
        ];
    }

    if (empty($email)) {
        // log_message('error', 'NexusSendEmail: Email is mandatory');
        return false;
    }

    // Build the "to" array
    $toData = ['email' => $email];
    if ($name !== null) {
        $toData['name'] = $name;
    }

    // Build BCC array
    $bccData = [];
    foreach ($bcc_emails as $bcc_email) {
        if (!empty($bcc_email) && filter_var($bcc_email, FILTER_VALIDATE_EMAIL)) {
            $bccData[] = ['email' => trim($bcc_email)];
        }
    }

    // Prepare email data
    if ($template_id) {
        $data = [
            'sender' => [
                'email' => 'nexus@modulelabs.in',
                'name'  => 'NEXUS Media'
            ],
            'to' => [$toData],
            'bcc' => $bccData,
            'templateId' => $template_id,
            'params' => $params
        ];
    } else {
        $data = [
            'sender' => [
                'email' => 'nexus@modulelabs.in',
                'name'  => 'NEXUS Media'
            ],
            'to' => [$toData],
            'bcc' => $bccData,
            'subject' => $subject,
            'htmlContent' => $message,
        ];
    }

    // Send email via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendinblue.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $headers = [
        'Accept: application/json',
        'Api-Key: ' . $sendinBlueKey,
        'Content-Type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);
    
    // Log for debugging
    // log_message('debug', 'NexusSendEmail: Email sent to ' . $email . ', HTTP Code: ' . $httpCode);
    // if ($httpCode != 201) {
    //     log_message('error', 'NexusSendEmail failed: ' . json_encode($response));
    // }
    return $httpCode == 201 && !empty($response['messageId']);
}

 
// function write_log_email_attempt($email, $subject, $httpCode, $response, $bcc_emails = [])
// {
//     $log = date('Y-m-d H:i:s') . " - Email to: {$email}, Subject: {$subject}, HTTP: {$httpCode}\n";
//     if (!empty($bcc_emails)) {
//         $log .= "BCC: " . implode(', ', $bcc_emails) . "\n";
//     }
//     if ($httpCode != 201) {
//         $log .= "Error: " . json_encode($response) . "\n";
//     }
//     // Uncomment to log to file
//     // file_put_contents(WRITEPATH . '/logs/email_logs.log', $log, FILE_APPEND);
// }