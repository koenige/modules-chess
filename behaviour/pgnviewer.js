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

/* Init */
var board = [];
var gameHistory = [];
var currentPly = [];
var game = {};
var pgnData = [];
var gamesTurnier = [];
var gameNotation = [];
var turnier = '';
var partie = '';
var clocks = [];
var chessPgnViewerConfig = window.chessPgnViewerConfig || {};
var refreshrate = chessPgnViewerConfig.refreshSeconds || 60;
var maxStartBoards = chessPgnViewerConfig.maxStartBoards || 12;
var pieceTheme = chessPgnViewerConfig.pieceTheme
	|| '/_behaviour/chessboardjs/releases/0.3.0/img/chesspieces/wikipedia/{piece}.png';
var pgnurl = '';
var colselect = 'col-md-3 col-sm-3 col-xs-3';
var teamPaarings = [];
var teamExists = false;

function chessPgnViewerTrack(category, action, label) {
	if (typeof ga === 'function') {
		ga('send', 'event', category, action, label);
	}
}

$( document ).ready(function() {
	pgnlink.forEach(function(item){
		pgnFromUrl(item.url, item.id);
	});
	//getAjaxRequest(false);
	buildSelectGame();
	boardResizeInit();
	//buildSelectTurnier(); //Auskommentiert für die Mannschaften
	if(teamExists) buildSelectTeamPaarings();
	notationInit();
	var i = 0;
	for(var key in gamesTurnier) {
	 gamesTurnier[key].forEach(function(item){
		 if(i >= maxStartBoards){
 			return;
 		}
 		i++;
		loadAlterklasse(item);
	 });
	 getAjaxRequest(false);
	 return;
	}
});

/* Main Functions Begin */
function notationInit(){
	jQuery("#notation").on("change",function(){
		if(jQuery(this).prop('checked')==false){
			jQuery('#board').addClass('nonotation');
		}else{
			jQuery('#board').removeClass('nonotation');
		}
	});
}

function buildSelectGame(){
	var selectOption = {};
	for(var key in pgnData) {
		var eventname = pgnData[key]['Event'];
		 if(selectOption[eventname] == undefined){
			selectOption[eventname] = '';
		 }
		 var result = pgnData[key]['Result'];
		 if(result == '*'){
			 result = 'Live';
		 }
		 if(pgnData[key]['WhiteTeam'] != '' && pgnData[key]['BlackTeam']){
			 selectOption[eventname] += '<option value="'+key+'">'+pgnData[key]['White']+' ('+ pgnData[key]['WhiteTeam'] +') - '+pgnData[key]['Black']+' ('+ pgnData[key]['BlackTeam'] +') : '+result+'</option>';
		 }else{
			 selectOption[eventname] += '<option value="'+key+'">'+pgnData[key]['White']+' - '+pgnData[key]['Black']+' : '+result+'</option>';
		 }
	}
	var selectOptionHtml = '';
	for(var key in selectOption) {
		selectOptionHtml += '<optgroup label="'+key+'">'+selectOption[key]+'</optgroup>';
	}
	selectOptionHtml = '<select class="" id="gameSelect" multiple="multiple">'+selectOptionHtml+'</select>';

	jQuery('#gameSelectCont').html(selectOptionHtml);

	jQuery('#gameSelect').on('change',function(){
		jQuery('.boarditem').hide();
		if(jQuery(this).val() != null){
			jQuery(this).val().forEach(function(item){
				newBoard(item);
				jQuery('#boardcont'+item).show();
				board[item].resize();

			});
			chessPgnViewerTrack('GameChange', 'Change', '');
		}
	});
	$("#gameSelect").chosen({width: "95%",placeholder_text_multiple:"Partienauswahl"});
}

function buildSelectTurnier(){
	var selectOption = '<option>Alterklasse wählen</option>';
	var i=0;
	for(var key in gamesTurnier) {
		if(i==0){
			selectOption += '<option value="'+key+'" selected="selected">'+key+'</option>';
		}else{
			selectOption += '<option value="'+key+'">'+key+'</option>';
		}
		i++;
	}
	selectOption = '<select id="turnierSelect">'+selectOption+'</select>';
	jQuery('#turnierSelectCont').html(selectOption);

	jQuery('#turnierSelect').on('change',function(){
		if(jQuery(this).val() != null){
			jQuery('.boarditem').hide();
			gamesTurnier[jQuery(this).val()].forEach(function(item){
				loadAlterklasse(item);
			});
			chessPgnViewerTrack('AlterklasseChange', 'Change', jQuery(this).val());
		};
	});
	$("#turnierSelect").chosen({disable_search:true,width: "90%"});
}

function buildSelectTeamPaarings(){
	var selectOption = '<option>Begegnung wählen</option>';
	var i=0;
	for(var key in teamPaarings) {
		if(i==0){
			selectOption += '<option value="'+key+'" selected="selected">'+key+'</option>';
		}else{
			selectOption += '<option value="'+key+'">'+key+'</option>';
		}
		i++;
	}
	selectOption = '<select id="teamPaaringsSelect">'+selectOption+'</select>';
	jQuery('#teamPaaringsSelectCont').html(selectOption);

	jQuery('#teamPaaringsSelect').on('change',function(){
		if(jQuery(this).val() != null){
			jQuery('.boarditem').hide();
			teamPaarings[jQuery(this).val()].forEach(function(item){
				loadAlterklasse(item);
			});
			chessPgnViewerTrack('teamPaaringChange', 'Change', jQuery(this).val());
		};
	});
	$("#teamPaaringsSelect").chosen({disable_search:true,width: "90%"});
}

function loadAlterklasse(item){
	newBoard(item);
	jQuery('#boardcont'+item).show();
	board[item].resize();
}

function pgnFromUrl(url, linkId) { 			// analyzes the imported PGN file
	var pgn = getURL(url);
	pgnurl = url;
  if(pgn !== undefined){
	  pgn = pgn.split(/[\r\n]+/g);
	  var i,m=0,z=0,gameid=false,pgnDataTemp=[];
	  for(i=0;i<pgn.length; i++){
			if(pgn[i].match(/\[ePGN /gi) != null){
				continue;
			}
	    if(pgn[i].match(/\[Event /gi) != null){
				/* TODO gameid wiederherstellbar machen */
	      gameid = 'pgn' + m;
				pgnDataTemp[gameid] = new Array();
	      m++;
	      z=0;
	      pgnDataTemp[gameid]['PGN'] = new Array();
	    }

			if(pgn[i].match(/\[Event /gi) != null)			pgnDataTemp[gameid]['Event'] 			= prozessLine(pgn[i], 'Event');
			if(pgn[i].match(/\[White /gi) != null)			pgnDataTemp[gameid]['White'] 			= prozessLine(pgn[i], 'White');
			if(pgn[i].match(/\[Black /gi) != null)			pgnDataTemp[gameid]['Black'] 			= prozessLine(pgn[i], 'Black');
			if(pgn[i].match(/\[Result /gi) != null)			pgnDataTemp[gameid]['Result'] 		= prozessLine(pgn[i], 'Result');
			if(pgn[i].match(/\[WhiteTeam /gi) != null)	pgnDataTemp[gameid]['WhiteTeam'] 	= prozessLine(pgn[i], 'WhiteTeam');
			if(pgn[i].match(/\[BlackTeam /gi) != null)	pgnDataTemp[gameid]['BlackTeam'] 	= prozessLine(pgn[i], 'BlackTeam');
			if(pgn[i].match(/\[Round /gi) != null) 			pgnDataTemp[gameid]['Round'] 			= prozessLine(pgn[i], 'Round');
			if(pgn[i].match(/\[Board /gi) != null)			pgnDataTemp[gameid]['Board'] 			= prozessLine(pgn[i], 'Board');

	    if(pgn[i].match(/^\[/gi) != null){
	      pgnDataTemp[gameid]['PGN'][z] = pgn[i];
	      z++;
	    }else{
	      if(pgn[i] != '' && pgnDataTemp[gameid] !== undefined){
	        if(pgnDataTemp[gameid]['PGN'][z] === undefined){
	          pgnDataTemp[gameid]['PGN'][z] = pgn[i];
	        }else{
	          pgnDataTemp[gameid]['PGN'][z] += " " + pgn[i];
	        }
	      }
	    }
	  }
		var position = 0;
		for (var k in pgnDataTemp){
			if (pgnDataTemp.hasOwnProperty(k)) {
				position++;
				var temp = pgnDataTemp[k]['PGN'].join('\n');
		  	//console.log(temp.split(/\d+\.\s/g));
				var matches = temp.match(/"\d+:\d+:\d+"/g);

				if(matches != null){
					if(matches.length%2==0){
						pgnDataTemp[k]['BlackClock'] = matches.pop().replaceAll('"', '');
						pgnDataTemp[k]['WhiteClock'] = matches.pop().replaceAll('"', '');
					}else{
						pgnDataTemp[k]['WhiteClock'] = matches.pop().replaceAll('"', '');
						pgnDataTemp[k]['BlackClock'] = matches.pop().replaceAll('"', '');
					}
				}
				var gameidTemp = pgnGameId(linkId, pgnDataTemp[k], position);
				pgnData[gameidTemp] = pgnDataTemp[k];

				if(gamesTurnier[pgnDataTemp[k]['Event']] === undefined){
					gamesTurnier[pgnDataTemp[k]['Event']] = [];
				}
				gamesTurnier[pgnDataTemp[k]['Event']].push(gameidTemp);

				if(pgnDataTemp[k]['Board'] != undefined && pgnDataTemp[k]['WhiteTeam'] != undefined){
					var paaring;
					// Schauen nach dem dem Board Nummer um die Brettfarbe richtig zuzuweisen

					paaring = pgnDataTemp[k]['WhiteTeam'] + ' - ' + pgnDataTemp[k]['BlackTeam'];
					if((pgnDataTemp[k]['BlackTeam'] + ' - ' + pgnDataTemp[k]['WhiteTeam']) in teamPaarings){
						paaring = pgnDataTemp[k]['BlackTeam'] + ' - ' + pgnDataTemp[k]['WhiteTeam'];
						teamPaarings[paaring].push(gameidTemp);
					}else if(paaring in teamPaarings){
						teamPaarings[paaring].push(gameidTemp);
					}else	if(teamPaarings[paaring] === undefined){
						teamPaarings[paaring] = [];
						teamPaarings[paaring].push(gameidTemp);
					}

					// if(pgnDataTemp[k]['Board'] % 2 == 0){
					// 	paaring = pgnDataTemp[k]['WhiteTeam'] + ' - ' + pgnDataTemp[k]['BlackTeam']
					// }else{
					// 	paaring = pgnDataTemp[k]['BlackTeam'] + ' - ' + pgnDataTemp[k]['WhiteTeam']
					// }
					// if(teamPaarings[paaring] === undefined){
					// 	teamPaarings[paaring] = [];
					// }
					// teamPaarings[paaring].push(gameidTemp);
					teamExists = true; // Aktiviert das Select-Feld
				}
				//newBoard(gameidTemp);
		  }
		}
	}
}

/*
 * game id from link id, round and board, e.g. 2024-dem-u18-4-3-2;
 * used in element ids and selectors, so only letters, digits, - and _
 */
function pgnGameId(linkId, gameData, position) {
	var parts = [linkId, gameData['Round'] || '', gameData['Board'] || position];
	return parts.join('-').replace(/[^A-Za-z0-9_-]+/g, '-').replace(/-+$/, '');
}

function newBoardFromHTML(gameid, pgn){
  /* TODO: Hier muss noch ein laden von einer URL möglich sein*/
  pgn = Object.keys(pgn).map(function (key) { return pgn[key]; });
  pgnData[gameid] = pgn;

  newBoard(gameid);
}

function loadGame(gameid) {
	game[gameid] = new Chess();
	game[gameid].load_pgn(pgnData[gameid]["PGN"].join('\n'), {newline_char:'\n',sloppy:true});
	$("#game"+gameid+"-dataTop").html(getGametextTop(game[gameid], gameid));
	$("#game"+gameid+"-data").html(getGametextButton(game[gameid], gameid));
	gameHistory[gameid] = game[gameid].history({verbose: true});
	goToMove(1000, gameid);
}

function newBoard(gameid){
	//url = siteurl+turnier+'/partien/'+partie+'.json';
	if(typeof board[gameid] == 'undefined'){
		/* Hier könnte der Ajax-Request kommen */

		/* HTML bauen */
		var html = "<div class='col "+ colselect +" boarditem' id='boardcont"+gameid+"'><div class='boardtexttop' id='game"+gameid+"-dataTop'></div><div class='boardview' id='board"+gameid+"'></div>";
		html += "<div class='boardtextbuttom' id='game"+gameid+"-data'></div>";
		html += "<div id='game"+gameid+"-scorecont'><span id='game"+gameid+"-score'></span><span id='game"+gameid+"-depth'></span><span id='game"+gameid+"-bestmove'></span><span id='game"+gameid+"-proposal'></span></div>";
		html += "<div class='boardbutton' id='board"+gameid+"-buttons'><button title='zum Anfang' type='button' class='btn btn-default btnStart' rel='"+gameid+"'>|<</button>";
		html += "<button title='vorheriger Zug' type='button' class='btn btn-default btnPrevious' rel='"+gameid+"'><</button>";
		html += "<button title='nächster Zug' type='button' class='btn btn-default btnNext' rel='"+gameid+"'>></button>";
		html += "<button title='zum Ende' type='button' class='btn btn-default btnEnd' rel='"+gameid+"'>>|</button><a title='Brett schlie&szlig;en' class='boardclose' rel='"+gameid+"'>Brett schlie&szlig;en</a></div>";
		html += "</div>";

		$("#board").append(html);

		//set up the board
		var cfg = {
			pieceTheme: pieceTheme,
		  position: 'start',
		  showNotation: false,
		  onChange: onChange ,
		  gameid: gameid
		};

		board[gameid] = new ChessBoard('board'+gameid, cfg);
		loadGame(gameid);

		$('#board'+gameid+'-buttons .btnStart').on('click', function() {
			game[$(this).attr("rel")].reset();
			currentPly[$(this).attr("rel")] = -1;
			board[$(this).attr("rel")].position(game[$(this).attr("rel")].fen());
			jQuery(".gameMoves-"+$(this).attr("rel")+" > span").removeClass("curmove");
			jQuery(".gameMove0-"+$(this).attr("rel")).addClass("curmove");
		});

		$('#board'+gameid+'-buttons .btnPrevious').on('click', function() {
			if (currentPly[$(this).attr("rel")] >= 0) {
				game[$(this).attr("rel")].undo();
				currentPly[$(this).attr("rel")]--;
				board[$(this).attr("rel")].position(game[$(this).attr("rel")].fen());
				jQuery(".gameMoves-"+$(this).attr("rel")+" > span").removeClass("curmove");
				jQuery(".gameMove"+currentPly[$(this).attr("rel")]+"-"+$(this).attr("rel")).addClass("curmove");
			}
		});

		$('#board'+gameid+'-buttons .btnNext').on('click', function() {
			if (currentPly[$(this).attr("rel")] < gameHistory[$(this).attr("rel")].length - 1) {
				currentPly[$(this).attr("rel")]++;
				game[$(this).attr("rel")].move(gameHistory[$(this).attr("rel")][currentPly[$(this).attr("rel")]].san);
				board[$(this).attr("rel")].position(game[$(this).attr("rel")].fen());
				jQuery(".gameMoves-"+$(this).attr("rel")+" > span").removeClass("curmove");
				jQuery(".gameMove"+currentPly[$(this).attr("rel")]+"-"+$(this).attr("rel")).addClass("curmove");
			}
		});

		$('#board'+gameid+'-buttons .btnEnd').on('click', function() {
			while (currentPly[$(this).attr("rel")] < gameHistory[$(this).attr("rel")].length - 1) {
				currentPly[$(this).attr("rel")]++;
				game[$(this).attr("rel")].move(gameHistory[$(this).attr("rel")][currentPly[$(this).attr("rel")]].san);
			}
			board[$(this).attr("rel")].position(game[$(this).attr("rel")].fen());
			jQuery(".gameMoves-"+$(this).attr("rel")+" > span").removeClass("curmove");
			jQuery(".gameMove"+currentPly[$(this).attr("rel")]+"-"+$(this).attr("rel")).addClass("curmove");
		});

		jQuery("#boardcont"+gameid+" .boardclose").on('click',function(){
			jQuery('#boardcont'+$(this).attr('rel')).hide();
			$("#gameSelect").find("[value='"+$(this).attr('rel')+"']").removeAttr("selected");
			$("#gameSelect").trigger("chosen:updated");
			return false;
		});
		loadAjaxGame(gameid);
	}
}

function getAjaxRequest(loadpgn){
	clearInterval(window["timer_Srefreshcountdown"]);

	jQuery(".refreshloader").show();
	jQuery("#refreshcountdown").hide();
	jQuery(".refreshcountdown-cont").hide();

	if(typeof loadpgn === 'undefined' || loadpgn == true){
		pgnlink.forEach(function(item){
			pgnFromUrl(item.url, item.id);
		});
		buildSelectGame();
	}
	$.each( Object.keys(game), function(index, value ){
		if(value != ''){
			loadAjaxGame(value);
		}
	});

	jQuery(".refreshloader").hide();
	jQuery("#refreshcountdown").show();
	jQuery("#refreshcountdown").countdowntimer({"seconds":refreshrate,stopButton:"stopBtnhms",timeUp:getAjaxRequest});
	jQuery(".refreshcountdown-cont").show();
}

function loadAjaxGame(gameid) {
	clearInterval(window["timer_HMSBlackClock"+gameid]);
	clearInterval(window["timer_HMSWhiteClock"+gameid]);

	var fenAct = game[gameid].fen().split(" ");

	game[gameid].load_pgn(pgnData[gameid]["PGN"].join('\n'), {newline_char:'\n',sloppy:true});

	var h = game[gameid].header();
	if(h.Result == '*'){
		pgnData[gameid]['Live'] = true;
	}else{
		pgnData[gameid]['Live'] = false;
	}

	$("#game"+gameid+"-dataTop").html(getGametextTop(game[gameid], gameid));
	$("#game"+gameid+"-data").html(getGametextButton(game[gameid], gameid));
	gameHistory[gameid] = game[gameid].history({verbose: true});
	var fenNew = game[gameid].fen().split(" ");

	if(pgnData[gameid]['Live'] == true){
		if(fenAct[5] == fenNew[5]){
			//goToMove(-1, gameid);
			//console.log("move");
		}else{
	    if(fenNew[1]=='w'){
	      goToMove((fenNew[5]*2)-3, gameid);
	    }else{
	      goToMove((fenNew[5]*2)-2, gameid);
	    }
			//console.log("notmove");
		}

		if(fenNew[1]=='w'){
			if(pgnData[gameid]["WhiteClock"] != undefined){
				var WhiteClockParts = pgnData[gameid]["WhiteClock"].split(":");
				$("#WhiteClock"+gameid).countdowntimer({"hours":WhiteClockParts[0],"minutes":WhiteClockParts[1],"seconds":WhiteClockParts[2]});
			}
		}else{
			if(pgnData[gameid]["BlackClock"] != undefined){
				var BlackClockParts = pgnData[gameid]["BlackClock"].split(":");
				$("#BlackClock"+gameid).countdowntimer({"hours":BlackClockParts[0],"minutes":BlackClockParts[1],"seconds":BlackClockParts[2]});
			}
		}
	}
}

function getGametextTop(g, gameid) {
	var h = g.header();

	if(h.Result == '*')
		var result = '';
	else{
		var result = h.Result.split("-");
		result = result[1];
	}

	var gameHeaderText = '<span class="boardergebnis">'+result+'</span><span class="schwarz" title="Schwarz"></span><strong>' + h.Black + '</strong> ' + h.BlackElo + ((h.BlackTeam != undefined) ? '<br><span class="teamname">' + h.BlackTeam + '</span>' : '') + ((h.Board != undefined) ? ' <span class="board"> - Brett ' + h.Board + '</span>' : '');
	if(typeof pgnData[gameid]['BlackClock'] != 'undefined') gameHeaderText += ' <span class="clock"><span class="BlackClock" id="BlackClock'+gameid+'">'+pgnData[gameid]['BlackClock']+'</span></span>';
	return gameHeaderText;
}

function getGametextButton(g, gameid) {
	var h = g.header();

	if(h.Result == '*')
		var result = '';
	else{
		var result = h.Result.split("-");
		result = result[0];
	}

	var gameHeaderText = '<span class="boardergebnis">'+result+'</span><span class="weiss" title="Weiss"></span><strong>' + h.White + '</strong> ' + h.WhiteElo + ((h.WhiteTeam != undefined) ? '<br><span class="teamname">' + h.WhiteTeam + '</span>' : '') + ((h.Board != undefined) ? '<span class="board"> - Brett ' + h.Board + '</span>' : '');
	if(typeof pgnData[gameid]['WhiteClock'] != 'undefined') gameHeaderText += ' <span class="clock"><span class="WhiteClock" id="WhiteClock'+gameid+'">'+pgnData[gameid]['WhiteClock']+'</span></span>';

	gameHeaderText += '</div>';
	var pgn = g.pgn();
	var gameMoves = pgn.replace(/\[(.*?)\]/gm, '').replace(h.Result, '').trim();

	if(gameMoves){
		//format the moves so each one is individually identified, so it can be highlighted
		moveArray = gameMoves.split(/([0-9]+\.\s)/).filter(function(n) {return n;});
		for (var i = 0, l = moveArray.length; i < l; ++i) {
			var s = $.trim(moveArray[i]);
			if (!/^[0-9]+\.$/.test(s)) { //move numbers
				s = s.replace(new RegExp("N", 'g'),"♘");
				s = s.replace(new RegExp("R", 'g'),"♖");
				s = s.replace(new RegExp("Q", 'g'),"♕");
				s = s.replace(new RegExp("N", 'g'),"♗");
				s = s.replace(new RegExp("B", 'g'),"♗");
				s = s.replace(new RegExp("K", 'g'),"♔");
				m = s.split(/\s+/);
				for (var j = 0, ll = m.length; j < ll; ++j) {
					m[j] = '<span class="gameMove' + (i + j - 1) + '-' + gameid + '"><a id="myLink" href="#" onclick="goToMove(' + (i + j - 1) + ',\''+ gameid +'\');return false;">' + m[j] + '</a></span>';
				}
				s = m.join(' ');
			}
			moveArray[i] = s;
		}
		return gameHeaderText + '<div class="gameNotation gameMoves-'+ gameid +'">' + moveArray.join(' ') + '</div>';
	}else{
		return gameHeaderText + '<div class="gameNotation gameMoves-'+ gameid +'"></div>';
	}
}

function goToMove(ply, gameid) {
	if (ply > gameHistory[gameid].length - 1) ply = gameHistory[gameid].length - 1;
	game[gameid].reset();
	for (var i = 0; i <= ply; i++) {
		game[gameid].move(gameHistory[gameid][i].san);
	}
	currentPly[gameid] = i - 1;
	board[gameid].position(game[gameid].fen());
	jQuery(".gameMoves-"+gameid+" > span").removeClass("curmove");
	jQuery(".gameMove"+(i-1)+"-"+gameid).addClass("curmove");
}

function onChange(oldPos, newPos) {
	$("#board"+this.gameid).find('.move-highlight').removeClass("move-highlight");
	var lastmove = gameHistory[this.gameid][ gameHistory[this.gameid].length - 1];

	$("#board"+this.gameid).find('.square-' + lastmove.from).addClass('move-highlight');
	$("#board"+this.gameid).find('.square-' + lastmove.to).addClass('move-highlight');
};

function boardResizeInit(){
	$("#boardsize").chosen({disable_search:true,width: "90%"});
	jQuery("#boardsize").on('change',function(){
		var col = jQuery(this).val();
		boardResize(col);
		chessPgnViewerTrack('BoardResize', 'Change', col);
	});

	$( window ).resize(function() {
		for(var key in board) {
			board[key].resize();
		}
	});

}

function boardResize(col){
	jQuery(".boarditem").removeClass("col-md-2 col-md-3 col-md-4 col-md-6 col-md-12 col-sm-2 col-sm-3 col-sm-4 col-sm-6 col-sm-12 col-xs-3 col-xs-4 col-xs-6");
	switch(col) {
	    case "2":
					jQuery(".boarditem").addClass("col-md-2 col-sm-3"); break;
	    case "3":
	        jQuery(".boarditem").addClass("col-md-3 col-sm-3 col-xs-3"); break;
			case "4":
					jQuery(".boarditem").addClass("col-md-4 col-sm-4 col-xs-4"); break;
			case "6":
					jQuery(".boarditem").addClass("col-md-6 col-sm-6 col-xs-6"); break;
			case "12":
					jQuery(".boarditem").addClass("col-md-12");	break;
	    default:
	        jQuery(".boarditem").addClass(colselect);
	}

	for(var key in board) {
		board[key].resize();
	}
	colselect = "col-md-"+col;
}
/* Main Functions End */

/* Helper Functions Begin*/
function prozessLine(temp, label){
	temp = temp.replace("["+label+" ","");
	temp = temp.replace("]","");
	temp = temp.replace(new RegExp('"', 'g'),"");
	return temp;
}

function clock(gameid, color) {
    var startTime = $("#boardcont"+ gameid +" ."+ color +"Clock").html();
    var pieces = startTime.split(":");
    var time = new Date();
    time.setHours(pieces[0]);
    time.setMinutes(pieces[1]);
    time.setSeconds(pieces[2]);
    var timedif = new Date(time.valueOf() - 1000);
    var newtime = timedif.toTimeString().split(" ")[0];
    $("#board"+ gameid +" ."+ color +"Clock").html(newtime);
}

function getURL(url){
    return $.ajax({
        type: "GET",
        //url: url + '?p=' + new Date().getTime(),
				url: url,
        async: false,
    	error: function(xhr, ajaxOptions, thrownError){
			return false;
		}
	}).always(function(jqXHR, textStatus) {
    if (textStatus != "success") {
        console.log("Error: " + jqXHR.statusText);
    }
}).responseText;
}

/* Helper Functions Ende*/
/*
$.each( Object.keys(game), function(index, value ){
		if(value != ''){
			var score = JSON.parse(getURL("https://schach.ehtokkire.de/pgnScoreByFen.php?fenhash="+MD5(game[value].fen())));
jQuery("#game"+value+"-score").html("<br>Bewertung: " + score.score);
jQuery("#game"+value+"-depth").html("<br>Tiefe: " + score.depth);
jQuery("#game"+value+"-bestmove").html("<br>Vorschlag: " + score.bestmove);
jQuery("#game"+value+"-proposal").html("<br>" + score.proposal);
		}
	});
	*/
