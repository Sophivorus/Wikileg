<?php

/**
 * Descargar la última versión de la tabla de normativas modificadas y actualizar la tabla correspondiente en la base de datos
 */

require_once __DIR__ . '/includes/config.php';

exec( 'wget http://datos.jus.gob.ar/dataset/d9a963ea-8b1d-4ca3-9dd9-07a4773e8c23/resource/0c4fdafe-f4e8-4ac2-bc2e-acf50c27066d/download/base-complementaria-infoleg-normas-modificadas.zip' );
exec( 'unzip base-complementaria-infoleg-normas-modificadas.zip' );
unlink( 'base-complementaria-infoleg-normas-modificadas.zip' );

$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
$database->options( MYSQLI_OPT_LOCAL_INFILE, true );

$database->query( "DROP TABLE IF EXISTS modificadas" );

$database->query( "CREATE TABLE modificadas (
id_norma_modificada INT,
id_norma_modificatoria INT,
tipo_norma VARCHAR(99),
nro_norma INT,
clase_norma VARCHAR(99),
organismo_origen VARCHAR(99),
fecha_boletin VARCHAR(10),
titulo_sumario VARCHAR(99),
titulo_resumido VARCHAR(99)
)" );

$query = <<<EOM
LOAD DATA LOCAL INFILE 'base-complementaria-infoleg-normas-modificadas.csv' INTO TABLE modificadas
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(id_norma_modificada,id_norma_modificatoria,tipo_norma,nro_norma,clase_norma,organismo_origen,fecha_boletin,titulo_sumario,titulo_resumido)
EOM;

$database->query( $query );

unlink( 'base-complementaria-infoleg-normas-modificadas.csv' );