<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-02-26 15:58:08 --> 404 Page Not Found: api/User/hijos
ERROR - 2026-02-26 15:58:08 --> 404 Page Not Found: api/User/hijos
ERROR - 2026-02-26 19:42:32 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:42:32 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:42:39 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` = 2
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:44:20 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:44:20 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:45:14 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
ERROR - 2026-02-26 19:45:14 --> Query error: Unknown column 'rd.hijo_id' in 'field list' - Invalid query: SELECT `rd`.`id`, `rd`.`fecha`, `rd`.`avance_texto`, `rd`.`tarea_descripcion`, `rd`.`completado`, `rd`.`no_hubo_tarea`, `rd`.`profesor_falto`, `rd`.`hijo_id`, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`, `u`.`nombre` as `hijo_nombre`
FROM `registros_diarios` `rd`
JOIN `materias` `m` ON `m`.`id` = `rd`.`materia_id`
JOIN `usuarios` `u` ON `u`.`id` = `rd`.`hijo_id`
WHERE `rd`.`hijo_id` IN('2', '3', '4')
AND `rd`.`profesor_falto` = 1
AND `rd`.`fecha` >= '2026-02-01'
AND `rd`.`fecha` <= '2026-02-28'
ORDER BY `rd`.`fecha` DESC
