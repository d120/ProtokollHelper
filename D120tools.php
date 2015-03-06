<?php

$wgHooks['ParserFirstCallInit'][] = 'wfProtokollHelperSetup';

function wfProtokollHelperSetup( Parser $parser ) {
	$parser->setHook( 'protokollliste', 'wfProtokollListeRender' );
	$parser->setHook( 'bteil' , 'wfBTeilRender'  );
	return true;
}

function isFachschaftler($user) {
	//var_dump($user);
	if (!$user) return false;
	return preg_match('/fachschaft.informatik.tu-darmstadt.de$/', $user->getEmail());
}

function wfBTeilRender( $input, array $args, Parser $parser, PPFrame $frame ) {
	global $wgUser;
	$parser->disableCache();

	if (isFachschaftler($wgUser)) {
		return "<div style='color:#870;border:1px solid #fea;padding: 5px 10px;'>".
		"<div style='float:right;background: #fea;margin:-5px -10px 0 0;padding:4px 10px;border-radius:0 0 0 8px;'>B-TEIL</div>".
		"$input".
		"</div>";
	} else {
		return array("<i style='color:#870'>Dies ist ein B-Teil und nur für aktive Fachschaftler sichtbar.</i>", "ishtml"=>true);
	}
//	return print_r($wgUser, true);
}


function wfProtokollListeRender( $input, array $args, Parser $parser, PPFrame $frame ) {
	$attr = array();    

	$parser->disableCache();

	$dbr = wfGetDB( DB_SLAVE );

	$prefix = mysql_escape_string($args["prefix"]);

	$conds = array();
	$conds[] = "page_title like '$prefix%' ";
	//$conds['page_is_redirect'] = 0;
	$res = $dbr->select(
		'page',
		array(
		'page_namespace',
		'page_title',
		'page_is_redirect'
		),
		$conds,
		__METHOD__,
		array(
		'ORDER BY' => 'page_title DESC',
		'LIMIT' => intval($args["max_count"]),
		'OFFSET' => '0',
		)
	);
	$out = "";
	foreach($res as $row) {
		$title = preg_replace('$'.$args["regex"].'$', $args["replace"], $row->page_title);
		$title = htmlspecialchars($title);
		$out.= "<p><a href='/wiki/".$row->page_title."'>$title</a></p>\n";
	}

	return $out;
}




// fuer Protokolle: View Source deaktivieren um BTeil zu verbergen

$wgHooks['MediaWikiPerformAction'][] = 'ProtectSource';

function ProtectSource( $output, $article, $title, $user, $request ) {

  global $wgActions, $wgUser;

	$protectSourceText = false;

	// hook replacement for the "MediaWikiPerformAction" hook
	if( !wfRunHooks( 'AlternatePerformAction', array( $output, $article, $title, $user, $request ) ) ) {
		wfProfileOut( __METHOD__ );
		return;
	}
	//var_dump($request->getVal( 'action' ), $request->getVal( 'diff' ));

	// only block source of protected pages
	if(strpos($title->getFullText(),"Protokolle/FS-Sitzungen") === 0) {
		$protectSourceText = "Der Zugang zum Quelltext von Protokollen ist nur aktiven Fachschaftlern möglich.";
	}
	if (!$protectSourceText) {
		return true;
	}

	// check if the user is allowed to see protected parts
	if (isFachschaftler($wgUser))
		return true;

	$action = $request->getVal( 'action' );
	$diffview = $request->getVal( 'diff' );
	if($diffview) $action = 'blockdiff';

	// we are not blocking the following actions, so if these are the requested action, return processing
	// to MediaWiki::performaction.
	$okactions = array('view', 'watch', 'unwatch', 'info', 'render', 'purge');
	if(in_array( $action, $okactions) ) {
		return true;
	}
	
	// these actions will either reveal source information about the article, 
	// or are actions that should not be executable if a user is not allowed to edit an article.
	$blockedactions = array('raw', 'delete', 'revert', 'rollback', 'protect', 'unprotect', 
	'markpatrolled', 'deletetrackback', 'edit', 'blockdiff', 'submit');
	
	// here's where the real action will take place....
	if(in_array($action, $blockedactions)) {
		// sets some information for debugging if you're using that feature
		if ( isset($title) ) {
			$output->mDebugtext .= 'Original title: ' . $title->getPrefixedText() . "\n";
		}
		$output->setPageTitle( 'Zugriff verweigert' );
		$output->setHTMLTitle( 'Zugriff verweigert' );
		// this is for spiders, like GoogleBot, etc. telling them not to index this page
		$output->setRobotPolicy( 'noindex,nofollow' );
		// indicates that this is not related to an article
		$output->setArticleRelated( false );
		// turns off caching for this page
		$output->enableClientCache( false );
		// blanks out any redirect instructions
		$output->mRedirect = '';
		// blanks out any article text that might have been previously loaded
		$output->mBodytext = '';
		// adds the message to the body of the page that the user will see 
		$output->addHTML($protectSourceText);
		// add a link to return to the article's view page
		$output->addReturnTo( $title );
		return false;
	} else {
		// if the action is not in the blocked actions array or the ok actions array, then it is probably a custom
		// action added via another extension.  So, let's return processing to MediaWiki::performAction and let
		// it take over
		return true;
	}
}


function wfLetztesProtokollRender( $input, array $args, Parser $parser, PPFrame $frame ) {
        $attr = array();    

	$parser->disableCache();

	$dbr = wfGetDB( DB_SLAVE );

	$prefix = mysql_escape_string($args["prefix"]);

	$conds = array();
	$conds[] = "page_title like '$prefix%' ";
	$res = $dbr->select(
	  'page', array('page_namespace','page_title','page_is_redirect'),
	  $conds, __METHOD__,
	  array(
	    'ORDER BY' => 'page_title DESC',
	    'LIMIT' => '1',
	    'OFFSET' => '0',
	  )
	);
	$out = "";
	$title = Title::loadFromRow($res[0]);

	$page = new WikiPage();
	$page->mTitle = $title;
	$page->loadPageData();
	//$page->

	return $out;
}



