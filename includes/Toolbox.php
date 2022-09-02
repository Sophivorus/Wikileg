<?php

use MediaWiki\MediaWikiServices;

class WikilegToolbox {

	/**
	 * Crear una página
	 */
	static function create( $titulo, $texto ) {
		$User = User::newSystemUser( 'Sophivorus' );
		$Instance = MediaWikiServices::getInstance();
		$Config = $Instance->getMainConfig();
		$Title = Title::newFromText( $titulo );
		$timestamp = wfTimestampNow();
		$Revision = new WikiRevision( $Config );
		$Revision->setTitle( $Title );
		$Revision->setModel( 'wikitext' );
		$Revision->setText( $texto );
		$Revision->setUserObj( $User );
		$Revision->setTimestamp( $timestamp );
		$Revision->importOldRevision();
		return $Title;
	}

	/**
	 * Replace the given patterns
	 */
	static function replace( $string, array $patterns, string $modifiers = '' ) {
		foreach ( $patterns as $pattern => $replacement ) {
			$result = preg_replace( '/' . $pattern . '/u' . $modifiers, $replacement, $string );
			if ( $result ) {
				$string = $result;
			} elseif ( preg_last_error() !== PREG_NO_ERROR ) {
				$error = array_flip( get_defined_constants( true )['pcre'] )[ preg_last_error() ];
				echo "$error\n";
				echo "$pattern\n";
				echo "$replacement\n";
			}
		}
		return $string;
	}

	/**
	 * Get contents in UTF8 from a given URL or path
	 */
	static function file_get_contents_utf8( $path ) { 
		$contents = file_get_contents( $path );
		$encoding = mb_detect_encoding( $contents, 'UTF-8, ISO-8859-1, windows-1252', true );
		return mb_convert_encoding( $contents, 'UTF-8', $encoding ); 
	}

	/**
	 * Get the contents of a URL via CURL
	 */
	static function curl_get_contents( $url ) {
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $curl, CURLOPT_URL, $url );
		$contents = curl_exec( $curl );
		curl_close( $curl );
		if ( $contents ) {
			$encoding = mb_detect_encoding( $contents, 'UTF-8, ISO-8859-1, windows-1252', true );
			$contents = mb_convert_encoding( $contents, 'UTF-8', $encoding );
			return $contents;
		}
		return false;
	}

	/**
	 * Normalize name
	 */
	static function normalize_name( $name ) {
		$name = self::title_case( $name );
		$name = preg_replace( '/(.*), ?(.*)/', '$2 $1', $name ); // Perez, Juan -> Juan Perez
		return $name;
	}

	/**
	 * Title case
	 */
	static function title_case( $string ) {
		$string = trim( $string );
		$string = mb_strtolower( $string );
		$string = self::tildar( $string );
		$string = mb_convert_case( $string, MB_CASE_TITLE, "UTF-8" );
	
		$preposiciones = ['a','ante','bajo','cabe','con','contra','de','desde','durante','en','entre','hacia','hasta','mediante','para','por','según','sin','so','sobre','tras','versus','vía'];
		$articulos = ['el','la','los','las','un','una','unos','unas'];
		$pronombres = ['este','esta','esto','estos','estas','ese','esa','eso','esos','esas','aquel','aquello','aquella','aquellos','aquellas'];
		$otros = ['e','y','o','u','que','del','al','su'];
		$patterns = array_merge( $preposiciones, $pronombres, $articulos, $otros );
		foreach ( $patterns as $pattern ) {
			$result = preg_replace( '/ ' . ucfirst( $pattern ) . ' /u', " $pattern ", $string );
			if ( $result ) {
				$string = $result;
			}
		}

		// Conservar los números romanos en mayúsculas
		$string = preg_replace_callback([
			"/\b([IVX]+)\b/i"
		], function( $matches ) {
			return strtoupper( $matches[1] );
		}, $string );
	
		return $string;
	}

	/**
	 * Sentence case
	 */
	static function sentence_case( $string ) {
		$string = self::tildar( $string );
		$sentences = preg_split( '/([.?!]+)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$string = '';
		foreach ( $sentences as $key => $sentence ) {
			$string .= ( $key & 1 ) == 0 ? ucfirst( mb_strtolower( trim( $sentence ) ) ) : $sentence . ' ';
		}
		$string = trim( $string );

		// Siglas como B. O.
		$string = preg_replace_callback([
			"/ ([a-z])\./"
		], function( $matches ) {
			$sigla = mb_strtoupper( $matches[1] );
			return " $sigla.";
		}, $string );

		// Numeros como 12.345
		$string = self::replace( $string, [
			"(\d+)\. (\d+)" => "$1$2",
		]);

		return $string;
	}

	/**
	 * Poner tildes
	 */
	static function tildar( $string ) {
		$sow = "(\b)"; // Start of word
		$eow = "(\b|\.|\,)"; // End of word
		return self::replace( $string, [
			"cion" . $eow => "ción$1",
			"logia" . $eow	=> "logía$2",
			$sow . "abogacia" . $eow	=> "$1abogacía$2",
			$sow . "aeronautica" . $eow	=> "$1aeronáutica$2",
			$sow . "articulo" . $eow	=> "$1artículo$2",
			$sow . "articulos" . $eow	=> "$1artículos$2",
			$sow . "bioquimica" . $eow	=> "$1bioquímica$2",
			$sow . "codigo" . $eow		=> "$1código$2",
			$sow . "credito" . $eow		=> "$1crédito$2",
			$sow . "derogase" . $eow	=> "$1derógase$2",
			$sow . "deposito" . $eow	=> "$1depósito$2",
			$sow . "economico" . $eow	=> "$1económico$2",
			$sow . "economicas" . $eow	=> "$1económicas$2",
			$sow . "historico" . $eow	=> "$1histórico$2",
			$sow . "interes" . $eow		=> "$1interés$2",
			$sow . "nacion" . $eow		=> "$1nación$2",
			$sow . "numero" . $eow		=> "$1número$2",
			$sow . "periodo" . $eow		=> "$1período$2",
			$sow . "publica" . $eow		=> "$1pública$2",
			$sow . "publicas" . $eow	=> "$1públicas$2",
			$sow . "publico" . $eow		=> "$1público$2",
			$sow . "publicos" . $eow	=> "$1públicos$2",
			$sow . "regimen" . $eow		=> "$1régimen$2",
			$sow . "regulanse" . $eow	=> "$1regúlanse$2",
			$sow . "resolucion" . $eow	=> "$1resolución$2",
			$sow . "tecnica" . $eow		=> "$1técnica$2",
			$sow . "basicas" . $eow		=> "$1básicas$2",
		], "i" );
	}

	/**
	 * Replacement for the mysqli::get_result method
	 * for when mysqlnd is not available
	 */
	static function get_result( $statement ) {
		$result = [];
		$statement->store_result();
		for ( $i = 0; $i < $statement->num_rows; $i++ ) {
			$metadata = $statement->result_metadata();
			$params = [];
			while ( $Field = $metadata->fetch_field() ) {
				$params[] = &$result[ $i ][ $Field->name ];
			}
			call_user_func_array( [ $statement, 'bind_result' ], $params );
			$statement->fetch();
		}
		return $result;
	}

	static function roman2int( $roman ) {
		$romans = [
			'M' => 1000,
			'CM' => 900,
			'D' => 500,
			'CD' => 400,
			'C' => 100,
			'XC' => 90,
			'L' => 50,
			'XL' => 40,
			'X' => 10,
			'IX' => 9,
			'V' => 5,
			'IV' => 4,
			'I' => 1,
		];
		$int = 0;
		foreach ( $romans as $key => $value ) {
		    while ( strpos( $roman, $key ) === 0) {
		        $int += $value;
		        $roman = substr( $roman, strlen( $key ) );
		    }
		}
		return $int;
	}

	static function ordinal2int( $ordinal ) {
		$int = self::replace( $ordinal, [
			"primer[oa]"	=> "1",
			"segund[oa]"	=> "2",
			"tercer[oa]"	=> "3",
			"cuart[oa]"		=> "4",
			"quint[oa]"		=> "5",
			"sext[oa]"		=> "6",
			"s.ptim[oa]"	=> "7",
			"octav[oa]"		=> "8",
			"noven[oa]"		=> "9",
		], "i" );
		return $int;
	}

	/**
	 * Dump and die
	 */
	static function dd( $var ) {
		var_dump( $var );
		die;
	}
}