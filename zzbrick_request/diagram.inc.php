<?php

/**
 * chess module
 * convert FEN string to diagram with GIFs
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 10.12.2003, 2015-2016, 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Output HTML code for chess diagram described by FEN
 * (Forsythe-Edwards-Notation)
 *
 * Examples:
 * [FEN "8/5pk1/4p1pp/3pP3/2pP4/2P3P1/1r3PKP/R7 b - - 0 1"]
 * FEN: ...
 *
 * @param string $fen valid FEN string
 * @return string HTML code for diagram
 * @todo use preg_match() to check whether string is valid
 */
function mod_chess_diagram($params) {
	$fen = implode(' ', $params);

	// cleanup @todo redirect
	$fen = trim($fen);
	$fen = str_replace('&#8203;', '', $fen);
	// remove "" beginning and end
	if (substr($fen, 0, 1) === '"') $fen = substr($fen, 1);
	if (substr($fen, -1) === '"') $fen = substr($fen, 0, -1);
	if (substr($fen, 0, 6) === '[FEN "' AND substr($fen, -2) === '"]') {
		$fen = substr($fen, 6, -2);
	} elseif (substr($fen, 0, 5) === 'FEN: ') {
		$fen = substr($fen, 5, -1);
	}

	$fen_array = explode(" ", $fen);
	if (count($fen_array) > 6) return false;
	$position = $fen_array[0];
	$to_move = isset($fen_array[1]) ? $fen_array[1] : false;
	$data['castling'] = isset($fen_array[2]) ? $fen_array[2] : false;
	$data['en_passant'] = isset($fen_array[3]) ? $fen_array[3] : false;
	$data['halfmoves'] = isset($fen_array[4]) ? $fen_array[4] : false;
	$data['moves'] = isset($fen_array[5]) ? $fen_array[5] : false;
	$data['fen'] = $fen;
	
	// allgemeine Definitionen
	
	$field_top_right = "w";
	$field_bottom_right = "s";
	$field_count = 1;
	$fields_per_line = 8; // muss auch irgendwo anders herkommen

	$replace_field = "";
	for ($i = 1; $i <= 8; $i++) {
		$replace_field .= "1";
		$position = str_replace($i, $replace_field, $position);
	}

	$lines = explode("/", $position);
	$lines_count = count($lines);	// Anzahl der Zeilen
	$i = 0;
	
	switch ($to_move) {
		case 'w': $data['white_to_move'] = true; break;
		case 'b': $data['black_to_move'] = true; break;
		default: break; // @todo errorhandling
	}

	$data['board'] = '';
	foreach ($lines as $line) {
		$no = $lines_count - $i;
		// Felder umdrehen am anfang jeder zeile
		if ($field_count === 1) $field_count = 0;
		else $field_count = 1;
		
		$data['rows'][$no]['no'] = $no;

		for ($j = 0; $j < $fields_per_line; $j++) {
			$cell = [];
			$field = substr($line, $j, 1);
			if ($j === $fields_per_line - 1) {
				$cell['class'] = 'last-child';
			}

			switch ($field) {
				case "K": $cell['src'] = "wK"; $cell['alt'] = "K"; $cell['title'] = 'weißer König'; break;
				case "Q": $cell['src'] = "wD"; $cell['alt'] = "D"; $cell['title'] = 'weiße Dame'; break;
				case "R": $cell['src'] = "wT"; $cell['alt'] = "T"; $cell['title'] = 'weißer Turm'; break;
				case "B": $cell['src'] = "wL"; $cell['alt'] = "L"; $cell['title'] = 'weißer Läufer'; break;
				case "N": $cell['src'] = "wS"; $cell['alt'] = "S"; $cell['title'] = 'weißer Springer'; break;
				case "P": $cell['src'] = "wB"; $cell['alt'] = "B"; $cell['title'] = 'weißer Bauer'; break;
				case "k": $cell['src'] = "sK"; $cell['alt'] = "k"; $cell['title'] = 'schwarzer König'; break;
				case "q": $cell['src'] = "sD"; $cell['alt'] = "d"; $cell['title'] = 'schwarze Dame'; break;
				case "r": $cell['src'] = "sT"; $cell['alt'] = "t"; $cell['title'] = 'schwarzer Turm'; break;
				case "b": $cell['src'] = "sL"; $cell['alt'] = "l"; $cell['title'] = 'schwarzer Läufer'; break;
				case "n": $cell['src'] = "sS"; $cell['alt'] = "s"; $cell['title'] = 'schwarzer Springer'; break;
				case "p": $cell['src'] = "sB"; $cell['alt'] = "b"; $cell['title'] = 'schwarzer Bauer'; break;
				case "1": $cell['src'] = ""; $cell['alt'] = "."; $cell['title'] = ''; break;
				default:
					$output = '<p class="error">FEN nicht gültig</p>';
					wrap_error(sprintf('FEN nicht gültig: %s', $fen));
					return $output;
 			}
			if ($field_count === 0) {
				$cell['field'] = $field_top_right;
				$field_count = 1;
			} else {
				$cell['field'] = $field_bottom_right;
				$field_count = 0;
			}
			
 			$data['rows'][$no]['cells'][$j] = $cell;
		}
		$i++;
	}
	$page['text'] = wrap_template('diagram', $data);
	return $page;
}
