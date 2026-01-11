<?php 

/**
 * chess module
 * formatting functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * wrap PGN move text
 *
 * @param string $result
 * @return string
 */
function mf_chess_pgn_wordwrap($string) {
	return wordwrap($string, 79);
}

/**
 * convert a PGN date into an ISO 8601 date
 *
 * @param string $date
 * @return string
 */
function mf_chess_pgn_date($date) {
	$iso = str_replace('.', '-', $date);
	$iso = str_replace('??', '00', $iso);
	return $iso;
}

/**
 * convert a PGN date into an ISO date
 *
 * @param string $date
 * @return string
 */
function mf_chess_pgn_date_localized($date) {
	return wrap_date(mf_chess_pgn_date($date));
}

/**
 * convert a Numeric Annotation Glyph (NAG) into a symbol
 *
 * @param string $nag
 * @return string
 */
function mf_chess_pgn_nag($nag) {
	static $definitions = [];
	if (!$definitions)
		$definitions = wrap_tsv_parse('NAG', 'chess');
	
	$key = substr($nag, 1); // strip $
	return $definitions[$key]['CSM'] ?? $nag;
}

/**
 * format a name of a player
 *
 * @param string $name
 * @return string
 */
function mf_chess_pgn_name($name) {
	if (!wrap_setting('chess_pgn_name_first_last')) return $name;
	$name = explode(',', $name);
	$name = trim(implode(' ', array_reverse($name)));
	return $name;
}
