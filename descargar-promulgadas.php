<?php

/**
 * Descargar la última versión de la tabla de normativas promulgadas y actualizar la tabla correspondiente en la base de datos
 */

require_once __DIR__ . '/includes/config.php';

exec( 'wget https://datos.hcdn.gob.ar/dataset/337ef578-7faf-41fb-92fa-00c28f64a4d6/resource/cbf78f72-1098-4e84-99e7-695d6d65a02a/download/leyes-promulgadas1.5.csv' );

$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
$database->options( MYSQLI_OPT_LOCAL_INFILE, true );

$database->query( "DROP TABLE IF EXISTS promulgadas" );

$database->query( "CREATE TABLE promulgadas (
ley VARCHAR(5),
asunto VARCHAR(999),
fecha_sancion VARCHAR(10),
fecha_promulgacion VARCHAR(10),
decreto_promulgacion VARCHAR(10),
decreto_promulgacion_fecha VARCHAR(10),
tipo_promulgacion VARCHAR(99),
promulgacion_tacita VARCHAR(10),
veto VARCHAR(10),
decreto_veto_nro VARCHAR(10),
decreto_veto_anio VARCHAR(10),
decreto_veto_nroNORA VARCHAR(10),
decreto_veto_anioNORA VARCHAR(10),
decreto_vetoNORA VARCHAR(10),
decreto_veto_nroNORA2 VARCHAR(10),
decreto_veto_anioNORA2 VARCHAR(10),
decreto_vetoNORA2 VARCHAR(10),
decreto_veto VARCHAR(10)
)" );

$query = <<<EOM
LOAD DATA LOCAL INFILE 'leyes-promulgadas1.5.csv' INTO TABLE promulgadas
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(ley, asunto, fecha_sancion, fecha_promulgacion, decreto_promulgacion, decreto_promulgacion_fecha, tipo_promulgacion, promulgacion_tacita, veto, decreto_veto_nro, decreto_veto_anio, decreto_veto_nroNORA, decreto_veto_anioNORA, decreto_vetoNORA, decreto_veto_nroNORA2, decreto_veto_anioNORA2, decreto_vetoNORA2, decreto_veto)
EOM;

$database->query( $query );

unlink( 'leyes-promulgadas1.5.csv' );