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
 * Long piece name
 *
 * Accepts upper- or lowercase FEN letters (K/k → King / König).
 * @param string $piece single FEN piece letter
 * @param string $lang (optional)
 * @return string
 */
function mf_chess_piece_long($piece, $lang = null) {
	return wrap_reference('chess-pieces', 'long', $lang)[strtoupper($piece)] ?? '';
}

/**
 * Short piece name
 *
 * Accepts upper- or lowercase FEN letters.
 * @param string $piece single FEN piece letter
 * @param string $lang (optional)
 * @return string
 */
function mf_chess_piece_short($piece, $lang = null) {
	return wrap_reference('chess-pieces', 'short', $lang)[strtoupper($piece)] ?? '';
}

/**
 * PGN move prefix
 *
 * Accepts uppercase letters. Pawn moves use no letter, so
 * returns an empty string for P/p (not the abbr column value in the TSV).
 * @param string $piece single piece letter
 * @param string $lang (optional)
 * @return string
 */
function mf_chess_piece_pgn($piece, $lang = null) {
	if (!ctype_upper($piece)) return '';
	if ($piece === 'P') return '';
	return wrap_reference('chess-pieces', 'short', $lang)[$piece] ?? '';
}

/**
 * Unicode chess symbol
 *
 * FEN letter case selects colour: uppercase = white (♔), lowercase = black (♚).
 *
 * @param string $piece single FEN piece letter
 * @return string
 */
function mf_chess_piece_unicode($piece) {
	if (strlen($piece) === 1)
		$piece = mf_chess_piece_id($piece);
	return wrap_reference('chess-pieces-titles', 'unicode')[$piece] ?? '';
}

/**
 * SVG markup for a chess piece
 *
 * @param string $piece single FEN piece letter (case selects white/black)
 * @param string $chess_set piece set name; defaults to setting chess_piece_set
 * @return string
 * @todo not implemented
 */
function mf_chess_piece_svg($piece, $chess_set = '') {
	return '';
}

/**
 * Coloured piece title from configuration/chess-pieces-titles.tsv
 *
 * FEN letter case selects colour: uppercase = white (wK), lowercase = black (bK).
 *
 * @param string $piece single FEN piece letter
 * @param string $lang (optional)
 * @return string e.g. "white king", "schwarzer Bauer"
 */
function mf_chess_piece_title($piece, $lang = null) {
	return wrap_reference('chess-pieces-titles', 'long', $lang)[mf_chess_piece_id($piece)] ?? '';
}

/**
 * Map a FEN piece letter to a chess-pieces-titles.tsv id (wK, bP, …)
 *
 * @param string $piece single FEN piece letter
 * @return string empty when invalid
 */
function mf_chess_piece_id($piece) {
	if ($piece === '') return '';
	if (!preg_match('/^[KQRBNPkqrbnp]$/', $piece)) return '';
	$colour = ($piece === strtolower($piece)) ? 'b' : 'w';
	return $colour.strtoupper($piece);
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
