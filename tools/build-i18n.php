<?php
/**
 * Translation build for Duke Yin Helper.
 *
 * The .po file is the single source of truth. This script derives the two
 * artefacts WordPress actually reads at runtime:
 *
 *   languages/duke-yin-helper-zh_CN.mo
 *       Used for PHP strings, and for the block.json title/description/keywords.
 *       Those are looked up with the gettext contexts "block title",
 *       "block description" and "block keyword" (see wp-includes/block-i18n.json),
 *       so a plain msgid does not translate a block title.
 *
 *   languages/duke-yin-helper-zh_CN-dukeyin-blocks-editor.json
 *       The editor bundle's script translations. wp_set_script_translations()
 *       is called with an explicit $path, so the file must be named
 *       {domain}-{locale}-{handle}.json.
 *
 * Usage:  php tools/build-i18n.php
 *
 * @package Duke_Yin_Helper
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( "Run this from the command line.\n" );
}

$root   = dirname( __DIR__ );
$domain = 'duke-yin-helper';
$locale = 'zh_CN';
$handle = 'dukeyin-blocks-editor';

$languages = $root . '/languages';
$po_file   = $languages . '/' . $domain . '-' . $locale . '.po';

/*
 * Reuse WordPress's own gettext implementation rather than parsing .po by hand:
 * it already handles obsolete (#~) entries, continuations and escaping.
 */
$pomo = null;

foreach ( array( dirname( $root, 3 ), dirname( $root, 4 ) ) as $candidate ) {
	$dir = $candidate . '/wp-includes/pomo';
	if ( is_readable( $dir . '/mo.php' ) ) {
		$pomo = $dir;
		break;
	}
}

if ( null === $pomo ) {
	fwrite( STDERR, "Could not locate wp-includes/pomo. Run this from inside a WordPress install.\n" );
	exit( 1 );
}

require_once $pomo . '/translations.php';
require_once $pomo . '/po.php';
require_once $pomo . '/mo.php';

if ( ! is_readable( $po_file ) ) {
	fwrite( STDERR, "Cannot read {$po_file}\n" );
	exit( 1 );
}

/* -------------------------------------------------------------------------
 * 1. Compile the .mo.
 * ---------------------------------------------------------------------- */

$po = new PO();

if ( true !== $po->import_from_file( $po_file ) ) {
	fwrite( STDERR, "Could not parse {$po_file}\n" );
	exit( 1 );
}

$mo = new MO();

if ( ! empty( $po->headers ) ) {
	$mo->set_headers( $po->headers );
}

$compiled = 0;

foreach ( $po->entries as $entry ) {
	// Skip the header entry and anything without a translation.
	if ( '' === $entry->singular || empty( $entry->translations ) ) {
		continue;
	}

	$mo->add_entry( $entry );
	$compiled++;
}

$mo_file = $languages . '/' . $domain . '-' . $locale . '.mo';

if ( true !== $mo->export_to_file( $mo_file ) ) {
	fwrite( STDERR, "Could not write {$mo_file}\n" );
	exit( 1 );
}

printf( "Wrote %s (%d entries)\n", $mo_file, $compiled );

/* -------------------------------------------------------------------------
 * 2. Build the editor script translations.
 *
 * Only strings that really occur in the bundle are exported, which is what the
 * upstream `wp i18n make-json` command does.
 * ---------------------------------------------------------------------- */

$js_file = $root . '/includes/blocks/assets/blocks.js';

if ( ! is_readable( $js_file ) ) {
	fwrite( STDERR, "Cannot read {$js_file}\n" );
	exit( 1 );
}

preg_match_all(
	'~(?:__|_x|esc_html__|esc_attr__)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*,\s*(?:\'[^\']*\'\s*,\s*)?\'' . preg_quote( $domain, '~' ) . '\'\s*\)~',
	file_get_contents( $js_file ),
	$matches
);

$js_strings = array_values( array_unique( $matches[1] ) );
sort( $js_strings );

$messages = array();
$missing  = array();

foreach ( $js_strings as $string ) {
	// The bundle uses plain __(), so only entries without a context apply.
	if ( isset( $po->entries[ $string ] ) && ! empty( $po->entries[ $string ]->translations ) ) {
		$messages[ $string ] = array( $po->entries[ $string ]->translations[0] );
	} else {
		$missing[] = $string;
	}
}

$json = array(
	'translation-revision-date' => gmdate( 'Y-m-d\TH:i:s+00:00' ),
	'generator'                => 'Duke Yin Helper',
	'domain'                   => $domain,
	'source'                   => 'includes/blocks/assets/blocks.js',
	'locale_data'              => array(
		$domain => array_merge(
			array(
				'' => array(
					'domain'       => $domain,
					'lang'         => $locale,
					'plural-forms' => 'nplurals=1; plural=0;',
				),
			),
			$messages
		),
	),
);

$json_file = $languages . '/' . $domain . '-' . $locale . '-' . $handle . '.json';

file_put_contents(
	$json_file,
	json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n"
);

printf( "Wrote %s (%d of %d strings)\n", $json_file, count( $messages ), count( $js_strings ) );

/* -------------------------------------------------------------------------
 * 3. Report gaps.
 * ---------------------------------------------------------------------- */

if ( $missing ) {
	echo "\nUntranslated strings used by the editor bundle:\n";
	foreach ( $missing as $string ) {
		echo "  - {$string}\n";
	}
	echo "\nAdd them to {$po_file} and run this script again.\n";
	exit( 1 );
}

echo "All editor strings translated.\n";
