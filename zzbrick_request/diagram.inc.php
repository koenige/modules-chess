<?php

/**
 * chess module
 * convert FEN string to diagram with GIFs
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 10.12.2003, 2015-2016, 2021, 2026 Gustaf Mossakowski
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
function mod_chess_diagram($params, $settings) {
	wrap_include('format', 'chess');
	
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
	
	$alphabet = range('a', 'z');
	$field_top_right = "w";
	$field_bottom_right = "s";
	$field_count = 1;
	$fields_per_line = $settings['fields_per_line'] ?? 8;
	$letters = range('A', strtoupper($alphabet[$fields_per_line - 1]));
	foreach ($letters as $letter) $data['letters'][]['letter'] = $letter;
	
	if (!empty($settings['caption'])) $data['caption'] = $settings['caption'];

	$replace_field = "";
	for ($i = 1; $i <= $fields_per_line; $i++) {
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

	$settings['check'] = $settings['check'] ?? true;
	
	// mark pieces somehow
	$mark['white_moves'] = 'x';
	$mark['black_moves'] = 'xs';
	$mark['mark'] = 'z';
	$mark['flip'] = '180';
	$attr = [];
	foreach ($settings as $setting => $fields) {
		if (!array_key_exists($setting, $mark)) continue;
		$fields = explode(',', $fields);
		foreach ($fields as $field) {
			$field = strtolower(trim($field));
			$line = array_search(substr($field, 0, 1), $alphabet); // a-h, might extend to z
			$row = substr($field, 1); // 1-8, might extend to 99
			$attr[$row][$line][] = $mark[$setting];
		}
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
				case "1":
					$cell['src'] = "";
					$cell['alt'] = ".";
					$cell['title'] = '';
					break;
				default:
					// @todo rename pieces to use English abbreviations
					if ($piece = mf_chess_piece_short($field, 'de')) {
						$cell['src'] = (ctype_upper($field) ? 'w' : 's').$piece;
						$cell['alt'] = $field;
						$cell['title'] = mf_chess_piece_title($field);
						break;
					}
					$piece_key = strtoupper($field);
					if (!empty($settings['piece'][$piece_key])) {
						if (ctype_upper($field)) {
							$cell['src'] = "w".strtoupper($field);
							$cell['alt'] = $field;
							$cell['title'] = wrap_text('white %s',
								['values' => $settings['piece'][$piece_key], 'context' => 'Extra piece']
							);
						} else {
							$cell['src'] = "s".strtoupper($field);
							$cell['alt'] = $field;
							$cell['title'] = wrap_text('black %s',
								['values' => $settings['piece'][$piece_key], 'context' => 'Extra piece']
							);
						}
						break;
					}
					if (!$settings['check']) break;
					wrap_error(['FEN invalid: %s (Symbol %s)', ['values' => [$fen, $field]]]);
					$data['fen_invalid'] = true;
					$page['text'] = wrap_template('diagram', $data);
					return $page;
 			}
			if ($field_count === 0) {
				$cell['field'] = $field_top_right;
				$field_count = 1;
			} else {
				$cell['field'] = $field_bottom_right;
				$field_count = 0;
			}
			if (!empty($attr[$no][$j])) {
				sort($attr[$no][$j]);
				$cell['attr'] = implode('', $attr[$no][$j]);
			}			
 			$data['rows'][$no]['cells'][$j] = $cell;
		}
		$i++;
	}
	if (!empty($settings['cut'])) {
		$cut = explode('/', $settings['cut']);
		foreach ($cut as $area) {
			$area = explode('-', $area);
			$type = is_numeric($area[0]) ? 'rows' : 'lines';
			if ($type === 'lines') {
				$area[0] = array_search(strtolower($area[0]), $alphabet);
				$area[1] = array_search(strtolower($area[1]), $alphabet);
			}
			foreach ($data['rows'] as $row => $line) {
				switch ($type) {
				case 'rows':
					if ($row < $area[0] OR $row > $area[1]) {
						unset($data['rows'][$row]);
					}
					break;
				case 'lines':
					foreach ($line['cells'] as $cell_index => $cell) {
						if ($cell_index < $area[0] OR $cell_index > $area[1]) {
							unset($data['rows'][$row]['cells'][$cell_index]);
							unset($data['letters'][$cell_index]);
						}
					}
				}
			}
		}
	}
	
	$page['text'] = wrap_template('diagram', $data);
	return $page;
}
