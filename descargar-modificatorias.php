<?php

/**
 * Descargar la última versión de la tabla de normativas modificatorias y actualizar la tabla correspondiente en la base de datos
 */

require_once __DIR__ . '/includes/config.php';

exec( 'wget http://datos.jus.gob.ar/dataset/d9a963ea-8b1d-4ca3-9dd9-07a4773e8c23/resource/dea3c247-5a5d-408f-a224-39ae0f8eb371/download/base-complementaria-infoleg-normas-modificatorias.zip' );
exec( 'unzip base-complementaria-infoleg-normas-modificatorias.zip' );
unlink( 'base-complementaria-infoleg-normas-modificatorias.zip' );

$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
$database->options( MYSQLI_OPT_LOCAL_INFILE, true );

$database->query( "DROP TABLE IF EXISTS modificatorias" );

$database->query( "CREATE TABLE modificatorias (
id_norma_modificatoria INT,
id_norma_modificada INT,
tipo_norma VARCHAR(99),
nro_norma INT,
clase_norma VARCHAR(99),
organismo_origen VARCHAR(99),
fecha_boletin VARCHAR(10),
titulo_sumario VARCHAR(99),
titulo_resumido VARCHAR(99)
)" );

$query = <<<EOM
LOAD DATA LOCAL INFILE 'base-complementaria-infoleg-normas-modificatorias.csv' INTO TABLE modificatorias
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(id_norma_modificatoria,id_norma_modificada,tipo_norma,nro_norma,clase_norma,organismo_origen,fecha_boletin,titulo_sumario,titulo_resumido)
EOM;

$database->query( $query );

unlink( 'base-complementaria-infoleg-normas-modificatorias.csv' );