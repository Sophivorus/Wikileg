<?php

/**
 * Este script descarga los textos de la legislación desde Infoleg
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Toolbox.php';
require_once __DIR__ . '/includes/Parser.php';
require_once __DIR__ . '/../w/maintenance/Maintenance.php';

class WikilegTextos extends Maintenance {

	/**
	 * Define the script options
	 */
	public function __construct() {
		parent::__construct();
		$this->addArg( 'tipo', 'Tipo de norma', false );
		$this->addArg( 'numero', 'Número de la norma', false );
		$this->addOption( 'tipo', 'Tipo de norma', false, true, 't' );
		$this->addOption( 'numero', 'Número de la norma', false, true, 'n' );
		$this->addOption( 'forzar', 'Descargar todos los textos, aunque ya hayan sido descargados', false, false, 'f' );
	}

	/**
	 * Main script
	 */
	public function execute() {
		$tipo = $this->getOption( 'tipo', $this->getArg( 0 ) );
		$numero = $this->getOption( 'numero', $this->getArg( 1 ) );
		$forzar = $this->getOption( 'forzar' );

		$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );

		$fields = 'tipo_norma, numero_norma, fecha_sancion, texto_original, texto_actualizado';

		$conditions = "( texto_original != '' OR texto_actualizado != '' )";
		if ( $tipo ) {
			$conditions .= " AND tipo_norma = '" . str_replace( '-', '/', $tipo ) . "'"; // Los "Decreto-Ley" son "Decreto/Ley" en la base
		}
		if ( $numero ) {
			$partes = explode( '/', $numero );
			$n = $partes[0];
			$conditions .= " AND numero_norma = $n";
			if ( array_key_exists( 1, $partes ) ) {
				$a = $partes[1];
				$conditions .= " AND fecha_sancion LIKE '$a%'";
			}
			$limit = "LIMIT 1";
		}
		$query = "SELECT $fields FROM normativa WHERE $conditions ORDER BY tipo_norma DESC, fecha_sancion ASC, numero_norma ASC";
		$statement = $database->prepare( $query );
		$statement->execute();
		$result = WikilegToolbox::get_result( $statement );
		while ( $data = array_shift( $result ) ) {
			extract( $data );

			$titulo = WikilegParser::construir_titulo( $tipo_norma, $numero_norma, $fecha_sancion );

			$path_texto_original = __DIR__ . "/textos/originales/" . str_replace( '/', ' ', $titulo );
			$path_texto_actualizado = __DIR__ . "/textos/actualizados/" . str_replace( '/', ' ', $titulo );

			if ( ! $forzar and ( file_exists( $path_texto_original ) or file_exists( $path_texto_actualizado ) ) ) {
				continue;
			}

			// Escupir el título
			$this->output( $titulo . PHP_EOL );

			if ( $texto_original and ( $forzar or ! file_exists( $path_texto_original ) ) ) {
				exec( "lynx -dump -width 99999999 -display_charset UTF-8 -nolist $texto_original > '$path_texto_original'" );
			}

			if ( $texto_actualizado and ( $forzar or ! file_exists( $path_texto_actualizado ) ) ) {
				exec( "lynx -dump -width 99999999 -display_charset UTF-8 -nolist $texto_actualizado > '$path_texto_actualizado'" );
			}
		}
	}
}

$maintClass = WikilegTextos::class;
require_once RUN_MAINTENANCE_IF_MAIN;