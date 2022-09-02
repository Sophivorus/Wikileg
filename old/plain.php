<?php

/**
 * Publish the given URL without modification
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/Parser.php';
require_once __DIR__ . '/includes/forceUTF8.php';
require_once __DIR__ . '/../wikiar.org/maintenance/Maintenance.php';

use \ForceUTF8\Encoding;
use MediaWiki\MediaWikiServices;

class WikileyPlainScript extends Maintenance {

	/**
	 * Define the script options
	 */
	public function __construct() {
		parent::__construct();
		$this->addOption( 'title', 'Title of the page', true, true, 't' );
		$this->addOption( 'source', 'URL of the source text', true, true, 's' );
	}

	/**
	 * Main script
	 */
	public function execute() {
		$title = $this->getOption( 'title' );

        // Get the text
		$source = $this->getOption( 'source' );
        $text = shell_exec( "lynx -dump -width 99999999 -display_charset UTF-8 -nolist $source" );

        // Parse it
		$text = Encoding::toUTF8( $text );
		$text = WikilegParser::normalizar( $text );
		$text = WikilegParser::secciones( $text );
		$text = WikilegParser::limpiar( $text );

		// Create the page
		$User = User::newSystemUser( 'AntBot' );
		$Instance = MediaWikiServices::getInstance();
		$Config = $Instance->getMainConfig();
		$Title = Title::newFromText( $title );
		$timestamp = wfTimestampNow();
		$Revision = new WikiRevision( $Config );
		$Revision->setTitle( $Title );
		$Revision->setText( $text );
		$Revision->setUserObj( $User );
		$Revision->setTimestamp( $timestamp );
		$Revision->importOldRevision();
	}
}

$maintClass = WikileyPlainScript::class;
require_once RUN_MAINTENANCE_IF_MAIN;