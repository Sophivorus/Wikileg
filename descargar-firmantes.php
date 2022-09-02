<?php

/**
 * Descargar la última versión de la tabla de normativas promulgadas y actualizar la tabla correspondiente en la base de datos
 */

require_once __DIR__ . '/includes/config.php';

exec( 'wget https://datos.hcdn.gob.ar/dataset/08b650e9-b8e5-4b68-a1b2-ecc9b45a63d4/resource/a88b42c3-d375-4072-8542-92b11db1d711/download/firmantes-leyes-sancionadas1.8.csv' );

$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
$database->options( MYSQLI_OPT_LOCAL_INFILE, true );

$database->query( "DROP TABLE IF EXISTS firmantes" );

$database->query( "CREATE TABLE firmantes (
ley VARCHAR(5),
camara_iniciadora VARCHAR(99),
firmante_orden VARCHAR(1),
firmante VARCHAR(99),
firmante_distrito VARCHAR(99),
firmante_bloque VARCHAR(99)
)" );

$query = <<<EOM
LOAD DATA LOCAL INFILE 'firmantes-leyes-sancionadas1.8.csv' INTO TABLE firmantes
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(ley, camara_iniciadora, firmante_orden, firmante, firmante_distrito, firmante_bloque)
EOM;

$database->query( $query );

unlink( 'firmantes-leyes-sancionadas1.8.csv' );