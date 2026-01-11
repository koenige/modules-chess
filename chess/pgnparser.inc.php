<?php 

/**
 * chess module
 * parse a PGN game
 * PGN = Portable Game Notation for chess games
 *
 * Part of »Zugzwang Project«
 * http://www.zugzwang.org/modules/chess
 *
 * @author Gustaf Mossakowski <gustaf@koenige.org>
 * @copyright Copyright © 2005-2010, 2023, 2026 Gustaf Mossakowski
 * @license http://opensource.org/licenses/lgpl-3.0.html LGPL-3.0
 */


/*

	http://search.cpan.org/~gmax/Chess-PGN-Parse/Parse.pm
	http://search.cpan.org/~hsmyers/Chess-PGN-EPD-0.21/EPD.pm
	
	http://www.taschenorakel.de/michael/projects/chessviewer/

	PGN parser
	File in iso-8859-1
	read only export format (probably)
	
	; Comment to EOL
	{ Comment }
	{ { Comment }
	{ ; Comment }
	; { Co} mment
	
	% Escape - rest of line ignored

	.	token, some delimited by whitespace, some self delimited
	[ \n\t]	whitespace allowed

	"[all ]*"		empty string token
	"bla"	string token
	"\""	
	"\\"	no newline, tab inside string, max 255 char

	[0-9]+	integer token, self terminating
	.		period (move number indications)
	*		asterisk (incomplete game, unknown result)
	[ und ]	delimit tag pairs
	( und )	RAV
	< und >	currently no meaning
	$[0-9]+	NAG Numeric Annotation Glyph
	A-Za-z0-9_+#=:-	symbol token
	
	
	tag pair section
	movetext section
		enumerated, poss. annotated moves, game termination marker (SAN Standard Algebraic Notation)


	tag pair
	[symbol_token string_token] 	- tag name: tag value
		tag name: [a-zA-Z_] (maybe extended Latin 1?)
		tag names for archival storage: begin with Uppercase letter
		tag value: ":" used as a seperator for several values
		
	Seven Tag Roster STR (order has to be like this)
	
	Event
	Site
	Date
	Round
	White
	Black
	Result


W Bob Doe
S Eva Doe
DM Schnellschach Höckendorf, 16.04.2004
(D00, Kommentare: Doe, J.)

1. d4 d5 

*/

function show_nag($my_nag) {
	static $pgn = [];
	if (!$pgn) $pgn = mf_chess_pgn_basics();
	
	$my_nag = substr($my_nag, 1); // strip $
	return $pgn['NAG'][$my_nag]['CSM'].' ';
}

function parse_comments($comments) {
	foreach (array_keys($comments) as $key) {
		if (substr($comments[$key],0,5) == '# FEN') {
			preg_match('/# FEN "(.+?)"/', $comments[$key], $board);
			$comments[$key] = preg_replace('/# FEN "(.+?)"/', '', $comments[$key]);
			$return = brick_format('%%% request diagram '.$board[1].' %%%');
			if ($return['text'])
				$comments[$key] = $return['text'].$comments[$key];
		}
	}
	return $comments;
}

function parse_movetext($movetext, $comments = [], $variants = []) {
	static $pgn = [];
	if (!$pgn) $pgn = mf_chess_pgn_basics();

	$moves = '';
	if (is_array($movetext)) 
		foreach ($movetext as $move)
			$moves .= ' '.$move;
	else $moves = $movetext;
	if (!$comments) {
		preg_match_all('/{(.+?)}/', $moves, $mycomments);
		//$moves = preg_replace('/{(.+?)}/', 'comment', $moves);
		foreach ($mycomments[1] as $comment)
			$comments[] = $comment;
		if ($comments)
			foreach ($comments as $key => $tree)
				$moves = str_replace('{'.$tree.'}', 'comment'.$key, $moves);
	}
	if ($comments) $comments = parse_comments($comments);
	if (!$variants) {
		$variant_tree[1] = true;
		$marker = 0;
		$variants = [];
		while ($variant_tree[1] == true) {
			preg_match_all('/\(([^(]+?)\)/', $moves, $variant_tree);
			foreach ($variant_tree[1] as $tree)
				$variants[] = $tree;
			if ($variants)
				foreach ($variants as $key => $tree)
					$moves = str_replace('('.$tree.')', 'variant'.$key, $moves);
			$marker++;
			if ($marker == 10) break;
		}
	}

	$moveparts = explode(' ', $moves);
	$movenum = false;
	//$variants = parse_comments($variants[1]);
	$nag_index = 0;
	$var_index = 0;
	$com_index = 0;
	$startcolor = false;
	$move_a = [];
	foreach ($moveparts as $movepart)
		if ($movepart) {
			if (preg_match('/(\d+)\.+/', $movepart, $movenumb)) {
				if (preg_match('/\d+\.\./', $movepart) && !$movenum) $startcolor = 'black';
				else $startcolor = false;
				$movenum = $movenumb[1];
				if(!isset($move_a[$movenum])) $nag_index = 0;
				if(!isset($move_a[$movenum])) $var_index = 0;
				if(!isset($move_a[$movenum])) $com_index = 0;
			} elseif (substr($movepart, 0, 1) == '$') {
				$color = 'NAG'.$nag_index;
				$nag_index++;
				$move_a[$movenum][$color] = $movepart;
			} elseif (substr($movepart,0,7) == 'comment') {
				$color = 'comment'.$com_index;
				$i = substr($movepart,7);
				$com_index++;
				$move_a[$movenum][$color] = $comments[$i];
			} elseif (substr($movepart,0,7) == 'variant') {
				$j = substr($movepart,7);
				$color = 'variant'.$var_index;
				$var_index++;
				$move_a[$movenum][$color] = parse_movetext($variants[$j], $comments, $variants);
			} else {
				if (!isset($move_a[$movenum]))
					if (!$movenum) $color = 'comment'.$com_index;
					elseif ($startcolor == 'black') $color = 'black';
					else $color = 'white';
				elseif (isset($move_a[$movenum]['white'])) $color = 'black';
				if (in_array($movepart, $pgn['game_endings'])) {
					//$move_a['result'] = $movepart; // uninteresting, we have that already
				} else
					$move_a[$movenum][$color] = $movepart;
			}
		}
	return $move_a;

}
