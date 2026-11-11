<?php

namespace Config;

//declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) 2021 CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use CodeIgniter\Router\RouteCollection;
$routes = Services::routes();

// @var RouteCollection $routes

$routes->get('/', 'Home::index');
$routes->group('students', ['filter' => 'permission:students.view'], function ($routes) {
    $routes->get('',         'StudentController::index');
    $routes->get('(:segment)', 'StudentController::show/$1');
});
$routes->post('students', 'StudentController::create', ['filter' => 'permission:students.create']);
$routes->put('students/(:segment)',    'StudentController::update/$1', ['filter' => 'permission:students.edit']);
$routes->delete('students/(:segment)', 'StudentController::delete/$1', ['filter' => 'permission:students.delete']);


// Courses
$routes->group('courses', ['filter' => 'permission:courses.view'], function ($routes) {
    $routes->get('', 'CourseController::index');
});
$routes->post('courses', 'CourseController::create', ['filter' => 'permission:courses.manage']);
$routes->put('courses/(:segment)', 'CourseController::update/$1', ['filter' => 'permission:courses.manage']);
$routes->delete('courses/(:segment)', 'CourseController::delete/$1', ['filter' => 'permission:courses.manage']);
$routes->post('courses/(:segment)/subjects', 'CourseController::attachSubjects/$1', ['filter' => 'permission:courses.manage']);

// Subjects
$routes->group('subjects', ['filter' => 'permission:subjects.view'], function ($routes) {
    $routes->get('', 'SubjectController::index');
});
$routes->post('subjects', 'SubjectController::create', ['filter' => 'permission:subjects.manage']);

// Batches
$routes->group('batches', ['filter' => 'permission:batches.view'], function ($routes) {
    $routes->get('', 'BatchController::index');
    $routes->get('(:segment)/students', 'BatchController::students/$1');
});
$routes->post('batches', 'BatchController::create', ['filter' => 'permission:batches.manage']);
$routes->post('batches/(:segment)/enroll', 'BatchController::enroll/$1', ['filter' => 'permission:batches.manage']);
$routes->get('sitemap.xml', 'Sitemap::index');


require APPPATH . 'Modules/Api/Config/Routes.php';
require APPPATH . 'Modules/Admin/Config/Routes.php';