<?php

/**
 * chess module
 * interactive multi-board PGN viewer
 *
 * Part of »Zugzwang Project«
 * https://www.zugzwang.org/modules/chess
 *
 * Ported from TYPO3 extension ek_pgn_viewer (Erik Kothe).
 *
 * @author Erik Kothe <kontakt@erikkothe.de>
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2017 Erik Kothe, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/**
 * Interactive PGN board viewer (chessboard.js + chess.js)
 *
 * Examples:
 * %%% request pgnviewer /2024/dem-u18/partien/1-live.pgn %%%
 * %%% request pgnviewer /a/partien/1-live.pgn /b/partien/1-live.pgn %%%
 *
 * @param array $params PGN URLs (site-relative or absolute)
 * @param array $settings optional brick settings
 * @return array|false $page
 */
function mod_chess_pgnviewer($params, $settings = []) {
	if (!$params) return false;

	$data = [
		'pgn_links' => [],
		'piece_theme' => $settings['piece_theme']
			?? wrap_setting('chess_pgnviewer_piece_theme'),
		'refresh_seconds' => (int) ($settings['refresh_seconds']
			?? wrap_setting('chess_pgnviewer_refresh_seconds')),
		'max_start_boards' => (int) ($settings['max_start_boards']
			?? wrap_setting('chess_pgnviewer_max_start_boards')),
		'with_jquery' => array_key_exists('with_jquery', $settings)
			? (bool) $settings['with_jquery']
			: (bool) wrap_setting('chess_pgnviewer_with_jquery'),
	];

	foreach ($params as $link) {
		$link = trim($link);
		if ($link === '') continue;
		$data['pgn_links'][] = ['url' => $link];
	}
	if (!$data['pgn_links']) return false;

	$page['head'] = wrap_template('pgnviewer-head');
	$page['text'] = wrap_template('pgnviewer', $data);
	$page['extra']['chess_pgnviewer'] = true;
	return $page;
}
