<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Toolbox.php';
require_once __DIR__ . '/includes/Parser.php';
require_once __DIR__ . '/includes/Encoder.php';
require_once __DIR__ . '/../w/maintenance/Maintenance.php';

use \ForceUTF8\Encoding;

class WikilegMainScript extends Maintenance {

	/**
	 * Define the script options
	 */
	public function __construct() {
		parent::__construct();
		$this->addArg( 'tipo', 'Tipo de norma', false );
		$this->addArg( 'numero', 'Número de la norma', false );
		$this->addOption( 'tipo', 'Tipo de norma', false, true, 't' );
		$this->addOption( 'numero', 'Número de la norma', false, true, 'n' );
		$this->addOption( 'desde', 'Número de la norma desde donde retomar', false, true );
		$this->addOption( 'orden', 'Orden descendente o ascendente', false, true, 'o' );
		$this->addOption( 'debug', 'Debug mode or not', false, false, 'd' );
	}

	/**
	 * Main script
	 */
	public function execute() {
		$tipo = $this->getOption( 'tipo', $this->getArg( 0 ) );
		$numero = $this->getOption( 'numero', $this->getArg( 1 ) );
		$desde = $this->getOption( 'desde' );
		$orden = $this->getOption( 'orden', 'asc' );
		$debug = $this->getOption( 'debug' );

		$limit = "";
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
		if ( $desde ) {
			$operador = $orden === 'asc' ? '>=' : '<=';
			if ( $tipo === 'Ley' ) {
				$conditions .= " AND numero_norma $operador $desde";
			} else {
				$conditions .= " AND YEAR(fecha_sancion) $operador $desde";
			}
		}

		$database = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );
		$statement1 = $database->prepare( "SELECT * FROM normativa WHERE $conditions ORDER BY tipo_norma DESC, fecha_sancion $orden, numero_norma $orden $limit" );
		$statement2 = $database->prepare( "SELECT n.tipo_norma, n.numero_norma, n.fecha_sancion FROM normativa n RIGHT JOIN modificatorias m ON n.id_norma = m.id_norma_modificada WHERE n.tipo_norma IN ( 'Ley', 'Decreto/Ley', 'Decreto' ) AND m.id_norma_modificatoria = ? ORDER BY n.tipo_norma DESC, n.fecha_sancion ASC, n.numero_norma ASC LIMIT ?;" );
		$statement3 = $database->prepare( "SELECT ley FROM promulgadas WHERE decreto_promulgacion = ? LIMIT 1" );
		$statement4 = $database->prepare( "SELECT firmante, firmante_bloque, firmante_distrito FROM firmantes WHERE ley = ? ORDER BY firmante_orden ASC" );

		$statement1->execute();
		$result1 = WikilegToolbox::get_result( $statement1 );
		while ( $data1 = array_shift( $result1 ) ) {

			// Asegurar que todo está en UTF8
			foreach ( $data1 as $key => $value ) {
				$key = Encoding::toUTF8( $key );
				$value = Encoding::toUTF8( $value );
				$data1[ $key ] = $value;
			}
			extract( $data1 );

			$tipo_norma = WikilegParser::normalizar_tipo_norma( $tipo_norma );
			$organismo_origen = WikilegParser::normalizar_organismo_origen( $organismo_origen );
			$titulo_sumario = WikilegParser::normalizar_titulo_sumario( $titulo_sumario );
			$titulo_resumido = WikilegParser::normalizar_titulo_resumido( $titulo_resumido );
			$anio_sancion = mb_substr( $fecha_sancion, 0, 4 );
			$observaciones = trim( $observaciones );

			$metadata = [
				'tipo' => $tipo_norma,
				'número' => $numero_norma,
				'tema-genérico' => $titulo_sumario,
				'tema-específico' => $titulo_resumido,
				'año-de-sanción' => $anio_sancion,
				'fecha-de-sanción' => $fecha_sancion,
				'organismo-de-origen' => $organismo_origen,
				'texto-original' => $texto_original,
				'texto-actualizado' => $texto_actualizado,
			];

			// Agregar las normas modificadas por esta norma
			if ( $modifica_a ) {
				$statement2->bind_param( 'ii', $id_norma, $modifica_a );
				$statement2->execute();
				$result2 = WikilegToolbox::get_result( $statement2 );
				$modificadas = [];
				while ( $data2 = array_shift( $result2 ) ) {
					$modificadas[] = WikilegParser::construir_titulo( $data2['tipo_norma'], $data2['numero_norma'], $data2['fecha_sancion'] );
				}
				$metadata['modifica'] = implode( ',', $modificadas );
			}

			if ( $tipo_norma === "Decreto" ) {
				$decreto = "$numero_norma/$anio_sancion";
				$statement3->bind_param( 'i', $decreto );
				$statement3->execute();
				$result3 = WikilegToolbox::get_result( $statement3 );
				$data3 = array_shift( $result3 );
				if ( $data3 ) {
					$metadata['promulga'] = WikilegParser::construir_titulo( 'Ley', $data3['ley'] );
				}
			}

			if ( $tipo_norma === "Ley" ) {
				$statement4->bind_param( 'i', $numero_norma );
				$statement4->execute();
				$result4 = WikilegToolbox::get_result( $statement4 );
				$firmantes = [];
				$firmantes_bloques = [];
				$firmantes_distritos = [];
				while ( $data4 = array_shift( $result4 ) ) {
					$firmantes[] = WikilegToolbox::normalize_name( $data4['firmante'] );
					foreach ( explode( '-', $data4['firmante_bloque'] ) as $firmante_bloque ) {
						$firmante_bloque = trim( $firmante_bloque );
						$firmante_bloque = strlen( $firmante_bloque ) > 5 ? WikilegToolbox::title_case( $firmante_bloque ) : $firmante_bloque;
						$firmantes_bloques[] = $firmante_bloque;
					}
					$firmantes_distritos[] = WikilegToolbox::title_case( $data4['firmante_distrito'] );
				}
				$metadata[ 'firmantes' ] = implode( ', ', array_unique( $firmantes ) );
				$metadata[ 'firmantes-bloques' ] = implode( ', ', array_unique( $firmantes_bloques ) );
				$metadata[ 'firmantes-distritos' ] = implode( ', ', array_unique( $firmantes_distritos ) );
			}

			/**
			 * Construir el título
			 */
			$titulo = WikilegParser::construir_titulo( $tipo_norma, $numero_norma, $fecha_sancion );

			/**
			 * Texto
			 */
			$texto = "";
			if ( $texto_original or $texto_actualizado ) {
		
				if ( file_exists( __DIR__ . "/textos/actualizados/" . str_replace( '/', ' ', $titulo ) ) ) {
					$texto = file_get_contents( __DIR__ . "/textos/actualizados/" . str_replace( '/', ' ', $titulo ) );
				} else {
					$texto = file_get_contents( __DIR__ . "/textos/originales/" . str_replace( '/', ' ', $titulo ) );
				}

				if ( $debug ) {
					$file = fopen( str_replace( '/', ' ', $titulo ), "wb" );
				    fwrite( $file, $texto );
					fclose( $file );
				}

				$texto = Encoding::toUTF8( $texto );
				$texto = WikilegParser::normalizar( $texto );
				$texto = WikilegParser::enlaces( $texto );
				$texto = WikilegParser::secciones( $texto );
				$texto = WikilegParser::citas( $texto );
				$texto = WikilegParser::listas( $texto );
				$texto = WikilegParser::notas( $texto );
				$texto = WikilegParser::imagenes( $texto, $texto_original );
				$texto = WikilegParser::limpiar( $texto );
			}

			/**
			 * Plantilla
			 */
			$plantilla = '{{Norma';
			foreach ( $metadata as $key => $value ) {
				$plantilla .= "\n|" . $key . ' = ' . $value;
			}
			$plantilla .= "\n}}\n\n";

			/**
			 * Intro
			 */
			$intro = WikilegParser::normalizar_texto_resumido( $texto_resumido );
			$intro = WikilegParser::normalizar( $intro );
			$intro = WikilegParser::enlaces( $intro );
			$intro = WikilegParser::notas( $intro );
			$intro = WikilegParser::limpiar( $intro );
			$intro = WikilegParser::comparar_con_primer_articulo( $intro, $texto );
			$intro .= "\n\n";
			if ( $observaciones ) {
				$observaciones = strip_tags( $observaciones );
				$observaciones = WikilegToolbox::sentence_case( $observaciones );
				$observaciones = WikilegParser::normalizar( $observaciones );
				$observaciones = WikilegParser::enlaces( $observaciones );
				$observaciones = WikilegParser::limpiar( $observaciones );
				$intro .= "{{Nota|$observaciones}}\n\n";
			}

			/**
			 * Finalmente, crear la página
			 */
			WikilegToolbox::create( $titulo, $plantilla . $intro . $texto );
			$this->output( $titulo . PHP_EOL );
		}
	}
}

$maintClass = WikilegMainScript::class;
require_once RUN_MAINTENANCE_IF_MAIN;