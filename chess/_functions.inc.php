<?php 

/**
 * chess module
 * common functions
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2021 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


function mf_chess_pgn_basics() {
	$NAGs = file(+__DIR__.'/../configuration/NAG.txt');
	$i = 0;
	foreach ($NAGs as $line) {
		$line = explode("\t", $line);
		$pgn['NAG'][$line[0]]['CSM'] = $line[1]; 
		$pgn['NAG'][$line[0]]['Symbol'] = $line[2]; 
		$pgn['NAG'][$line[0]]['Group'] = $line[3]; 
		//$pgn['NAG'][$line[0]]['Description'] = $line[4]; 
	}
	$pgn['game_endings'] = ['1-0', '0-1', '1/2-1/2', '*'];
	return $pgn;
}
