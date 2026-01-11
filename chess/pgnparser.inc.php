<?php 

/**
 * chess module
 * show a PGN game
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

/**
 * show a single PGN game from a file
 *
 * @param string $file path of file
 * @param mixed $nos
 * @return string
 */
function show_pgn_game($file, $nos) {
	wrap_include('pgn', 'chess');
	
	$filename = sprintf('%s/%s', wrap_setting('root_dir'), $file);
	if (!file_exists($filename)) return ''; // @todo Error!

	$pgn_file = file($filename);
	$games = mf_chess_pgn_parse($pgn_file, $filename);
	foreach (array_keys($games) as $index)
		$games[$index]['moves_p'] = parse_movetext($games[$index]['moves']);

	if (!is_array($nos)) $nos = [$nos];
	$data = [];
	$index = 0;
	foreach ($nos as $no) {
		$data[$index] = $games[$no]['head'];
		if (isset($data[$index]['FEN']))
			$data[$index]['diagram'] = brick(['request', 'diagram', $data[$index]['FEN']]);
		$data[$index]['moves'] = print_moves($games[$no]['moves_p'], 0);
		$data[$index]['path'] = $file;
		$index++;
	}
	return wrap_template('chessgames', $data);
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
				$output.= show_nag($moves[$key]);
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
