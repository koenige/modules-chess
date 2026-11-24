# Chess Module

Displaying chess boards and games in Zugzwang Project CMS.

## What is this repository for?

Integrate chess positions via FEN strings or chess games via PGN.

## Interactive PGN viewer

Multi-board viewer (ported from TYPO3 `ek_pgn_viewer` by Erik Kothe). Uses
`chessboard.js` and `chess.js` from `/_behaviour/chessboardjs` and
`/_behaviour/chess.js`.

Live games need the jQuery countdown timer at tag `1.0.8`, checked out
under `/_behaviour/jquery-countdownTimer` (from the site root):

```
git submodule add git@github.com:harshen/jQuery-countdownTimer www/_behaviour/jquery-countdownTimer
git -C www/_behaviour/jquery-countdownTimer checkout 1.0.8
```

```
%%% request pgnviewer /2024/dem-u18/partien/1-live.pgn %%%
%%% request pgnviewer /a/partien/1-live.pgn /b/partien/1-live.pgn %%%
```

Does not replace the tournament live pages that still use `ltpgnviewer` /
`pgn4web`.

## How do I get set up?

* Install as a Module in folder `_inc/modules/chess`

## Who do I talk to?

* Gustaf Mossakowski <gustaf@koenige.org>
