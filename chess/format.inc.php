<?php 

/**
 * chess module
 * formatting functions
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
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
