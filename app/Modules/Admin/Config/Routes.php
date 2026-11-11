<?php
//$routes = Services::routes();

$routes = service('routes');

$routes->group('admin', ['namespace' => '\App\Modules\Admin\Controllers'], function ($routes) {

    // Auth (public)
    $routes->get('login',  'AuthController::loginForm');
    $routes->post('login', 'AuthController::login');
    $routes->get('logout', 'AuthController::logout');
    // Forgot password
    $routes->get('forgot-password',  'AuthController::forgotPasswordForm');
    $routes->post('forgot-password', 'AuthController::forgotPassword');
    // Reset password
    $routes->get('reset-password/(:segment)',  'AuthController::resetPasswordForm/$1');
    $routes->post('reset-password/(:segment)', 'AuthController::resetPassword/$1');
    // Public registration (lead capture — no DB)
    $routes->get('register',  'AuthController::registerForm');
    $routes->post('register', 'AuthController::register');

    // Protected
    $routes->group('', ['namespace' => 'App\Modules\Admin\Controllers','filter' => 'adminauth'], function ($routes) {

        $routes->get('/',          '\\App\\Modules\\Admin\\Controllers\\DashboardController::index');
        $routes->get('dashboard',  '\\App\\Modules\\Admin\\Controllers\\DashboardController::index');

        // Students
        $routes->group('students', ['filter' => 'adminauth:students.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\StudentController::index');
            $routes->get('create','\App\Modules\Admin\Controllers\StudentController::create');
            $routes->post('/',   '\App\Modules\Admin\Controllers\StudentController::store');
            $routes->get('(:segment)/edit', '\App\Modules\Admin\Controllers\StudentController::edit/$1');
            $routes->post('(:segment)',     '\App\Modules\Admin\Controllers\StudentController::update/$1');
            $routes->post('(:segment)/delete','\App\Modules\Admin\Controllers\StudentController::delete/$1');
        });

        // Courses
        $routes->group('courses', ['filter' => 'adminauth:courses.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\CourseController::index');
            $routes->get('create','\App\Modules\Admin\Controllers\CourseController::create');
            $routes->post('/',   '\App\Modules\Admin\Controllers\CourseController::store');
            $routes->get('(:segment)/edit', '\App\Modules\Admin\Controllers\CourseController::edit/$1');
            $routes->post('(:segment)',     '\App\Modules\Admin\Controllers\CourseController::update/$1');
        });

        // Batches
        $routes->group('batches', ['filter' => 'adminauth:batches.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\BatchController::index');
            $routes->get('create','\App\Modules\Admin\Controllers\BatchController::create');
            $routes->post('/',   '\App\Modules\Admin\Controllers\BatchController::store');
            $routes->get('(:segment)/students', '\App\Modules\Admin\Controllers\BatchController::students/$1');
        });

        // Teachers
        $routes->group('teachers', ['filter' => 'adminauth:teachers.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\TeacherController::index');
            $routes->get('create','\App\Modules\Admin\Controllers\TeacherController::create');
            $routes->post('/',   '\App\Modules\Admin\Controllers\TeacherController::store');
        });

        // Attendance
        $routes->group('attendance', ['filter' => 'adminauth:attendance.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\AttendanceController::index');
            $routes->get('mark', '\App\Modules\Admin\Controllers\AttendanceController::mark');
            $routes->post('save','\App\Modules\Admin\Controllers\AttendanceController::save');
        });

        // Fees
        $routes->group('fees', ['filter' => 'adminauth:fees.view'], function ($routes) {
            $routes->get('/',       '\App\Modules\Admin\Controllers\FeeController::index');
            $routes->get('collect', '\App\Modules\Admin\Controllers\FeeController::collect');
            $routes->post('/',      '\App\Modules\Admin\Controllers\FeeController::store');
            // Receipt PDF routes — put these BEFORE any (:segment) catch-all
            $routes->get('(:segment)/receipt',        '\App\Modules\Admin\Controllers\FeeController::receipt/$1',       ['filter' => 'adminauth:fees.view']);
            $routes->get('(:segment)/receipt/inline', '\App\Modules\Admin\Controllers\FeeController::receiptInline/$1', ['filter' => 'adminauth:fees.view']);
            $routes->get('(:segment)/success',        '\App\Modules\Admin\Controllers\FeeController::success/$1',       ['filter' => 'adminauth:fees.view']);
        });

        // Exams
        $routes->group('exams', ['filter' => 'adminauth:exams.view'], function ($routes) {
            $routes->get('/',    '\App\Modules\Admin\Controllers\ExamController::index');
            $routes->post('/',   '\App\Modules\Admin\Controllers\ExamController::create');
            $routes->get('(:segment)/marks', '\App\Modules\Admin\Controllers\ExamController::marks/$1');
        });

        // Reports
        $routes->get('reports', '\App\Modules\Admin\Controllers\ReportController::index', ['filter' => 'adminauth:reports.view']);

        // Branches
        $routes->get('branches', '\App\Modules\Admin\Controllers\BranchController::index', ['filter' => 'adminauth:branches.view']);

        // Users
// $routes->get('users', '\App\Modules\Admin\Controllers\UserController::index', ['filter' => 'adminauth:users.view']);
// $routes->post('users/(:num)/reset-password', '\App\Modules\Admin\Controllers\UserAdminController::resetPassword/$1', ['filter' => 'adminauth']);
// $routes->post('users/(:num)/force-logout',   '\App\Modules\Admin\Controllers\UserAdminController::forceLogout/$1',   ['filter' => 'adminauth']);
// $routes->post('users/(:num)/reset-password', 'UserAdminController::resetPassword/$1', ['filter' => 'adminauth']);
// $routes->post('users/(:num)/force-logout',   'UserAdminController::forceLogout/$1',   ['filter' => 'adminauth']); 

$routes->group('users', function ($routes) {
    $routes->get('/',              '\App\Modules\Admin\Controllers\UserController::index');
    $routes->get('create',         '\App\Modules\Admin\Controllers\UserController::create');
    #$routes->get('create',         '\App\Modules\Admin\Controllers\UserController::create');
    $routes->post('/',             '\App\Modules\Admin\Controllers\UserController::store');
    $routes->post('(:num)/reset-password', '\App\Modules\Admin\Controllers\UserAdminController::resetPassword/$1');
    $routes->post('(:num)/force-logout',   '\App\Modules\Admin\Controllers\UserAdminController::forceLogout/$1');
});
        
        
        // Settings
        $routes->group('settings', ['filter' => 'adminauth:settings.manage'], function ($routes) {
            $routes->get('/', '\App\Modules\Admin\Controllers\SettingController::index');
            $routes->post('/', '\App\Modules\Admin\Controllers\SettingController::save');
        });


        // ---- SUPER ADMIN routes ----
        $routes->group('tenants', ['filter' => 'adminauth'], function ($routes) {
            $routes->get('/',       '\App\Modules\Admin\Controllers\TenantController::index');
            $routes->get('(:num)',  '\App\Modules\Admin\Controllers\TenantController::show/$1');

            $routes->get('create',           '\App\Modules\Admin\Controllers\TenantController::create');
            $routes->post('/',               '\App\Modules\Admin\Controllers\TenantController::store');
            $routes->get('(:num)/edit',      '\App\Modules\Admin\Controllers\TenantController::edit/$1');
            $routes->post('(:num)',          '\App\Modules\Admin\Controllers\TenantController::update/$1');
            $routes->post('(:num)/status',   '\App\Modules\Admin\Controllers\TenantController::status/$1');
            $routes->post('(:num)/delete',   '\App\Modules\Admin\Controllers\TenantController::delete/$1');

        });

        $routes->group('plans', ['filter' => 'adminauth'], function ($routes) {
            $routes->get('/',              '\App\Modules\Admin\Controllers\PlanController::index');
            $routes->get('create',         '\App\Modules\Admin\Controllers\PlanController::create');
            $routes->post('/',             '\App\Modules\Admin\Controllers\PlanController::store');
            $routes->get('(:num)/edit',    '\App\Modules\Admin\Controllers\PlanController::edit/$1');
            $routes->post('(:num)',        '\App\Modules\Admin\Controllers\PlanController::update/$1');
            $routes->post('(:num)/toggle', '\App\Modules\Admin\Controllers\PlanController::toggle/$1', ['filter' => 'adminauth']);
            $routes->post('(:num)/delete', '\App\Modules\Admin\Controllers\PlanController::delete/$1', ['filter' => 'adminauth']);
        });

        $routes->get('subscriptions', '\App\Modules\Admin\Controllers\SubscriptionController::index', ['filter' => 'adminauth']);
        $routes->post('subscriptions/(:num)/renew',  '\App\Modules\Admin\Controllers\SubscriptionController::renew/$1',  ['filter' => 'adminauth']);
        $routes->post('subscriptions/(:num)/cancel', '\App\Modules\Admin\Controllers\SubscriptionController::cancel/$1', ['filter' => 'adminauth']);

        $routes->group('support', ['filter' => 'adminauth'], function ($routes) {
            $routes->get('/',              '\App\Modules\Admin\Controllers\SupportController::index');
            $routes->post('(:num)',        '\App\Modules\Admin\Controllers\SupportController::update/$1');
        });

        $routes->get('audit-logs', '\App\Modules\Admin\Controllers\AuditLogController::index', ['filter' => 'adminauth']);
        $routes->get('reports/revenue', '\App\Modules\Admin\Controllers\ReportAdminController::revenue', ['filter' => 'adminauth']);

        $routes->get('platform-settings', '\App\Modules\Admin\Controllers\PlatformController::settings', ['filter' => 'adminauth']);
        $routes->post('platform-settings', '\App\Modules\Admin\Controllers\PlatformController::saveSettings', ['filter' => 'adminauth']);
        $routes->get('broadcast', '\App\Modules\Admin\Controllers\PlatformController::broadcast', ['filter' => 'adminauth']);
        $routes->post('broadcast', '\App\Modules\Admin\Controllers\PlatformController::sendBroadcast', ['filter' => 'adminauth']);

        // Profile / Change Password (all roles)
        $routes->group('profile', ['filter' => 'adminauth'], function ($routes) {
            $routes->get('/',                  '\App\Modules\Admin\Controllers\ProfileController::index');
            $routes->post('/',                 '\App\Modules\Admin\Controllers\ProfileController::update');
            $routes->get('change-password',    '\App\Modules\Admin\Controllers\ProfileController::changePasswordForm');
            $routes->post('change-password',   '\App\Modules\Admin\Controllers\ProfileController::changePassword');
        });

    });
});
