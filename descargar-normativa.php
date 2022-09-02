<?php

/**
 * Descargar la última versión de la tabla de normativa y actualizar la tabla correspondiente en la base de datos
 */

require_once __DIR__ . '/includes/config.php';

exec( 'wget http://datos.jus.gob.ar/dataset/d9a963ea-8b1d-4ca3-9dd9-07a4773e8c23/resource/bf0ec116-ad4e-4572-a476-e57167a84403/download/base-infoleg-normativa-nacional.zip' );
exec( 'unzip base-infoleg-normativa-nacional.zip' );
unlink( 'base-infoleg-normativa-nacional.zip' );

$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
$database->options( MYSQLI_OPT_LOCAL_INFILE, true );

$database->query( "DROP TABLE IF EXISTS normativa" );

$database->query( "CREATE TABLE normativa (
id_norma INT NOT NULL PRIMARY KEY,
tipo_norma VARCHAR(99),
numero_norma INT,
clase_norma VARCHAR(99),
organismo_origen VARCHAR(99),
fecha_sancion VARCHAR(10),
numero_boletin INT,
fecha_boletin VARCHAR(10),
pagina_boletin INT,
titulo_resumido VARCHAR(999),
titulo_sumario VARCHAR(999),
texto_resumido TEXT,
observaciones TEXT,
texto_original VARCHAR(999),
texto_actualizado VARCHAR(999),
modificada_por INT,
modifica_a INT
)" );

$query = <<<EOM
LOAD DATA LOCAL INFILE 'base-infoleg-normativa-nacional.csv' INTO TABLE normativa
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(id_norma,tipo_norma,numero_norma,clase_norma,organismo_origen,fecha_sancion,numero_boletin,fecha_boletin,pagina_boletin,titulo_resumido,titulo_sumario,texto_resumido,observaciones,texto_original,texto_actualizado,modificada_por,modifica_a)
EOM;

$database->query( $query );

unlink( 'base-infoleg-normativa-nacional.csv' );