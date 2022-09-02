<?php

class WikilegParser {

	/**
	 * Construir el título de la norma
	 */
	static function construir_titulo( $tipo_norma, $numero_norma, $fecha_sancion = null ) {
		$tipo_norma = self::normalizar_tipo_norma( $tipo_norma );
		$titulo = "$tipo_norma $numero_norma";
		if ( $tipo_norma !== 'Ley' ) {
			$anio_sancion = mb_substr( $fecha_sancion, 0, 4 ); // Año de sanción
			$titulo .= "/$anio_sancion";
		}
		return $titulo;
	}

	/**
	 * Normalizar el tipo de norma
	 */
	static function normalizar_tipo_norma( $tipo_norma ) {
		$tipo_norma = preg_replace( '#\/#', '-', $tipo_norma ); // Para las normas del tipo Decreto/Ley
		return $tipo_norma;
	}

	/**
	 * Normalizar el título sumario
	 */
	static function normalizar_titulo_sumario( $titulo_sumario ) {
		$titulo_sumario = WikilegToolbox::title_case( $titulo_sumario );
		$titulo_sumario = WikilegToolbox::tildar( $titulo_sumario );
		$titulo_sumario = self::normalizar( $titulo_sumario );
		$titulo_sumario = preg_replace( '#[\.\/\[\]\{\}\|\#\<\>\'\"\:]#', '', $titulo_sumario ); // Quitar caracteres problemáticos
		$titulo_sumario = trim( $titulo_sumario, ' -' );
		return $titulo_sumario;
	}

	/**
	 * Normalizar el título resumido
	 */
	static function normalizar_titulo_resumido( $titulo_resumido ) {
		$titulo_resumido = WikilegToolbox::title_case( $titulo_resumido );
		$titulo_resumido = WikilegToolbox::tildar( $titulo_resumido );
		$titulo_resumido = self::normalizar( $titulo_resumido );
		$titulo_resumido = preg_replace( '#[\.\/\[\]\{\}\|\#\<\>\'\"\:]#', '', $titulo_resumido ); // Quitar caracteres problemáticos
		$titulo_resumido = trim( $titulo_resumido, ' -' );
		return $titulo_resumido;
	}

	/**
	 * Comparar el texto resumido con el primer artículo
	 * Muchas veces coinciden excepto por los errores de ortografía del texto resumido
	 * En tales casos, reemplazar el texto resumido por el primer artículo
	 * Ver por ejemplo Decreto 46/2017
	 */
	static function comparar_con_primer_articulo( $intro, $texto ) {
		preg_match( "/====== Artículo 1 ======\n+([^=]+)/", $texto, $matches );
		if ( array_key_exists( 1, $matches ) ) {
			$articulo = $matches[1];
			$articulo = trim( $articulo );
			$articulo_sin_acentos = WikilegToolbox::replace( $articulo, [
				"á" => "a",
				"é" => "e",
				"í" => "i",
				"ó" => "o",
				"ú" => "u",
			], 'i' );
			if ( strcasecmp( $articulo_sin_acentos, $intro ) === 0 ) {
				$intro = $articulo;
			}
		}
		return $intro;
	}

	/**
	 * Normalizar el texto resumido
	 */
	static function normalizar_texto_resumido( $texto_resumido ) {
		$texto_resumido = WikilegToolbox::sentence_case( $texto_resumido );
		$texto_resumido = WikilegToolbox::tildar( $texto_resumido );
		$texto_resumido = rtrim( $texto_resumido, '.-\n ' ) . '.';
		return $texto_resumido;
	}

	/**
	 * Normalizar el organismo de origen
	 */
	static function normalizar_organismo_origen( $organismo_origen ) {
		$organismo_origen = WikilegToolbox::replace( $organismo_origen, [
			' \(.+\)' => '', // Quitar siglas entre paréntesis, por ejemplo "(P.E.N.)"
		]);
		$organismo_origen = WikilegToolbox::title_case( $organismo_origen );
		return $organismo_origen;
	}

	/**
	 * Normalizar el texto para que los demás regexes funcionen correctamente
	 */
	static function normalizar( $text ) {

		$text = WikilegToolbox::replace( $text, [

			// Puntuación
			"\n +"		=> "\n",
			"\n\*"		=> "\n", // Ley 11681
			"\t+"		=> " ",
			"\n"		=> "\n\n",
			" :"		=> ":",
			"[º°ª]+"	=> "",
			"[“”]"		=> '"',
			"–"			=> "—", // n-dash a m-dash
//			" ,"		=> ",", // Muy arriesgado
//			"\\\/"		=> "V", // Ley 25399, pero no funciona

			// Separadores de miles
			"(\d{1,3})[ .](\d{3})[ .](\d{3})[ .](\d{3})[ .](\d{3})[ .](\d{3})" => "$1$2$3$4$5$6",
			"(\d{1,3})[ .](\d{3})[ .](\d{3})[ .](\d{3})[ .](\d{3})" => "$1$2$3$4$5",
			"(\d{1,3})[ .](\d{3})[ .](\d{3})[ .](\d{3})" => "$1$2$3$4",
			"(\d{1,3})[ .](\d{3})[ .](\d{3})" => "$1$2$3",
			"(\d{1,3})[ .](\d{3})" => "$1$2",

			// Abreviaciones
			"inc\."		=> "inciso ",
			"[Nn]ro\."	=> "número ",
			"arts\."	=> "artículos ",
			"art\."		=> "artículo ",
			" art "		=> " artículo ",
			"Art\."		=> "Artículo ",
			"\nArt "	=> "Artículo ",
			"[Dd]ec\."	=> "Decreto ",
			"[Dd]to\-"	=> "Decreto ",

			// Mayúsculas
			'decreto-ley'	=> 'Decreto-Ley',
			'decreto'		=> 'Decreto',
			'DECRETO'		=> 'Decreto',

			// Espacios múltiples
			"  +"		=> " ",
		]);

		// Abreviaciones case-insensitive
		$text = WikilegToolbox::replace( $text, [
			" N\.? (\d+)"							=> " $1",
			" n.m\.? (\d+)"							=> " $1",
			" n.meros? (\d+)"						=> " $1",
			"T\. ?O\."								=> "T. O.",
			"s\/n"									=> "S/N",
			"B\. ?O\. ?\d+\/\d+\/\d+( Suplemento)?"	=> "", // Las referencias al boletín son inútiles, creo
			"D\. ?N\. ?I\. ? (\d+)"					=> "DNI $1", // DNIs
			"M\. ?I\. ? (\d+)"						=> "MI $1", // MIs - Decreto 22/2019
		], 'i' );

		$n = "(\d+)";
		$a = "(\d+)";
		$barra = " ?\/ ?";
		$fecha = "\d+\S? de \w+ de (\d+)";

		$articulo = "art.culo (\d+|S\/N)( bis| ter | qu.ter)?";

		$inciso = ",? inciso ([\w]+)\)?,?";
		$apartado = ",? apartado ([\w]+)\)?,?";

		$titulo = "t.tulo ([\w]+)\)?,?";
		$capitulo = "cap.tulo ([\w]+)\)?,?";

		$del = ",? del? ";
		$de_la = ",? de la ";
		$de_fecha = ",? de fecha ";

		$ley = "(?:Ley|L\.) "; // L. en Ley 11242
		$leyes = "Leyes ?";
		$ley_provincial = "Ley Provincial ?";

		$decreto_ley = "Decreto[ \/-]Ley ?";
		$decreto = "Decreto ?";

		$resolucion = "Resoluci.n ?";

		$disposicion = "Disposici.n ?";

		$decision_administrativa = "Decisi.n Administrativa ?";

		$nacion = "Naci.n";
		$argentina = "Argentina";

		$codigo_de_comercio = "C.digo de Comercio";

		$codigo_procesal_civil_y_comercial = "C.digo Procesal Civil y Comercial";
		$codigo_civil_y_comercial = "C.digo Civil y Comercial";
		$codigo_civil = "C.digo Civil";

		$codigo_penal = "C.digo Penal";

		$constitucion_nacional = "Constituci.n Nacional";

/* Esta funcionalidad tuvo que ser sacrificada debido a la dificultad de distinguir casos como "Ley de Julio de 1983"
		$alias = "([a-záéíóúüñÁÉÍÓÚÑÜ ]{1,90}?)";
		$ley_de_alias_n = "Ley de $alias\,? N\.? ?";
		$ley_de_alias = "Ley de $alias\,?";
		$ley_x = "Ley $alias";
		// Capturar aliases y crear redirecciones
		$text = preg_replace_callback([
			"/$ley_de_alias_n $n/",
			"/$ley_de_alias $n/",
		], function( $matches ) {
			$alias = $matches[1];
			$alias = "Ley de $alias";
			$alias = WikilegToolbox::title_case( $alias );
			$titulo = $matches[2];
			if ( array_key_exists( 3, $matches ) ) {
				$titulo .= $matches[3];
			}
			$titulo = "Ley $titulo";
			WikilegToolbox::create( $alias, "#REDIRECCIÓN [[$titulo]]" );
		}, $text );
*/

		$text = WikilegToolbox::replace( $text, [

			// Decretos leyes
			$articulo . $del . $decreto_ley . $n . $de_fecha . $fecha			=> "artículo $1$2 del Decreto-Ley $3/$4",
			$articulo . $del . $decreto_ley . $n . $del . $fecha				=> "artículo $1$2 del Decreto-Ley $3/$4",
			$articulo . $del . $decreto_ley . $n . $del . $a					=> "artículo $1$2 del Decreto-Ley $3/$4",
			$articulo . $del . $decreto_ley . $n . $barra . $a					=> "artículo $1$2 del Decreto-Ley $3/$4",
			$articulo . ',? ' . $decreto_ley . $n . $barra . $a					=> "artículo $1$2 del Decreto-Ley $3/$4",
			$decreto_ley . $n . $de_fecha . $fecha								=> "Decreto-Ley $1/$2",
			$decreto_ley . $n . $del . $fecha									=> "Decreto-Ley $1/$2",
			$decreto_ley . $n . $del . $a										=> "Decreto-Ley $1/$2",
			$decreto_ley . $n . $barra . $a										=> "Decreto-Ley $1/$2",
			$decreto_ley . $n . ' ' . $a										=> "Decreto-Ley $1/$2",

			// Leyes
			$articulo . $inciso . $apartado . $de_la . $ley . $n				=> "apartado $4 del inciso $3 del artículo $1$2 de la Ley $5",
			$articulo . $inciso . $de_la . $ley . $n							=> "inciso $3 del artículo $1$2 de la Ley $4",
			$articulo . $del . $titulo . $de_la . $ley . $n						=> "artículo $1$2 de la Ley $4",
			$articulo . $del . $capitulo . $del . $titulo . $de_la . $ley . $n	=> "artículo $1$2 de la Ley $5",
			$articulo . $de_la . $ley_provincial . $n							=> "artículo $1$2 de la Ley Provincial $3",
			$articulo . $de_la . $ley . $n										=> "artículo $1$2 de la Ley $3",
			$articulo . ',? ' . $ley . $n										=> "artículo $1$2 de la Ley $3",
			$ley . $n . ',? ' . $articulo										=> "artículo $3$4 de la Ley $1",
			$ley_provincial . $n												=> "Ley Provincial $1",
			$ley . $n															=> "Ley $1",

			// Decretos
			$articulo . $del . $decreto . $n . $de_fecha . $fecha				=> "artículo $1$2 del Decreto $3/$4",
			$articulo . $del . $decreto . $n . $del . $fecha					=> "artículo $1$2 del Decreto $3/$4",
			$articulo . $del . $decreto . $n . $del . $a						=> "artículo $1$2 del Decreto $3/$4",
			$articulo . $del . $decreto . $n . $barra . $a						=> "artículo $1$2 del Decreto $3/$4",
			$decreto . $n . $de_fecha . $fecha									=> "Decreto $1/$2",
			$decreto . $n . $del . $fecha										=> "Decreto $1/$2",
			$decreto . $n . $del . $a											=> "Decreto $1/$2",
			$decreto . $n . $barra . $a											=> "Decreto $1/$2",
/*
			// Resoluciones
			$resolucion . $n . $de_fecha . $fecha								=> "Resolución $1/$2",
			$resolucion . $n . $del . $fecha									=> "Resolución $1/$2",
			$resolucion . $n . $del . $a										=> "Resolución $1/$2",
			$resolucion . $n . $barra . $a										=> "Resolución $1/$2",

			// Disposiciones
			$disposicion . $n . $de_fecha . $fecha								=> "Disposición $1/$2",
			$disposicion . $n . $del . $fecha									=> "Disposición $1/$2",
			$disposicion . $n . $del . $a										=> "Disposición $1/$2",
			$disposicion . $n . $barra . $a										=> "Disposición $1/$2",

			// Decisiones administrativas
			$decision_administrativa . $n . $de_fecha . $fecha					=> "Decisión Administrativa $1/$2",
			$decision_administrativa . $n . $del . $fecha						=> "Decisión Administrativa $1/$2",
			$decision_administrativa . $n . $del . $a							=> "Decisión Administrativa $1/$2",
			$decision_administrativa . $n . $barra . $a							=> "Decisión Administrativa $1/$2",
*/
			// Código de Comercio
			$codigo_de_comercio . $de_la . $nacion . $argentina					=> "Código de Comercio",
			$codigo_de_comercio . $de_la . $nacion								=> "Código de Comercio",
			$codigo_de_comercio													=> "Código de Comercio",
			$articulo . $del . $codigo_de_comercio								=> "artículo $1$2 del Código de Comercio",

			// Código Civil
			$codigo_procesal_civil_y_comercial . $de_la . $nacion . $argentina	=> "Código Civil",
			$codigo_procesal_civil_y_comercial . $de_la . $nacion				=> "Código Civil",
			$codigo_procesal_civil_y_comercial									=> "Código Civil",
			$codigo_civil_y_comercial . $de_la . $nacion . $argentina			=> "Código Civil",
			$codigo_civil_y_comercial . $de_la . $nacion						=> "Código Civil",
			$codigo_civil . $de_la . $nacion . $argentina						=> "Código Civil",
			$codigo_civil . $de_la . $nacion									=> "Código Civil",
			$codigo_civil														=> "Código Civil",
			$articulo . $del . $codigo_civil									=> "artículo $1$2 del Código Civil",

			// Código Penal
			$articulo . $del . $codigo_penal									=> "artículo $1$2 del Código Penal",
			$codigo_penal														=> "Código Penal",

			// Constitución Nacional
			$articulo . $inciso . $de_la . $constitucion_nacional				=> "inciso $3 del artículo $1$2 de la Constitución Nacional",
			$articulo . $de_la . $constitucion_nacional							=> "artículo $1$2 de la Constitución Nacional",
			$constitucion_nacional												=> "Constitución Nacional",

			// Organismos
			" S\.? ?A\.?"														=> " S.A.",
			" S\.? ?E\.?"														=> " S.E.",
			" Sociedad An[óo]nima"												=> " S.A.",

			"Administraci[óo]?n de Infraestructuras Ferroviarias S.E."			=> "Administración de Infraestructuras Ferroviarias S.E.",
			"Administraci[óo]?n de Parques Nacionales"							=> "Administración de Parques Nacionales",
			"Administraci[óo]?n Federal de Ingresos P[úu]?blicos"				=> "Administración Federal de Ingresos Públicos",
			"Administraci[óo]?n General de Museos y Archivos Presidenciales"	=> "Administración General de Museos y Archivos Presidenciales",
			"Administraci[óo]?n General de Puertos S.E."						=> "Administración General de Puertos S.E.",
			"Administraci[óo]?n Nacional de Aviaci[óo]?n Civil"					=> "Administración Nacional de Aviación Civil",
			"Administraci[óo]?n Nacional de B[ou]?sques"						=> "Administración Nacional de Bosques",
			"Administraci[óo]?n Nacional de la Seguridad Social"				=> "Administración Nacional de la Seguridad Social",
			"Administraci[óo]?n Nacional de Laboratorios e Institutos de Salud Dr\.? Carlos Malbr.n" => "Administración Nacional de Laboratorios e Institutos de Salud Dr. Carlos Malbrán",
			"Administraci[óo]?n Nacional de Medicamentos, Alimentos y Tecnolog.a M.dica" => "Administración Nacional de Medicamentos, Alimentos y Tecnología Médica",
			"Aerol[íi]?neas Argentinas S.A."									=> "Aerolíneas Argentinas",
			"Aerol[íi]?neas Argentinas S.A."									=> "Aerolíneas Argentinas",
			"Agencia de Acceso a la Informaci[óo]?n P[úu]?blica"				=> "Agencia de Acceso a la Información Pública",
			"Agencia de Administraci[óo]?n de Bienes del Estado"				=> "Agencia de Administración de Bienes del Estado",
			"Agencia de Deporte Nacional"										=> "Agencia de Deporte Nacional",
			"Agencia de Planificaci[óo]?n"										=> "Agencia de Planificación",
			"Agencia Nacional de Discapacidad"									=> "Agencia Nacional de Discapacidad",
			"Agencia Nacional de Laboratorios P[úu]?blicos"						=> "Agencia Nacional de Laboratorios Públicos",
			"Agencia Nacional de Materiales Controlados"						=> "Agencia Nacional de Materiales Controlados",
			"Agencia Nacional de Promoci[óo]?n Cient[íi]?fica y Tecnol[óo]?gica" => "Agencia Nacional de Promoción Científica y Tecnológica",
			"Agencia Nacional de Seguridad Vial"								=> "Agencia Nacional de Seguridad Vial",
			"Agua y Saneamientos Argentinos S.A."								=> "Agua y Saneamientos Argentinos S.A.",
			"Archivo Nacional de la Memoria"									=> "Archivo Nacional de la Memoria",
			"Austral L[íi]?neas A[ée]?reas -? ?Cielos del Sur S.A."				=> "Austral Líneas Aéreas",
			"Austral L[íi]?neas A[ée]?reas -? ?Cielos del Sur S.A."				=> "Austral Líneas Aéreas",
			"Austral L[íi]?neas A[ée]?reas"										=> "Austral Líneas Aéreas",
			"Autoridad de la Cuenca Matanza Riachuelo"							=> "Autoridad de la Cuenca Matanza Riachuelo",
			"Autoridad Regulatoria Nuclear"										=> "Autoridad Regulatoria Nuclear",
			"Ballet Nacional"													=> "Ballet Nacional",
			"Banco Central de la Rep[úu]?blica Argentina"						=> "Banco Central de la República Argentina",
			"Banco Central de la Rep[úu]?blica"									=> "Banco Central de la República Argentina",
			"Banco de Inversi[óo]?n y Comercio Exterior S\.? ?A\."				=> "Banco de Inversión y Comercio Exterior S.A.",
			"Banco Naci[óo]?n"													=> "Banco de la Nación Argentina",
			"Banco de la Naci[óo]?n Argentina"									=> "Banco de la Nación Argentina",
			"Banco Hipotecario Nacional S\?. ?A\.?"								=> "Banco Hipotecario Nacional S.A.",
			"Banco Nacional de Datos Gen[ée]?ticos"								=> "Banco Nacional de Datos Genéticos",
			"Belgrano Cargas y Log[íi]?stica S.A."								=> "Belgrano Cargas y Logística S.A.",
			"Biblioteca Nacional Dr\.? Mariano Moreno"							=> "Biblioteca Nacional Doctor Mariano Moreno",
			"Biblioteca Nacional Doctor Mariano Moreno"							=> "Biblioteca Nacional Doctor Mariano Moreno",
			"Caja de Retiros, Jubilaciones y Pensiones de la Polic[íi]?a Federal" => "Caja de Retiros, Jubilaciones y Pensiones de la Policía Federal",
			"Casa Creativa del Sur"												=> "Casa Creativa del Sur",
			"Casa de Moneda S.E."												=> "Casa de Moneda S.E.",
			"Casas de Contenidos Federales"										=> "Casas de Contenidos Federales",
			"Centro Internacional para la Promoci[óo]?n de los Derechos Humanos" => "Centro Internacional para la Promoción de los Derechos Humanos",
			"Cinemateca y Archivo de la Imagen Nacional"						=> "Cinemateca y Archivo de la Imagen Nacional",
			"Colonia Nacional Dr\.? Manuel A\.? Montes de Oca"					=> "Colonia Nacional Dr. Manuel A Montes de Oca",
			"Comisi[óo]?n Mixta Argentino-Paraguaya del R[íi]?o Paran[áa]?"		=> "Comisión Mixta Argentino-Paraguaya del Río Paraná",
			"Comisi[óo]?n Nacional Antidopaje"									=> "Comisión Nacional Antidopaje",
			"Comisi[óo]?n Nacional de Actividades Espaciales"					=> "Comisión Nacional de Actividades Espaciales",
			"Comisi[óo]?n Nacional de Comercio Exterior"						=> "Comisión Nacional de Comercio Exterior",
			"Comisi[óo]?n Nacional de Coordinaci[óo]?n del Programa de Promoci[óo]?n del Microcr[ée]?dito para el Desarrollo de la Econom[íi]?a Social" => "Comisión Nacional de Coordinación del Programa de Promoción del Microcrédito para el Desarrollo de la Economía Social",
			"Comisi[óo]?n Nacional de Defensa de la Competencia"				=> "Comisión Nacional de Defensa de la Competencia",
			"Comisi[óo]?n Nacional de Energ[íi]?a At[óo]?mica"					=> "Comisión Nacional de Energía Atómica",
			"Comisi[óo]?n Nacional de Evaluaci[óo]?n y Acreditaci[óo]?n Universitaria" => "Comisión Nacional de Evaluación y Acreditación Universitaria",
			"Comisi[óo]?n Nacional de Monumentos, de Lugares y de Bienes Hist[óo]?ricos" => "Comisión Nacional de Monumentos, de Lugares y de Bienes Históricos",
			"Comisi[óo]?n Nacional de Regulaci[óo]?n del Transporte"			=> "Comisión Nacional de Regulación del Transporte",
			"Comisi[óo]?n Nacional de Valores"									=> "Comisión Nacional de Valores",
			"Comisi[óo]?n Nacional Protectora de Bibliotecas Populares"			=> "Comisión Nacional Protectora de Bibliotecas Populares",
			"Comisi[óo]?n T[ée]?cnica Mixta de Salto Grande"					=> "Comisión Técnica Mixta de Salto Grande",
			"Compa[ñn]?[íi]?a Inversora en Transmisi[óo]?n El[ée]?ctrica S.A."	=> "Compañía Inversora en Transmisión Eléctrica S.A.",
			"Consejo Nacional de Coordinaci[óo]?n de Pol[íi]?ticas Sociales"	=> "Consejo Nacional de Coordinación de Políticas Sociales",
			"Consejo Nacional de Investigaciones Cient[íi]?ficas y T[ée]?cnicas" => "Consejo Nacional de Investigaciones Científicas y Técnicas",
			"Construcci[óo]?n de Viviendas para la Armada"						=> "Construcción de Viviendas para la Armada",
			"Contenidos P[úu]?blicos S.E."										=> "Contenidos Públicos S.E.",
			"Corporaci[óo]?n Antiguo Puerto Madero S.A."						=> "Corporación Antiguo Puerto Madero S.A.",
			"Corporaci[óo]?n del Mercado Central de Buenos Aires"				=> "Corporación del Mercado Central de Buenos Aires",
			"Corporaci[óo]?n Interestadual Pulmar[íi]?"							=> "Corporación Interestadual Pulmarí",
			"Corredores Viales S.A."											=> "Corredores Viales S.A.",
			"Correo Oficial de la Rep[úu]?blica Argentina S.A."					=> "Correo Oficial de la República Argentina S.A.",
			"Desarrollo del Capital Humano Ferroviario Sociedad An[óo]?nima con Participaci[óo]?n Estatal Mayoritaria" => "Desarrollo del Capital Humano Ferroviario Sociedad Anónima con Participación Estatal Mayoritaria",
			"Dioxitek S.A."														=> "Dioxitek S.A.",
			"Direcci[óo]?n Nacional de Migraciones"								=> "Dirección Nacional de Migraciones",
			"Direcci[óo]?n Nacional de Vialidad"								=> "Dirección Nacional de Vialidad",
			"Direcci[óo]?n Nacional del Registro Nacional de las Personas"		=> "Dirección Nacional del Registro Nacional de las Personas",
			"Direcci[óo]?n Nacional del Servicio Penitenciario Federal"			=> "Dirección Nacional del Servicio Penitenciario Federal",
			"EDUC\.?AR S.E."													=> "EDUC.AR S.E.",
			"Emprendimientos Energ[ée]?ticos Binacionales S.A."					=> "Emprendimientos Energéticos Binacionales S.A.",
			"Empresa Argentina de Navegaci[óo]?n A[ée]?rea S.E."				=> "Empresa Argentina de Navegación Aérea S.E.",
			"Empresa Argentina de Soluciones Satelitales - ARSAT S.A."			=> "ARSAT",
			"Empresa Argentina de Soluciones Satelitales"						=> "ARSAT",
			"ARSAT S.A."														=> "ARSAT",
			"Ente Binacional Yacyret[áa]?"										=> "Ente Binacional Yacyretá",
			"Ente Nacional de Alto Rendimiento Deportivo"						=> "Ente Nacional de Alto Rendimiento Deportivo",
			"Ente Nacional de Comunicaciones"									=> "Ente Nacional de Comunicaciones",
			"Ente Nacional de Obras H[íi]?dricas de Saneamiento"				=> "Ente Nacional de Obras Hídricas de Saneamiento",
			"Ente Nacional Regulador de la Electricidad"						=> "Ente Nacional Regulador de la Electricidad",
			"Ente Nacional Regulador del Gas"									=> "Ente Nacional Regulador del Gas",
			"Ente Regulador de Agua y Saneamiento"								=> "Ente Regulador de Agua y Saneamiento",
			"Entidad Binacional para el Proyecto Tunel de Baja Altura - Ferrocarril Transandino Central" => "Entidad Binacional para el Proyecto Tunel de Baja Altura - Ferrocarril Transandino Central",
			"Entidad Binacional para el Proyecto Tunel Internacional de Agua Negra" => "Entidad Binacional para el Proyecto Tunel Internacional de Agua Negra",
			"Entidad Binacional para el Proyecto Tunel Internacional Paso Las Leñas" => "Entidad Binacional para el Proyecto Tunel Internacional Paso Las Leñas",
			"Estado Mayor Conjunto de las Fuerzas Armadas"						=> "Estado Mayor Conjunto de las Fuerzas Armadas",
			"Estado Mayor General de la Armada Argentina"						=> "Estado Mayor General de la Armada Argentina",
			"Estado Mayor General de la Fuerza A[ée]?rea Argentina"				=> "Estado Mayor General de la Fuerza Aérea Argentina",
			"Estado Mayor General del Ej[ée]?rcito Argentino"					=> "Estado Mayor General del Ejército Argentino",
			'F[áa]?brica Argentina de Aviones "?Brig\.? San Mart[íi]?n"? S\.? ?A\.?' => "Fábrica Argentina de Aviones Brig. San Martín S.A.",
			"Fabricaciones Militares S.E."										=> "Fabricaciones Militares S.E.",
			"Ferrocarriles Argentinos S.E."										=> "Ferrocarriles Argentinos S.E.",
			"Fondo de Capital Social  S.A."										=> "Fondo de Capital Social S.A.",
			"Fondo Fiduciario Federal de Infraestructura Regional"				=> "Fondo Fiduciario Federal de Infraestructura Regional",
			"Fondo Nacional de las Artes"										=> "Fondo Nacional de las Artes",
			"Fundaci[óo]?n Miguel Lillo"										=> "Fundación Miguel Lillo",
			"Gendarmer[íi]?a Nacional Argentina"								=> "Gendarmería Nacional Argentina",
			"Hospital Nacional Baldomero Sommer"								=> "Hospital Nacional Baldomero Sommer",
			"Hospital Nacional Profesor Alejandro Posadas"						=> "Hospital Nacional Profesor Alejandro Posadas",
			"Hospital Nacional en Red Especializado en Salud Mental y Adicciones Lic\.? Laura Bonaparte (ex CENARESO)" => "Hospital Nacional en Red Especializado en Salud Mental y Adicciones Lic. Laura Bonaparte (ex CENARESO)",
			"Instituto Argentino del Transporte"								=> "Instituto Argentino del Transporte",
			"Instituto de Ayuda Financiera para Pago de Retiros y Pensiones Militares" => "Instituto de Ayuda Financiera para Pago de Retiros y Pensiones Militares",
			"Instituto de Investigaciones Aplicadas"							=> "Instituto de Investigaciones Aplicadas",
			"Instituto de Obra Social de las Fuerzas Armadas"					=> "Instituto de Obra Social de las Fuerzas Armadas",
			"Instituto Geogr[áa]?fico Nacional"									=> "Instituto Geográfico Nacional",
			"Instituto Nacional Juan D\.? Per[óo]?n de Estudios e Investigaciones Hist[óo]?ricas, Sociales y Pol[íi]?ticas" => "Instituto Nacional Juan D. Perón de Estudios e Investigaciones Históricas, Sociales y Políticas",
			"Instituto Nacional Belgraniano"									=> "Instituto Nacional Belgraniano",
			"Instituto Nacional Browniano"										=> "Instituto Nacional Browniano",
			"Instituto Nacional Central [ÚU]?nico Coordinador de Ablación e Implante" => "Instituto Nacional Central Único Coordinador de Ablación e Implante",
			"Instituto Nacional contra la Discriminación, la Xenofobia y el Racismo" => "Instituto Nacional contra la Discriminación, la Xenofobia y el Racismo",
			"Instituto Nacional de Asociativismo y Econom[íi]?a Social"			=> "Instituto Nacional de Asociativismo y Economía Social",
			"Instituto Nacional de Asuntos Ind[íi]?genas"						=> "Instituto Nacional de Asuntos Indígenas",
			"Instituto Nacional de Cine y Artes Audiovisuales"					=> "Instituto Nacional de Cine y Artes Audiovisuales",
			"Instituto Nacional de Educaci[óo]?n Tecnol[óo]?gica"				=> "Instituto Nacional de Educación Tecnológica",
			"Instituto Nacional de Estad[íi]?sticas y Censos"					=> "Instituto Nacional de Estadísticas y Censos",
			"Instituto Nacional de Formaci[óo]?n Docente"						=> "Instituto Nacional de Formación Docente",
			"Instituto Nacional de Investigaci[óo]?n y Desarrollo Pesquero"		=> "Instituto Nacional de Investigación y Desarrollo Pesquero",
			"Instituto Nacional de Investigaciones Hist[óo]?ricas Eva Per[óo]?n" => "Instituto Nacional de Investigaciones Históricas Eva Perón",
			"Instituto Nacional de Investigaciones Hist[óo]?ricas J\.? ?M\.? de Rosas" => "Instituto Nacional de Investigaciones Históricas J.M. de Rosas",
			"Instituto Nacional de Juventud"									=> "Instituto Nacional de Juventud",
			"Instituto Nacional de la M[úu]?sica"								=> "Instituto Nacional de la Música",
			"Instituto Nacional de la Propiedad Industrial"						=> "Instituto Nacional de la Propiedad Industrial",
			"Instituto Nacional de la Yerba Mate"								=> "Instituto Nacional de la Yerba Mate",
			"Instituto Nacional de las Mujeres"									=> "Instituto Nacional de las Mujeres",
			"Instituto Nacional de Medicina Tropical"							=> "Instituto Nacional de Medicina Tropical",
			"Instituto Nacional de Prevenci[óo]?n S[íi]?smica"					=> "Instituto Nacional de Prevención Sísmica",
			"Instituto Nacional de Promoci[óo]?n Tur[íi]?stica"					=> "Instituto Nacional de Promoción Turística",
			"Instituto Nacional de Rehabilitaci[óo]?n Psicof[íi]?sica del Sur Dr\.? Juan Otimio Tesone" => "Instituto Nacional de Rehabilitación Psicofísica del Sur Dr. Juan Otimio Tesone",
			"Instituto Nacional de Semillas"									=> "Instituto Nacional de Semillas",
			"Instituto Nacional de Servicios Sociales para Jubilados y Pensionados" => "Instituto Nacional de Servicios Sociales para Jubilados y Pensionados",
			"Instituto Nacional de Tecnolog[íi]?a Agropecuaria"					=> "Instituto Nacional de Tecnología Agropecuaria",
			"Instituto Nacional de Tecnolog[íi]?a Industrial"					=> "Instituto Nacional de Tecnología Industrial",
			"Instituto Nacional de Vitivinicultura"								=> "Instituto Nacional de Vitivinicultura",
			"Instituto Nacional del Agua"										=> "Instituto Nacional del Agua",
			"Instituto Nacional del C[áa]?ncer"									=> "Instituto Nacional del Cáncer",
			"Instituto Nacional del Teatro"										=> "Instituto Nacional del Teatro",
			"Instituto Nacional Newberiano"										=> "Instituto Nacional Newberiano",
			"Instituto Nacional Sanmartiniano"									=> "Instituto Nacional Sanmartiniano",
			"Instituto Nacional Yrigoyeneano"									=> "Instituto Nacional Yrigoyeneano",
			"Integración Energ[ée]?tica Argentina S\. ?A\.?"					=> "Integración Energética Argentina S.A.",
			"Intercargo S\.? ?A\.? ?C\.?"										=> "Intercargo S.A.C.",
			"Junta de Investigaci[óo]?n de Accidentes de Aviaci[óo]?n Civil"	=> "Junta de Investigación de Accidentes de Aviación Civil",
			"Ministerio de Agricultura, Ganader[ií]?a y Pesca"					=> "Ministerio de Agricultura, Ganadería y Pesca",
			"Ministerio de Defensa"												=> "Ministerio de Defensa",
			"Ministerio de Salud"												=> "Ministerio de Salud",
			"Ministerio de Seguridad"											=> "Ministerio de Seguridad",
			"Museo de Sitio ESMA - ?Ex Centro Clandestino de Detenci[óo]?n, Tortura y Exterminio" => "Museo ESMA",
			"Museo de Sitio ESMA"												=> "Museo ESMA",
			"Museo Nacional de Bellas Artes"									=> "Museo Nacional de Bellas Artes",
			"Naci[óo]?n Burs[áa]?til S.A."										=> "Nación Bursátil S.A.",
			"Naci[óo]?n Servicios S.A."											=> "Nación Servicios S.A.",
			"Nucleoel[ée]?ctrica Argentina S.A."								=> "Nucleoeléctrica Argentina S.A.",
			"Operadora Ferroviaria S.E."										=> "Operadora Ferroviaria S.E.",
			"Organismo Regulador de Seguridad de Presas"						=> "Organismo Regulador de Seguridad de Presas",
			"Organismo Regulador del Sistema Nacional de Aeropuertos"			=> "Organismo Regulador del Sistema Nacional de Aeropuertos",
			"Parque Tecn[óo]?polis del Bicentenario, Ciencia, Tecnolog[íi]?a, Cultura y Arte" => "Parque Tecnópolis del Bicentenario, Ciencia, Tecnología, Cultura y Arte",
			"Pellegrini S\.? ?A\.? Gerente de Fondos Comunes de Inversi[óo]?n"	=> "Pellegrini S.A. Gerente de Fondos Comunes de Inversión",
			"Polic[íi]?a de Seguridad Aeroportuaria"							=> "Policía de Seguridad Aeroportuaria",
			"Polic[íi]?a Federal Argentina"										=> "Policía Federal Argentina",
			"Polo Tecnol[óo]?gico Constituyentes S.A."							=> "Polo Tecnológico Constituyentes S.A.",
			"Prefectura Naval Argentina"										=> "Prefectura Naval Argentina",
			"Procuraci[óo]?n del Tesoro de la Naci[óo]?n"						=> "Procuración del Tesoro de la Nación",
			"Radio de la Universidad Nacional del Litoral S.A."					=> "Radio de la Universidad Nacional del Litoral S.A.",
			"Radio y Televisi[óo]?n Argentina S.E."								=> "Radio y Televisión Argentina S.E.",
			"Registro Nacional de Trabajadores Rurales y Empleadores"			=> "Registro Nacional de Trabajadores Rurales y Empleadores",
			"Servicio de Radio y Televisi[óo]?n de la Universidad Nacional de C[óo]?rdoba S.A." => "Servicio de Radio y Televisión de la Universidad Nacional de Córdoba S.A.",
			"Servicio Geol[óo]?gico Minero Argentino"							=> "Servicio Geológico Minero Argentino",
			"Servicio Meteorol[óo]?gico Nacional"								=> "Servicio Meteorológico Nacional",
			"Servicio Nacional de Sanidad y Calidad Agroalimentaria"			=> "Servicio Nacional de Sanidad y Calidad Agroalimentaria",
			"Sindicatura General de la Naci[óo]?n"								=> "Sindicatura General de la Nación",
			"Superintendencia de Riesgos del Trabajo"							=> "Superintendencia de Riesgos del Trabajo",
			"Superintendencia de Seguros de la Naci[óo]?n"						=> "Superintendencia de Seguros de la Nación",
			"Superintendencia de Servicios de Salud"							=> "Superintendencia de Servicios de Salud",
			"TANDANOR S\.? ?A\.? ?C\.?I\.? y N\.?"								=> "TANDANOR S.A.C.I. y N.",
			"Teatro Nacional Cervantes"											=> "Teatro Nacional Cervantes",
			"Telam S.E."														=> "Telam S.E.",
			"Tribunal de Tasaciones de la Naci[óo]?n"							=> "Tribunal de Tasaciones de la Nación",
			"Tribunal Fiscal de la Naci[óo]?n"									=> "Tribunal Fiscal de la Nación",
			"Unidad de Informaci[óo]?n Financiera"								=> "Unidad de Información Financiera",
			"Unidad Ejecutora de la Obra de Soterramiento del Corredor Ferroviario Caballito-Moreno, de la L[íi]?nea Sarmiento" => "Unidad Ejecutora de la Obra de Soterramiento del Corredor Ferroviario Caballito-Moreno, de la Línea Sarmiento",
			"Unidad Ejecutora del R[ée]?gimen Nacional de Ventanilla [ÚU]?nica de Comercio Exterior Argentino" => "Unidad Ejecutora del Régimen Nacional de Ventanilla Única de Comercio Exterior Argentino",
			"Unidad Especial Sistema de Transmisi[óo]?n de Energía El[ée]?ctrica" => "Unidad Especial Sistema de Transmisión de Energía Eléctrica",
			"Universidad de la Defensa Nacional"								=> "Universidad de la Defensa Nacional",
			"Yacimientos Carbon[íi]?feros de R[íi]?o Turbio"					=> "Yacimientos Carboníferos de Río Turbio",
			"Yacimientos Mineros Agua de Dionisio"								=> "Yacimientos Mineros Agua de Dionisio",
			"YPF Gas S.A."														=> "YPF Gas S.A.",
			"YPF S.A."															=> "YPF S.A.",

		], 'i' );

		// Normalizar años abreviados
		$text = preg_replace_callback([
			"#(Decreto-Ley) (\d+)/(\d+)#",
			"#(Decreto) (\d+)/(\d+)#",
		], function( $matches ) {
			$tipo = $matches[1];
			$n = $matches[2];
			$a = $matches[3];
			if ( $a < 22 ) {
				$a = "20$a"; // Puede fallar para normas anteriores a 1922
			} else if ( $a < 100 ) {
				$a = "19$a";
			}
			return "$tipo $n/$a";
		}, $text );

		return $text;
	}

	static function enlaces( $text ) {
		$articulo = "artículo (\d+)( bis| ter | quáter)?";

		$del = " del ";
		$de_la = " de la ";

		$ley = "Ley (\d+)";
		$decreto = "Decreto (\d+)\/(\d+)";
		$decreto_ley = "Decreto-Ley (\d+)\/(\d+)";
		$ley_provincial = "Ley Provincial (\d+)";
		$codigo_de_comercio = "C.digo de Comercio";
		$codigo_civil = "C.digo Civil";
		$codigo_penal = "C.digo Penal";
		$constitucion_nacional = "Constitución Nacional";


		// Enlazar plurales
		$text = preg_replace_callback([
			"/Leyes ([\d ,yn]+)/i",
		], function( $matches ) {
			$numeros = $matches[1];
			$numeros = preg_replace( "/(\d+)/", "[[Ley@$1|$1]]", $numeros );
			return "Leyes $numeros";
		}, $text );


		return WikilegToolbox::replace( $text, [

			$articulo . $de_la . $ley_provincial								=> "artículo@$1$2 de la [http://www.gob.gba.gov.ar/legislacion/legislacion/l-$3.html Ley@Provincial@$3]",
			$ley_provincial														=> "[http://www.gob.gba.gov.ar/legislacion/legislacion/l-$3.html Ley@Provincial@$1]",

			$articulo . $del . $decreto_ley										=> "[[Decreto-Ley@$3/$4#Artículo@$1|artículo@$1$2 del Decreto-Ley@$3/$4]]",
			$decreto_ley														=> "[[Decreto-Ley@$1/$2|Decreto-Ley@$1/$2]]",

			$articulo . $de_la . $ley											=> "[[Ley@$3#Artículo@$1|artículo@$1$2 de la Ley@$3]]",
			$ley																=> "[[Ley@$1|Ley@$1]]",

			$articulo . $del . $decreto											=> "[[Decreto@$3/$4#Artículo@$1$2|artículo@$1$2 del Decreto@$3/$4]]",
			$decreto															=> "[[Decreto@$1/$2|Decreto@$1/$2]]",

			$articulo . $del . $codigo_de_comercio								=> "[[Código@de@Comercio#Artículo@$1$2|artículo@$1$2 del Código@de@Comercio]]",
			$codigo_de_comercio													=> "[[Código@de@Comercio]]",

			$articulo . $del . $codigo_civil									=> "[[Código@Civil#Artículo@$1$2|artículo@$1$2 del Código@Civil]]",
			$codigo_civil														=> "[[Código@Civil]]",

			$articulo . $del . $codigo_penal									=> "[[Código@Penal#Artículo@$1$2|artículo@$1$2 del Código@Penal]]",
			$codigo_penal														=> "[[Código@Penal]]",

			$articulo . $de_la . $constitucion_nacional							=> "[[Constitución@Nacional#Artículo@$1$2|artículo@$1$2 de la Constitución@Nacional]]",
			$constitucion_nacional												=> "[[Constitución@Nacional]]",

			$articulo															=> "[[#Artículo@$1$2|artículo@$1$2]]",

			"Administración de Infraestructuras Ferroviarias S.E."				=> "[[Organismo::Administración de Infraestructuras Ferroviarias S.E.]]",
			"Administración de Parques Nacionales"								=> "[[Organismo::Administración de Parques Nacionales]]",
			"Administración Federal de Ingresos Públicos"						=> "[[Organismo::Administración Federal de Ingresos Públicos]]",
			"Administración General de Museos y Archivos Presidenciales"		=> "[[Organismo::Administración General de Museos y Archivos Presidenciales]]",
			"Administración General de Puertos S.E."							=> "[[Organismo::Administración General de Puertos S.E.]]",
			"Administración Nacional de Aviación Civil"							=> "[[Organismo::Administración Nacional de Aviación Civil]]",
			"Administración Nacional de Bosques"								=> "[[Organismo::Administración Nacional de Bosques]]",
			"Administración Nacional de la Seguridad Social"					=> "[[Organismo::Administración Nacional de la Seguridad Social]]",
			"Administración Nacional de Laboratorios e Institutos de Salud Dr. Carlos Malbrán" => "[[Organismo::Administración Nacional de Laboratorios e Institutos de Salud Dr. Carlos Malbrán]]",
			"Administración Nacional de Medicamentos, Alimentos y Tecnología Médica" => "[[Organismo::Administración Nacional de Medicamentos, Alimentos y Tecnología Médica]]",
			"Aerolíneas Argentinas"												=> "[[Organismo::Aerolíneas Argentinas]]",
			"Agencia de Acceso a la Información Pública"						=> "[[Organismo::Agencia de Acceso a la Información Pública]]",
			"Agencia de Administración de Bienes del Estado"					=> "[[Organismo::Agencia de Administración de Bienes del Estado]]",
			"Agencia de Deporte Nacional"										=> "[[Organismo::Agencia de Deporte Nacional]]",
			"Agencia de Planificación"											=> "[[Organismo::Agencia de Planificación]]",
			"Agencia Nacional de Discapacidad"									=> "[[Organismo::Agencia Nacional de Discapacidad]]",
			"Agencia Nacional de Laboratorios Públicos"							=> "[[Organismo::Agencia Nacional de Laboratorios Públicos]]",
			"Agencia Nacional de Materiales Controlados"						=> "[[Organismo::Agencia Nacional de Materiales Controlados]]",
			"Agencia Nacional de Promoción Científica y Tecnológica"			=> "[[Organismo::Agencia Nacional de Promoción Científica y Tecnológica]]",
			"Agencia Nacional de Seguridad Vial"								=> "[[Organismo::Agencia Nacional de Seguridad Vial]]",
			"Agua y Saneamientos Argentinos S.A."								=> "[[Organismo::Agua y Saneamientos Argentinos S.A.]]",
			"Archivo Nacional de la Memoria"									=> "[[Organismo::Archivo Nacional de la Memoria]]",
			"Austral Líneas Aéreas"												=> "[[Organismo::Austral Líneas Aéreas]]",
			"Autoridad de la Cuenca Matanza Riachuelo"							=> "[[Organismo::Autoridad de la Cuenca Matanza Riachuelo]]",
			"Autoridad Regulatoria Nuclear"										=> "[[Organismo::Autoridad Regulatoria Nuclear]]",
			"Ballet Nacional"													=> "[[Organismo::Ballet Nacional]]",
			"Banco Central de la República Argentina"							=> "[[Organismo::Banco Central de la República Argentina]]",
			"Banco de Inversión y Comercio Exterior S.A."						=> "[[Organismo::Banco de Inversión y Comercio Exterior S.A.]]",
			"Banco de la Nación Argentina"										=> "[[Organismo::Banco de la Nación Argentina]]",
			"Banco Hipotecario Nacional S.A."									=> "[[Organismo::Banco Hipotecario Nacional S.A.]]",
			"Banco Nacional de Datos Genéticos"									=> "[[Organismo::Banco Nacional de Datos Genéticos]]",
			"Belgrano Cargas y Logística S.A."									=> "[[Organismo::Belgrano Cargas y Logística S.A.]]",
			"Biblioteca Nacional Doctor Mariano Moreno"							=> "[[Organismo::Biblioteca Nacional Doctor Mariano Moreno]]",
			"Caja de Retiros, Jubilaciones y Pensiones de la Policía Federal"	=> "[[Organismo::Caja de Retiros, Jubilaciones y Pensiones de la Policía Federal]]",
			"Casa Creativa del Sur"												=> "[[Organismo::Casa Creativa del Sur]]",
			"Casa de Moneda S.E."												=> "[[Organismo::Casa de Moneda S.E.]]",
			"Casas de Contenidos Federales"										=> "[[Organismo::Casas de Contenidos Federales]]",
			"Centro Internacional para la Promoción de los Derechos Humanos"	=> "[[Organismo::Centro Internacional para la Promoción de los Derechos Humanos]]",
			"Cinemateca y Archivo de la Imagen Nacional"						=> "[[Organismo::Cinemateca y Archivo de la Imagen Nacional]]",
			"Colonia Nacional Dr. Manuel A Montes de Oca"						=> "[[Organismo::Colonia Nacional Dr. Manuel A Montes de Oca]]",
			"Comisión Mixta Argentino-Paraguaya del Río Paraná"					=> "[[Organismo::Comisión Mixta Argentino-Paraguaya del Río Paraná]]",
			"Comisión Nacional Antidopaje"										=> "[[Organismo::Comisión Nacional Antidopaje]]",
			"Comisión Nacional de Actividades Espaciales"						=> "[[Organismo::Comisión Nacional de Actividades Espaciales]]",
			"Comisión Nacional de Comercio Exterior"							=> "[[Organismo::Comisión Nacional de Comercio Exterior]]",
			"Comisión Nacional de Coordinación del Programa de Promoción del Microcrédito para el Desarrollo de la Economía Social" => "[[Organismo::Comisión Nacional de Coordinación del Programa de Promoción del Microcrédito para el Desarrollo de la Economía Social]]",
			"Comisión Nacional de Defensa de la Competencia"					=> "[[Organismo::Comisión Nacional de Defensa de la Competencia]]",
			"Comisión Nacional de Energía Atómica"								=> "[[Organismo::Comisión Nacional de Energía Atómica]]",
			"Comisión Nacional de Evaluación y Acreditación Universitaria"		=> "[[Organismo::Comisión Nacional de Evaluación y Acreditación Universitaria]]",
			"Comisión Nacional de Monumentos, de Lugares y de Bienes Históricos" => "[[Organismo::Comisión Nacional de Monumentos, de Lugares y de Bienes Históricos]]",
			"Comisión Nacional de Regulación del Transporte"					=> "[[Organismo::Comisión Nacional de Regulación del Transporte]]",
			"Comisión Nacional de Valores"										=> "[[Organismo::Comisión Nacional de Valores]]",
			"Comisión Nacional Protectora de Bibliotecas Populares"				=> "[[Organismo::Comisión Nacional Protectora de Bibliotecas Populares]]",
			"Comisión Técnica Mixta de Salto Grande"							=> "[[Organismo::Comisión Técnica Mixta de Salto Grande]]",
			"Compañía Inversora en Transmisión Eléctrica S.A."					=> "[[Organismo::Compañía Inversora en Transmisión Eléctrica S.A.]]",
			"Consejo Nacional de Coordinación de Políticas Sociales"			=> "[[Organismo::Consejo Nacional de Coordinación de Políticas Sociales]]",
			"Consejo Nacional de Investigaciones Científicas y Técnicas"		=> "[[Organismo::Consejo Nacional de Investigaciones Científicas y Técnicas]]",
			"Construcción de Viviendas para la Armada"							=> "[[Organismo::Construcción de Viviendas para la Armada]]",
			"Contenidos Públicos S.E."											=> "[[Organismo::Contenidos Públicos S.E.]]",
			"Corporación Antiguo Puerto Madero S.A."							=> "[[Organismo::Corporación Antiguo Puerto Madero S.A.]]",
			"Corporación del Mercado Central de Buenos Aires"					=> "[[Organismo::Corporación del Mercado Central de Buenos Aires]]",
			"Corporación Interestadual Pulmarí"									=> "[[Organismo::Corporación Interestadual Pulmarí]]",
			"Corredores Viales S.A."											=> "[[Organismo::Corredores Viales S.A.]]",
			"Correo Oficial de la República Argentina S.A."						=> "[[Organismo::Correo Oficial de la República Argentina S.A.]]",
			"Desarrollo del Capital Humano Ferroviario Sociedad Anónima con Participación Estatal Mayoritaria" => "[[Organismo::Desarrollo del Capital Humano Ferroviario Sociedad Anónima con Participación Estatal Mayoritaria]]",
			"Dioxitek S.A."														=> "[[Organismo::Dioxitek S.A.]]",
			"Dirección Nacional de Migraciones"									=> "[[Organismo::Dirección Nacional de Migraciones]]",
			"Dirección Nacional de Vialidad"									=> "[[Organismo::Dirección Nacional de Vialidad]]",
			"Dirección Nacional del Registro Nacional de las Personas"			=> "[[Organismo::Dirección Nacional del Registro Nacional de las Personas]]",
			"Dirección Nacional del Servicio Penitenciario Federal"				=> "[[Organismo::Dirección Nacional del Servicio Penitenciario Federal]]",
			"EDUC.AR S.E."														=> "[[Organismo::EDUC.AR S.E.]]",
			"Emprendimientos Energéticos Binacionales S.A."						=> "[[Organismo::Emprendimientos Energéticos Binacionales S.A.]]",
			"Empresa Argentina de Navegación Aérea S.E."						=> "[[Organismo::Empresa Argentina de Navegación Aérea S.E.]]",
			"ARSAT"																=> "[[Organismo::ARSAT]]",
			"Ente Binacional Yacyretá"											=> "[[Organismo::Ente Binacional Yacyretá]]",
			"Ente Nacional de Alto Rendimiento Deportivo"						=> "[[Organismo::Ente Nacional de Alto Rendimiento Deportivo]]",
			"Ente Nacional de Comunicaciones"									=> "[[Organismo::Ente Nacional de Comunicaciones]]",
			"Ente Nacional de Obras Hídricas de Saneamiento"					=> "[[Organismo::Ente Nacional de Obras Hídricas de Saneamiento]]",
			"Ente Nacional Regulador de la Electricidad"						=> "[[Organismo::Ente Nacional Regulador de la Electricidad]]",
			"Ente Nacional Regulador del Gas"									=> "[[Organismo::Ente Nacional Regulador del Gas]]",
			"Ente Regulador de Agua y Saneamiento"								=> "[[Organismo::Ente Regulador de Agua y Saneamiento]]",
			"Entidad Binacional para el Proyecto Tunel de Baja Altura - Ferrocarril Transandino Central" => "[[Organismo::Entidad Binacional para el Proyecto Tunel de Baja Altura - Ferrocarril Transandino Central]]",
			"Entidad Binacional para el Proyecto Tunel Internacional de Agua Negra" => "[[Organismo::Entidad Binacional para el Proyecto Tunel Internacional de Agua Negra]]",
			"Entidad Binacional para el Proyecto Tunel Internacional Paso Las Leñas" => "[[Organismo::Entidad Binacional para el Proyecto Tunel Internacional Paso Las Leñas]]",
			"Estado Mayor Conjunto de las Fuerzas Armadas"						=> "[[Organismo::Estado Mayor Conjunto de las Fuerzas Armadas]]",
			"Estado Mayor General de la Armada Argentina"						=> "[[Organismo::Estado Mayor General de la Armada Argentina]]",
			"Estado Mayor General de la Fuerza Aérea Argentina"					=> "[[Organismo::Estado Mayor General de la Fuerza Aérea Argentina]]",
			"Estado Mayor General del Ejército Argentino"						=> "[[Organismo::Estado Mayor General del Ejército Argentino]]",
			"Fábrica Argentina de Aviones “Brig. San Martín” S.A."				=> "[[Organismo::Fábrica Argentina de Aviones “Brig. San Martín” S.A.]]",
			"Fabricaciones Militares S.E."										=> "[[Organismo::Fabricaciones Militares S.E.]]",
			"Ferrocarriles Argentinos S.E."										=> "[[Organismo::Ferrocarriles Argentinos S.E.]]",
			"Fondo de Capital Social S.A."										=> "[[Organismo::Fondo de Capital Social S.A.]]",
			"Fondo Fiduciario Federal de Infraestructura Regional"				=> "[[Organismo::Fondo Fiduciario Federal de Infraestructura Regional]]",
			"Fondo Nacional de las Artes"										=> "[[Organismo::Fondo Nacional de las Artes]]",
			"Fundación Miguel Lillo"											=> "[[Organismo::Fundación Miguel Lillo]]",
			"Gendarmería Nacional Argentina"									=> "[[Organismo::Gendarmería Nacional Argentina]]",
			"Hospital Nacional Baldomero Sommer"								=> "[[Organismo::Hospital Nacional Baldomero Sommer]]",
			"Hospital Nacional Profesor Alejandro Posadas"						=> "[[Organismo::Hospital Nacional Profesor Alejandro Posadas]]",
			"Hospital Nacional en Red Especializado en Salud Mental y Adicciones Lic. Laura Bonaparte (ex CENARESO)" => "[[Organismo::Hospital Nacional en Red Especializado en Salud Mental y Adicciones Lic. Laura Bonaparte (ex CENARESO)]]",
			"Instituto Argentino del Transporte"								=> "[[Organismo::Instituto Argentino del Transporte]]",
			"Instituto de Ayuda Financiera para Pago de Retiros y Pensiones Militares" => "[[Organismo::Instituto de Ayuda Financiera para Pago de Retiros y Pensiones Militares]]",
			"Instituto de Investigaciones Aplicadas"							=> "[[Organismo::Instituto de Investigaciones Aplicadas]]",
			"Instituto de Obra Social de las Fuerzas Armadas"					=> "[[Organismo::Instituto de Obra Social de las Fuerzas Armadas]]",
			"Instituto Geográfico Nacional"										=> "[[Organismo::Instituto Geográfico Nacional]]",
			"Instituto Nacional Juan D. Perón de Estudios e Investigaciones Históricas, Sociales y Políticas" => "[[Organismo::Instituto Nacional Juan D. Perón de Estudios e Investigaciones Históricas, Sociales y Políticas]]",
			"Instituto Nacional Belgraniano"									=> "[[Organismo::Instituto Nacional Belgraniano]]",
			"Instituto Nacional Browniano"										=> "[[Organismo::Instituto Nacional Browniano]]",
			"Instituto Nacional Central Único Coordinador de Ablación e Implante" => "[[Organismo::Instituto Nacional Central Único Coordinador de Ablación e Implante]]",
			"Instituto Nacional contra la Discriminación, la Xenofobia y el Racismo" => "[[Organismo::Instituto Nacional contra la Discriminación, la Xenofobia y el Racismo]]",
			"Instituto Nacional de Asociativismo y Economía Social"				=> "[[Organismo::Instituto Nacional de Asociativismo y Economía Social]]",
			"Instituto Nacional de Asuntos Indígenas"							=> "[[Organismo::Instituto Nacional de Asuntos Indígenas]]",
			"Instituto Nacional de Cine y Artes Audiovisuales"					=> "[[Organismo::Instituto Nacional de Cine y Artes Audiovisuales]]",
			"Instituto Nacional de Educación Tecnológica"						=> "[[Organismo::Instituto Nacional de Educación Tecnológica]]",
			"Instituto Nacional de Estadísticas y Censos"						=> "[[Organismo::Instituto Nacional de Estadísticas y Censos]]",
			"Instituto Nacional de Formación Docente"							=> "[[Organismo::Instituto Nacional de Formación Docente]]",
			"Instituto Nacional de Investigación y Desarrollo Pesquero"			=> "[[Organismo::Instituto Nacional de Investigación y Desarrollo Pesquero]]",
			"Instituto Nacional de Investigaciones Históricas Eva Perón"		=> "[[Organismo::Instituto Nacional de Investigaciones Históricas Eva Perón]]",
			"Instituto Nacional de Investigaciones Históricas J.M. DE ROSAS"	=> "[[Organismo::Instituto Nacional de Investigaciones Históricas J.M. DE ROSAS]]",
			"Instituto Nacional de Juventud"									=> "[[Organismo::Instituto Nacional de Juventud]]",
			"Instituto Nacional de la Música"									=> "[[Organismo::Instituto Nacional de la Música]]",
			"Instituto Nacional de la Propiedad Industrial"						=> "[[Organismo::Instituto Nacional de la Propiedad Industrial]]",
			"Instituto Nacional de la Yerba Mate"								=> "[[Organismo::Instituto Nacional de la Yerba Mate]]",
			"Instituto Nacional de las Mujeres"									=> "[[Organismo::Instituto Nacional de las Mujeres]]",
			"Instituto Nacional de Medicina Tropical"							=> "[[Organismo::Instituto Nacional de Medicina Tropical]]",
			"Instituto Nacional de Prevención Sísmica"							=> "[[Organismo::Instituto Nacional de Prevención Sísmica]]",
			"Instituto Nacional de Promoción Turística"							=> "[[Organismo::Instituto Nacional de Promoción Turística]]",
			"Instituto Nacional de Rehabilitación Psicofísica del Sur Dr. Juan Otimio Tesone" => "[[Organismo::Instituto Nacional de Rehabilitación Psicofísica del Sur Dr. Juan Otimio Tesone]]",
			"Instituto Nacional de Semillas"									=> "[[Organismo::Instituto Nacional de Semillas]]",
			"Instituto Nacional de Servicios Sociales para Jubilados y Pensionados" => "[[Organismo::Instituto Nacional de Servicios Sociales para Jubilados y Pensionados]]",
			"Instituto Nacional de Tecnología Agropecuaria"						=> "[[Organismo::Instituto Nacional de Tecnología Agropecuaria]]",
			"Instituto Nacional de Tecnología Industrial"						=> "[[Organismo::Instituto Nacional de Tecnología Industrial]]",
			"Instituto Nacional de Vitivinicultura"								=> "[[Organismo::Instituto Nacional de Vitivinicultura]]",
			"Instituto Nacional del Agua"										=> "[[Organismo::Instituto Nacional del Agua]]",
			"Instituto Nacional del Cáncer"										=> "[[Organismo::Instituto Nacional del Cáncer]]",
			"Instituto Nacional del Teatro"										=> "[[Organismo::Instituto Nacional del Teatro]]",
			"Instituto Nacional Newberiano"										=> "[[Organismo::Instituto Nacional Newberiano]]",
			"Instituto Nacional Sanmartiniano"									=> "[[Organismo::Instituto Nacional Sanmartiniano]]",
			"Instituto Nacional Yrigoyeneano"									=> "[[Organismo::Instituto Nacional Yrigoyeneano]]",
			"Integración Energética Argentina S.A."								=> "[[Organismo::Integración Energética Argentina S.A.]]",
			"Intercargo S.A.C."													=> "[[Organismo::Intercargo S.A.C.]]",
			"Junta de Investigación de Accidentes de Aviación Civil"			=> "[[Organismo::Junta de Investigación de Accidentes de Aviación Civil]]",
			"Ministerio de Seguridad"											=> "[[Organismo::Ministerio de Seguridad]]",
			"Museo ESMA"														=> "[[Organismo::Museo ESMA]]",
			"Museo Nacional de Bellas Artes"									=> "[[Organismo::Museo Nacional de Bellas Artes]]",
			"Nación Bursátil S.A."												=> "[[Organismo::Nación Bursátil S.A.]]",
			"Nación Servicios S.A."												=> "[[Organismo::Nación Servicios S.A.]]",
			"Nucleoeléctrica Argentina S.A."									=> "[[Organismo::Nucleoeléctrica Argentina S.A.]]",
			"Operadora Ferroviaria S.E."										=> "[[Organismo::Operadora Ferroviaria S.E.]]",
			"Organismo Regulador de Seguridad de Presas"						=> "[[Organismo::Organismo Regulador de Seguridad de Presas]]",
			"Organismo Regulador del Sistema Nacional de Aeropuertos"			=> "[[Organismo::Organismo Regulador del Sistema Nacional de Aeropuertos]]",
			"Parque Tecnópolis del Bicentenario, Ciencia, Tecnología, Cultura y Arte" => "[[Organismo::Parque Tecnópolis del Bicentenario, Ciencia, Tecnología, Cultura y Arte]]",
			"Pellegrini S.A. Gerente de Fondos Comunes de Inversión"			=> "[[Organismo::Pellegrini S.A. Gerente de Fondos Comunes de Inversión]]",
			"Policía de Seguridad Aeroportuaria"								=> "[[Organismo::Policía de Seguridad Aeroportuaria]]",
			"Policía Federal Argentina"											=> "[[Organismo::Policía Federal Argentina]]",
			"Polo Tecnológico Constituyentes S.A."								=> "[[Organismo::Polo Tecnológico Constituyentes S.A.]]",
			"Prefectura Naval Argentina"										=> "[[Organismo::Prefectura Naval Argentina]]",
			"Procuración del Tesoro de la Nación"								=> "[[Organismo::Procuración del Tesoro de la Nación]]",
			"Radio de la Universidad Nacional del Litoral S.A."					=> "[[Organismo::Radio de la Universidad Nacional del Litoral S.A.]]",
			"Radio y Televisión Argentina S.E."									=> "[[Organismo::Radio y Televisión Argentina S.E.]]",
			"Registro Nacional de Trabajadores Rurales y Empleadores"			=> "[[Organismo::Registro Nacional de Trabajadores Rurales y Empleadores]]",
			"Servicio de Radio y Televisión de la Universidad Nacional de Córdoba S.A." => "[[Organismo::Servicio de Radio y Televisión de la Universidad Nacional de Córdoba S.A.]]",
			"Servicio Geológico Minero Argentino"								=> "[[Organismo::Servicio Geológico Minero Argentino]]",
			"Servicio Meteorológico Nacional"									=> "[[Organismo::Servicio Meteorológico Nacional]]",
			"Servicio Nacional de Sanidad y Calidad Agroalimentaria"			=> "[[Organismo::Servicio Nacional de Sanidad y Calidad Agroalimentaria]]",
			"Sindicatura General de la Nación"									=> "[[Organismo::Sindicatura General de la Nación]]",
			"Superintendencia de Riesgos del Trabajo"							=> "[[Organismo::Superintendencia de Riesgos del Trabajo]]",
			"Superintendencia de Seguros de la Nación"							=> "[[Organismo::Superintendencia de Seguros de la Nación]]",
			"Superintendencia de Servicios de Salud"							=> "[[Organismo::Superintendencia de Servicios de Salud]]",
			"TANDANOR S.A.C.I. y N."											=> "[[Organismo::TANDANOR S.A.C.I. y N.]]",
			"Teatro Nacional Cervantes"											=> "[[Organismo::Teatro Nacional Cervantes]]",
			"Telam S.E."														=> "[[Organismo::Telam S.E.]]",
			"Tribunal de Tasaciones de la Nación"								=> "[[Organismo::Tribunal de Tasaciones de la Nación]]",
			"Tribunal Fiscal de la Nación"										=> "[[Organismo::Tribunal Fiscal de la Nación]]",
			"Unidad de Información Financiera"									=> "[[Organismo::Unidad de Información Financiera]]",
			"Unidad Ejecutora de la Obra de Soterramiento del Corredor Ferroviario Caballito-Moreno, de la Línea Sarmiento" => "[[Organismo::Unidad Ejecutora de la Obra de Soterramiento del Corredor Ferroviario Caballito-Moreno, de la Línea Sarmiento]]",
			"Unidad Ejecutora del Régimen Nacional de Ventanilla Única de Comercio Exterior Argentino" => "[[Organismo::Unidad Ejecutora del Régimen Nacional de Ventanilla Única de Comercio Exterior Argentino]]",
			"Unidad Especial Sistema de Transmisión de Energía Eléctrica"		=> "[[Organismo::Unidad Especial Sistema de Transmisión de Energía Eléctrica]]",
			"Universidad de la Defensa Nacional"								=> "[[Organismo::Universidad de la Defensa Nacional]]",
			"Yacimientos Carboníferos de Río Turbio"							=> "[[Organismo::Yacimientos Carboníferos de Río Turbio]]",
			"Yacimientos Mineros Agua de Dionisio"								=> "[[Organismo::Yacimientos Mineros Agua de Dionisio]]",
			"YPF Gas S.A."														=> "[[Organismo::YPF Gas S.A.]]",
			"YPF S.A."															=> "[[Organismo::YPF S.A.]]",

			"@"																	=> " ",
		]);
	}

	static function secciones( $text ) {

		$numero = "([\w\d\/]+)";

		$bis = "(bis|ter|quáter)";

		$separador = "[\s\-\–\—\.\:]{1,4}";

		$titulo = "([^\n=]+)\.?\n";

		$resumen = "([^\n=]{1,90})\.?\n"; // El límite de 90 es para no capturar un párrafo cuando no hay resumen

		$text = WikilegToolbox::replace( $text, [

			// Secciones
			"\nPor ello;\n\n.+"						=> "\n\n== Ley ==\n\n",
			"\nVISTO Y CONSIDERANDO:?"				=> "\n\n== Visto y considerando ==\n\n",
			"\nVISTO:?"								=> "\n\n== Visto ==\n\n",
			"\nCONSIDERANDO:?"						=> "\n\n== Considerando ==\n\n",
			"\nDECRETA:?"							=> "\n\n== Decreto ==\n\n",
			"\nRESUELVE:?"							=> "\n\n== Resolución ==\n\n",
			"\nDECIDE:?"							=> "\n\n== Decisión ==\n\n",
			"\nDISPONE:?"							=> "\n\n== Disposición ==\n\n",
			"\nNOTA:?"								=> "", // ???
			"Han convenido lo siguiente:"			=> "\n\n== Convenio ==\n\n", // Ley 25399

			"\nACUERDO DE $titulo"					=> "\n\n= Acuerdo $1 =\n\n",
			"\nCONVENCI.N $titulo"					=> "\n\n= Convención $1 =\n\n",
			"\nCONVENIO $titulo"					=> "\n\n= Convenio $1 =\n\n",
			"\nENMIENDA $titulo"					=> "\n\n= Enmienda $1 =\n\n",
			"\nPROTOCOLO $titulo"					=> "\n\n= Protocolo $1 =\n\n",
//			"\nPRE.MBULO\n"							=> "\n\n== Preámbulo ==\n\n",

			"\nArt.culo $numero $bis$separador"		=> "\n\n====== Artículo $1 $2 ======\n\n",
			"\nArt.culo $numero$separador"			=> "\n\n====== Artículo $1 ======\n\n",

			"\nCap.tulo $numero$separador$resumen"	=> "\n\n===== Capítulo $1 - $2 =====\n\n",
			"\nCap.tulo $numero$separador"			=> "\n\n===== Capítulo $1 =====\n\n",
			"\n([IVX]+)[^\w]$separador$resumen"		=> "\n\n===== Capítulo $1 - $2 =====\n\n", // Ley 12345
			"\n([IVX]+)[^\w]$separador"				=> "\n\n===== Capítulo $1 =====\n\n", // Ley 14370

			"\nT.tulo $numero$separador$resumen"	=> "\n\n==== Título $1 - $2 ====\n\n",
			"\nT.tulo $numero$separador"			=> "\n\n==== Título $1 ====\n\n",

			"\nSecci.n $numero$separador$resumen"	=> "\n\n=== Sección $1 - $2 ===\n\n",
			"\nSecci.n $numero$separador"			=> "\n\n=== Sección $1 ===\n\n",

			"\nParte $numero$separador$resumen"		=> "\n\n== Parte $1 - $2 ==\n\n",
			"\nParte $numero$separador"				=> "\n\n== Parte $1 ==\n\n",

			"\nAnexo $numero$separador$resumen"		=> "\n\n== Anexo $1 - $2 ==\n\n",
			"\nAnexo $numero$separador"				=> "\n\n== Anexo $1 ==\n\n",
			"\nAnexo $resumen"						=> "\n\n== Anexo - $1 ==\n\n",
			"\nAnexo"								=> "\n\n== Anexo ==\n\n",

			// Hasta encontrar la manera de quitar los índices, mas vale marcarlos como tales
			"\nINDICE"								=> "\n\n== Índice ==\n\n",

		], 'i' );

		// Normalizar secciones
		$text = preg_replace_callback([
			"/\n(=+)(.+?)(=+)\n/"
		], function( $matches ) {
			$antes = $matches[1];
			$titulo = $matches[2];
			$despues = $matches[3];

			$titulo = WikilegToolbox::title_case( $titulo );

			// Normalizar bis, ter, quáter, etc. y los sin número
			$titulo = WikilegToolbox::replace( $titulo, [
				'Bis' 		=> 'bis',
				'Ter' 		=> 'ter',
				'Qu[aá]ter' => 'quáter',
				'S\/n'		=> 'S/N',
			]);

			// Normalizar números romanos en artículos
			$titulo = preg_replace_callback([
				"/Artículo ([ivxlc]+)/i"
			], function( $matches ) {
				$numero = WikilegToolbox::roman2int( $matches[1] );
				return "Artículo $numero";
			}, $titulo );

			// Normalizar ordinales en artículos
			$titulo = preg_replace_callback([
				"/Artículo ([A-Za-z]+)/i"
			], function( $matches ) {
				$numero = WikilegToolbox::ordinal2int( $matches[1] );
				return "Artículo $numero";
			}, $titulo );

			// Normalizar el resumen
			$titulo = preg_replace_callback([
				"/(.+?) \- (.+)/"
			], function( $matches ) {
				$titulo = WikilegToolbox::title_case( $matches[1] );
				$resumen = WikilegToolbox::title_case( $matches[2] );
				$resumen = trim( $resumen, '".' );
				$resumen = WikilegToolbox::replace( $resumen, [
					'S\/n' => 'S/N',
				]);
				return "$titulo - $resumen";
			}, $titulo );

			return "\n$antes $titulo $despues\n";

		}, $text );

		return $text;
	}

	static function citas( $text ) {
		$text = WikilegToolbox::replace( $text, [
			"siguiente:\n+(.+?)(\n=+)"					=> "siguiente:\n\n<blockquote>\n$1\n</blockquote>$2",
			"siguiente (\w+):\n+\"?(.+?)\"?.?(\n=+)"	=> "siguiente $1:\n\n<blockquote>\n$2\n</blockquote>$3", // Decreto 895/2018#Artículo 9
			"siguiente: \"(.+?)\"?\.?(\n=+)"			=> "siguiente:\n\n<blockquote>\n$1\n</blockquote>$2",
			"siguientes:\n+(.+?)(\n=+)"					=> "siguientes:\n\n<blockquote>\n$1\n</blockquote>$2",
			"\n\"([^\"]{19,}?)\"?\.?(\n=+)"				=> "\n\n<blockquote>\n$1\n</blockquote>$2", // Ley 12345#Artículo 10
		], 'is' );

		// Quitar los títulos de las citas
		$text = preg_replace_callback([
			"/<blockquote>(.+?)<\/blockquote>/s"
		], function( $matches ) {
			$blockquote = $matches[0];
			$blockquote = self::secciones( $blockquote );
			$blockquote = preg_replace( "/ ?==+ ?/", "'''", $blockquote ); // Reemplazar títulos por negritas
			return $blockquote;
		}, $text );

		return $text;
	}

	static function listas( $text ) {
		return WikilegToolbox::replace( $text, [
			"\n\(?(\d{1,2})[\.\-\–\)]{1,2}(\d{1,2})[\.\-\–\)]{1,2}"	=> "\n$1.$2) ", // Ley 24206#Sección XII
			"\n\(?(\d{1,2})[\.\-\–\)]{1,2}"							=> "\n$1) ", // Ley 27444#Artículo 78
			"\n\(?([a-z])[\.\-\–\)]{1,2}"							=> "\n$1) ",
			"\n\(?([ivx]{1,4})[\.\-\–\)]{1,2}"						=> "\n$1) ", // Números romanos
			"\n•"													=> "\n*", // Ley 25399#Artículo 3
			"\n\n\*([^\n]+)"										=> "\n*$1", // Ley 25399#Artículo 3, pero no funciona
		], 'i' );
	}

	static function imagenes( $text, $texto_original ) {
		return preg_replace_callback([
			"/\n\[([\w-]+\.jpg)\]\n/"
		], function( $matches ) use( $texto_original ) {
			$barra = mb_strrpos( $texto_original, '/' ); // Posición de la última barra
			$base = mb_substr( $texto_original, 0, $barra + 1 );
			$imagen = $base . $matches[1];
			return "\n\n$imagen\n\n";
		}, $text );
	}

	static function notas( $text ) {
		$text = WikilegToolbox::replace( $text, [
			"\n\(Nota de redacción\) ?\((.*?)\)"	=> "\n{{Nota|$1}}\n", // Decreto-Ley 4104/1943
			"\n\(Nota Infoleg: ?(.{9,}?)\)\.?"		=> "\n{{Nota|$1}}\n", // Ley 25413
			"\n\(Notas?: ?(.{9,}?)\)\.?"			=> "\n{{Nota|$1}}\n",
		], 'is' );

		$text = WikilegToolbox::replace( $text, [
			"\n\((.*?)\)\.?\n"				=> "\n{{Nota|$1}}\n", // Ley 2393 Artículo 116
		]);

		return $text;
	}

	static function limpiar( $text ) {

		// Limpieza de formalidades
		$text = WikilegToolbox::replace( $text, [
			"\ne?la?\spresident[ea]\s+de\s+la\s+naci.n\s+argentina,?"	=> "",
			"\nPor ello,[^=]+"											=> "",
			"\. Que"													=> ".\n\nQue", // Para la sección Considerando
			", y\n"														=> ".\n",
		], 'i' );

		/**
		 * ATENCIÓN !!!
		 * Estas reglas son agresivas y podrían estar eliminando texto importante
		 */
		$text = WikilegToolbox::replace( $text, [
			"^.*?=="						=> "\n==", // Quitar todo hasta el primer título
			"\nComuníquese(.+?)\n([^=]+)"	=> "\nComuníquese$1\n", // Quita todo luego del último artículo
		], 's' );

		/**
		 * Limpieza de basura sintáctica
		 */
		$text = WikilegToolbox::replace( $text, [
			"\n[—\- ]+" => "\n", // Saltos de línea seguidos de guiones (Ley 22964) o espacios
			"\n,"		=> "\n", // Saltos de línea seguidos de comas
			"\n\n\n+"	=> "\n\n", // Saltos de línea múltiples
			" +\)"		=> ")", // Espacios seguidos de parentesis
			"\( +"		=> "(", // Parentesis seguidos de espacios
			" +,"		=> ",", // Espacios seguidos de comas
			",[^ ]"		=> ", ", // Comas no seguidas de espacios
			"  +"		=> " ", // Espacios múltiples
			" +\n"		=> "\n", // Espacios al final de los renglones
		]);

		return trim( $text );
	}
}