<?php

namespace App\Modules\Api\Config;

$routes = service('routes');

$routes->group('api/v1', ['namespace' => 'App\Modules\Api\Controllers\V1'], function ($routes) {
    //die("ASDASD");
    // Public auth endpoints
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/refresh', 'AuthController::refresh');
    $routes->options('dashboard', 'DashboardController::index');

    // Protected endpoints
    $routes->group('', ['namespace' => '\App\Modules\Api\Controllers\V1', 'filter' => ['cors', 'jwtauth', 'tenant'], ], function ($routes) {

        $routes->get('dashboard', 'DashboardController::index');
        $routes->get('auth/me', 'AuthController::me');
        $routes->post('auth/logout', 'AuthController::logout');

        // Tenants (super admin only for create/edit)
        $routes->get('tenants', 'TenantController::index');
        $routes->get('tenants/(:segment)', 'TenantController::show/$1');

        // Branches
        $routes->get('branches', 'BranchController::index');
        $routes->post('branches', 'BranchController::create', ['filter' => 'permission:settings.manage']);

        // Roles
        $routes->get('roles', 'RoleController::index');
        $routes->get('roles/(:num)/permissions', 'RoleController::permissions/$1');


        $routes->get('students', 'StudentController::index');
        $routes->get('batches', 'BatchController::index');
        $routes->get('courses', 'CourseController::index');

        // Attendance / Fees / Exams / Reports
        $routes->get('attendance',        'OperationsController::attendanceIndex');
        $routes->get('attendance/mark',   'OperationsController::attendanceMark');
        $routes->post('attendance/save',  'OperationsController::attendanceSave');

        $routes->get('fees',              'OperationsController::feesIndex');
        $routes->post('fees/collect',     'OperationsController::feesCollect');

        $routes->get('exams',             'OperationsController::examsIndex');
        $routes->post('exams',            'OperationsController::examsCreate');
        $routes->get('exams/(:segment)/marks',        'OperationsController::examMarks/$1');
        $routes->post('exams/(:segment)/marks',       'OperationsController::examMarksSave/$1');

        $routes->get('reports',           'OperationsController::reportsIndex');

        // Branches / Users / Settings (admin frontend)
        $routes->get('branches/all',      'BranchApiController::index');
        $routes->post('branches/all',     'BranchApiController::create');
        $routes->put('branches/all/(:segment)',   'BranchApiController::update/$1');
        $routes->delete('branches/all/(:segment)', 'BranchApiController::delete/$1');

        $routes->get('users',             'UserApiController::index');
        $routes->get('users/form-options','UserApiController::formOptions');
        $routes->post('users',            'UserApiController::create');
        $routes->put('users/(:segment)',  'UserApiController::update/$1');
        $routes->post('users/(:segment)/reset-password', 'UserApiController::resetPassword/$1');

        $routes->get('settings',          'SettingsApiController::index');
        $routes->put('settings',          'SettingsApiController::save');

        // Profile / password
        $routes->put('auth/profile',      'AuthController::updateProfile');
        $routes->post('auth/change-password', 'AuthController::changePassword');

        $routes->get('teachers', 'TeachersController::index');
        #$routes->get('teachers', 'TeachersController::index', ['filter' => ['cors', 'jwtauth', 'tenant', 'permission:teachers.view'],]);
        #$routes->get('teachers/(:segment)', 'TeachersController::show/$1', ['filter' => ['cors', 'jwtauth', 'tenant', 'permission:teachers.view'],]);
        $routes->post('teachers', 'TeachersController::create', ['filter' => ['cors', 'jwtauth', 'tenant', 'permission:teachers.manage'],]);
        $routes->put('teachers/(:segment)', 'TeachersController::update/$1', ['filter' => ['cors', 'jwtauth', 'tenant', 'permission:teachers.manage'],]);
        $routes->delete('teachers/(:segment)', 'TeachersController::delete/$1', ['filter' => ['cors', 'jwtauth', 'tenant', 'permission:teachers.manage'],]);
    
    
    });
});