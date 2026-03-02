<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Rutas de la API REST
|--------------------------------------------------------------------------
*/
$route['default_controller'] = 'welcome';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

// --- Auth ---
$route['api/auth/login']['POST']  = 'api/Auth/login';
$route['api/auth/logout']['POST'] = 'api/Auth/logout';
$route['api/auth/me']['GET']      = 'api/Auth/me';
$route['api/auth/update_photo']['POST'] = 'api/Auth/update_photo';

// --- Horarios ---
$route['api/schedule']['GET']            = 'api/Schedule/index';
$route['api/schedule']['POST']           = 'api/Schedule/create';
$route['api/schedule/hijos']['GET']      = 'api/Schedule/hijos';
$route['api/schedule/materias']['GET']   = 'api/Schedule/materias';
$route['api/schedule/materias']['POST']  = 'api/Schedule/create_materia';
$route['api/schedule/materias-hoy']['GET'] = 'api/Schedule/materias_hoy';
$route['api/schedule/materias/(:num)']['PUT']     = 'api/Schedule/update_materia/$1';
$route['api/schedule/materias/(:num)']['DELETE']  = 'api/Schedule/destroy_materia/$1';
$route['api/schedule/(:num)']['PUT']     = 'api/Schedule/update/$1';
$route['api/schedule/(:num)']['DELETE']  = 'api/Schedule/destroy/$1';

// --- Usuarios (Padre gestionando Hijos) ---
$route['api/users']['GET']       = 'api/Users/index';
$route['api/users']['POST']      = 'api/Users/create';
$route['api/user/hijos']['GET']  = 'api/Users/hijos';
$route['api/users/(:num)']['PUT']    = 'api/Users/update/$1';
$route['api/users/(:num)']['POST']   = 'api/Users/update/$1'; // For multipart/form-data updates
$route['api/users/(:num)']['DELETE'] = 'api/Users/destroy/$1';

// --- Registro Diario ---
$route['api/dailylog']['GET']           = 'api/DailyLog/index';
$route['api/dailylog']['POST']          = 'api/DailyLog/create';
$route['api/dailylog/retrasos']['GET']  = 'api/DailyLog/retrasos';
$route['api/dailylog/(:num)']['GET']    = 'api/DailyLog/show/$1';
$route['api/dailylog/(:num)']['PUT']    = 'api/DailyLog/update/$1';
$route['api/dailylog/(:num)/tarea']['POST'] = 'api/DailyLog/upload_homework/$1';

// --- Reportes ---
$route['api/report/weekly']['GET']      = 'api/Report/weekly';
$route['api/report/progreso']['GET']    = 'api/Report/progreso_detallado';
$route['api/report/inasistencias']['GET'] = 'api/Report/inasistencias';

// --- Evidencias ---
$route['api/evidence']['GET']           = 'api/Evidence/index';

// --- Eventos de calendario ---
$route['api/events/history']['GET']     = 'api/Events/history';
$route['api/events/padre']['GET']       = 'api/Events/padre';
$route['api/events/estado/(:num)']['PUT'] = 'api/Events/cambiar_estado/$1';
$route['api/events']['GET']             = 'api/Events/index';
$route['api/events']['POST']            = 'api/Events/create';
$route['api/events/(:num)']['DELETE']   = 'api/Events/destroy/$1';
