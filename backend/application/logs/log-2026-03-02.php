<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2026-03-02 20:14:14 --> Severity: Warning --> mysqli::real_connect(): (HY000/2002): No se puede establecer una conexión ya que el equipo de destino denegó expresamente dicha conexión C:\laragon\www\academia\backend\system\database\drivers\mysqli\mysqli_driver.php 211
ERROR - 2026-03-02 20:14:14 --> Unable to connect to the database
ERROR - 2026-03-02 20:14:32 --> Severity: Warning --> mysqli::real_connect(): (HY000/2002): No se puede establecer una conexión ya que el equipo de destino denegó expresamente dicha conexión C:\laragon\www\academia\backend\system\database\drivers\mysqli\mysqli_driver.php 211
ERROR - 2026-03-02 20:14:32 --> Unable to connect to the database
ERROR - 2026-03-02 20:15:13 --> Query error: Table 'academia.calendario_materias' doesn't exist - Invalid query: SELECT `cm`.*, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`
FROM `calendario_materias` `cm`
JOIN `materias` `m` ON `m`.`id` = `cm`.`materia_id`
WHERE `cm`.`usuario_id` = '2'
AND `cm`.`dia_semana` = '1'
ORDER BY `cm`.`hora_inicio` ASC
ERROR - 2026-03-02 20:15:13 --> Query error: Table 'academia.calendario_materias' doesn't exist - Invalid query: SELECT `cm`.*, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`
FROM `calendario_materias` `cm`
JOIN `materias` `m` ON `m`.`id` = `cm`.`materia_id`
WHERE `cm`.`usuario_id` = '2'
AND `cm`.`dia_semana` = '1'
ORDER BY `cm`.`hora_inicio` ASC
ERROR - 2026-03-02 20:15:26 --> Query error: Table 'academia.calendario_materias' doesn't exist - Invalid query: SELECT `cm`.*, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`
FROM `calendario_materias` `cm`
JOIN `materias` `m` ON `m`.`id` = `cm`.`materia_id`
WHERE `cm`.`usuario_id` = '2'
AND `cm`.`dia_semana` = '1'
ORDER BY `cm`.`hora_inicio` ASC
ERROR - 2026-03-02 20:15:49 --> Query error: Table 'academia.calendario_materias' doesn't exist - Invalid query: SELECT `cm`.*, `m`.`nombre` as `materia_nombre`, `m`.`color_hex`
FROM `calendario_materias` `cm`
JOIN `materias` `m` ON `m`.`id` = `cm`.`materia_id`
WHERE `cm`.`usuario_id` = '2'
AND `cm`.`dia_semana` = '1'
ORDER BY `cm`.`hora_inicio` ASC
