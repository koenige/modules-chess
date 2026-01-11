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

	if (count($params) !== 2) return false;

	$games = explode(',', $params[1]);
	foreach (array_keys($games) as $index) --$games[$index];
	$page['text'] = show_pgn_game($params[0], $games);
	return $page;
}
