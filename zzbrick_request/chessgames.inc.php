<?php

/**
 * chess module
 * show one or more chess games
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2005-2010, 2023, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Output of games from a PGN file to HTML
 * %%% request chessgames /partien/2000/200008hb.pgn 4,3 %%%
 *
 * @param array $params
 *		[0]: path to PGN file
 *		[1]: nos. of games in file, comma separated list
 * @return array $page
 */
function mf_chess_chessgames($params) {
	wrap_include('pgnparser', 'chess');
	wrap_include('pgn', 'chess');

	if (count($params) !== 2) return false;
	$nos = explode(',', $params[1]);
	foreach (array_keys($nos) as $index) --$nos[$index];

	$filename = sprintf('%s/%s', wrap_setting('root_dir'), $params[0]);
	if (!file_exists($filename)) {
		$page['text'] = ' ';
		return $page; // @todo Error!
	}

	$pgn_file = file($filename);
	$games = mf_chess_pgn_parse($pgn_file, $filename);
	foreach (array_keys($games) as $index)
		$games[$index]['moves_p'] = mf_chess_pgnparse_moves($games[$index]['moves']);

	$data = [];
	$index = 0;
	foreach ($nos as $no) {
		if (!array_key_exists($no, $games)) {
			wrap_error(wrap_text('Unable to find game %s in file %s',
				['values' => [$no, $filename]]
			), E_USER_WARNING);
			continue;
		}
		$data[$index] = $games[$no]['head'];
		$data[$index]['no'] = $no + 1;
		if (isset($data[$index]['FEN']))
			$data[$index]['diagram'] = brick(['request', 'diagram', $data[$index]['FEN']]);
		$data[$index]['moves'] = print_moves($games[$no]['moves_p'], 0);
		$data[$index]['path'] = $params[0];
		$index++;
	}
	$page['text'] = wrap_template('chessgames', $data);
	return $page;
}

function print_moves($game_moves, $level) {
	$output = '';
	$output.= '<dl><dt>';
	$space = ' ';
	$i = 0;
	if ($level > 1) {
		$output.= '(';
		$space = '';
	}
	$dotmov = false;
	if ($level) $dotmov = true;
	foreach ($game_moves as $move_num => $moves) {
		foreach (array_keys($moves) as $key) {
			if ($key == 'white') {
				$output.= ' '.$move_num.'.';
				$dotmov = false;
				$there_are_moves = true; // es wurden zuege ausgegeben
			}
			if (strstr($key, 'comment')) {
				$output.= '</dt>'."\n".'<dd';
				if (!$move_num && !$level) $output.= ' class="first"';
				$output.= '>'.$moves[$key].' </dd>'."\n".'<dt>';
				$dotmov = true;
			} elseif (strstr($key, 'variant')) {
				$output.= '</dt>'."\n".'<dd';
				if (!$move_num) $output.= ' class="first"';
				$output.= '>';
				$output.= print_moves($moves[$key], $level+1).' </dd>'."\n".'<dt>';
				$dotmov = true;
			} elseif (strstr($key, 'NAG')) {
				$output .= mf_chess_pgn_nag($moves[$key]).' ';
			} else {
				if ($dotmov OR (!$i && empty($there_are_moves))) $output.= $move_num.'... ';
				$output.= $space.mf_chess_pgn_translate_pieces($moves[$key], 'de');
				$space = ' ';
			}
			$i++;
		}
	}
	if ($level > 1) $output.= ')';
	$output.= '</dt></dl>';
	return $output;
}
