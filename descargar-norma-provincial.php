<?php

$contents = file_get_contents( 'http://www.saij.gob.ar/LPB0015177' );
preg_match( "/var uuidUrlFriendly = '([a-z0-9-]+)'/", $contents, $matches );
$id = $matches[1];
$contents = file_get_contents( "http://www.saij.gob.ar/view-document?guid=$id" );
$json = json_decode( $contents, true );
$data = json_decode( $json['data'], true );
$document = $data['document'];
$metadata = $document['metadata'];
$content = $document['content'];
var_dump( $content );