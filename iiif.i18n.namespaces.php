<?php
/**
 * @license GPL-2.0-or-later
 * @author D. Groenewegen
 */

$namespaceNames = [];

if( !defined( 'NS_IIIF' ) ) {
	define( 'NS_IIIF', 1230 );
}
if( !defined( 'NS_IIIF_TALK' ) ) {
	define( 'NS_IIIF_TALK', 1231 );
}

/** English */
$namespaceNames['en'] = [
	NS_IIIF => 'IIIF',
	NS_IIIF_TALK => 'IIIF_talk',
];
