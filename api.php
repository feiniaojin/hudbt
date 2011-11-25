<?php
header('Content-type: text/xml');

function get_user_prop($id) {
  $user = get_user_row($id);
  $out = '';
  if ($user) {
    $out .= '<user id="' . $user['id'] . '">';
    $out .= '<username>' . $user['username'] . '</username>';
    $out .= '<class>' . $user['class'] . '</class>';
    $out .= '<canonicalClass>' . get_user_class_name($arr['class'],false) . '</canonicalClass>';
    if ($user['donor'] == 'yes') {
      $out .= '<donor>true</donor>';
    }
    $out .= '</user>';
  }
  return $out;
}

function torrenttable_api($res, $variant = "torrent", $swap_headings = false) {
  global $Cache;
  global $lang_functions;
  global $CURUSER, $waitsystem;
  global $showextinfo;
  global $torrentmanage_class, $smalldescription_main, $enabletooltip_tweak;
  global $CURLANGDIR;
  // Added Br BruceWolf. 2011-04-24
  // Filter banned torrents
  global $seebanned_class;
  
  if ($variant == "torrent"){
    $last_browse = $CURUSER['last_browse'];
    $sectiontype = $browsecatmode;
  }
  elseif($variant == "music"){
    $last_browse = $CURUSER['last_music'];
    $sectiontype = $specialcatmode;
  }
  else{
    $last_browse = $CURUSER['last_browse'];
    $sectiontype = "";
  }

  $time_now = TIMENOW;
  if ($last_browse > $time_now) {
    $last_browse=$time_now;
  }

  if (get_user_class() < UC_VIP && $waitsystem == "yes") {
    $ratio = get_ratio($CURUSER["id"], false);
    $gigs = $CURUSER["uploaded"] / (1024*1024*1024);
    if($gigs > 10)
      {
	if ($ratio < 0.4) $wait = 24;
	elseif ($ratio < 0.5) $wait = 12;
	elseif ($ratio < 0.6) $wait = 6;
	elseif ($ratio < 0.8) $wait = 3;
	else $wait = 0;
      }
    else $wait = 0;
  }
?>

<api><query><torrents>

  <?php
  $caticonrow = get_category_icon_row($CURUSER['caticon']);
  if ($caticonrow['secondicon'] == 'yes')
    $has_secondicon = true;
  else $has_secondicon = false;
  $counter = 0;
  if ($smalldescription_main == 'no' || $CURUSER['showsmalldescr'] == 'no')
    $displaysmalldescr = false;
  else $displaysmalldescr = true;
  while ($row = mysql_fetch_assoc($res))  {
      if($row['banned'] == 'no' 
	 || ($row['banned'] == 'yes' 
	     && (get_user_class() >= $seebanned_class 
		 || $CURUSER['id'] == $row['owner']))) {
	$id = $row["id"];
	print('<torrent id="' . $id . '">');

	/* $sphighlight = get_torrent_bg_color($row['sp_state']); */
	/* print("<tr" . $sphighlight . ">\n"); */


	if (isset($row["category"])) {
	print('<catid>' . $row['category'] . '</catid>');
	  /* print(return_category_image($row["category"], "?")); */
	  /* if ($has_secondicon){ */
	  /*   print(get_second_icon($row, "pic/".$catimgurl."additional/")); */
	  /* } */
	}



	//torrent name
	$dispname = trim($row["name"]);
	$short_torrent_name_alt = "";
	$mouseovertorrent = "";
	$tooltipblock = "";
	$has_tooltip = false;
	if ($enabletooltip_tweak == 'yes')
	  $tooltiptype = $CURUSER['tooltip'];
	else
	  $tooltiptype = 'off';
	switch ($tooltiptype){
	case 'minorimdb' : {
	  if ($showextinfo['imdb'] == 'yes' && $row["url"])
	    {
	      $url = $row['url'];
	      $cache = $row['cache_stamp'];
	      $type = 'minor';
	      $has_tooltip = true;
	    }
	  break;
	}
	case 'medianimdb' :
	  {
	    if ($showextinfo['imdb'] == 'yes' && $row["url"])
	      {
		$url = $row['url'];
		$cache = $row['cache_stamp'];
		$type = 'median';
		$has_tooltip = true;
	      }
	    break;
	  }
	case 'off' :  break;
	}
	if (!$has_tooltip)
	  $short_torrent_name_alt = "title=\"".htmlspecialchars($dispname)."\"";
	else{
	  $torrent_tooltip[$counter]['id'] = "torrent_" . $counter;
	  $torrent_tooltip[$counter]['content'] = "";
	  $mouseovertorrent = "onmouseover=\"get_ext_info_ajax('".$torrent_tooltip[$counter]['id']."','".$url."','".$cache."','".$type."'); domTT_activate(this, event, 'content', document.getElementById('" . $torrent_tooltip[$counter]['id'] . "'), 'trail', false, 'delay',600,'lifetime',6000,'fade','both','styleClass','niceTitle', 'fadeMax',87, 'maxWidth', 500);\"";
	}
	$count_dispname=mb_strlen($dispname,"UTF-8");
	if (!$displaysmalldescr || $row["small_descr"] == "")// maximum length of torrent name
	  $max_length_of_torrent_name = 120;
	elseif ($CURUSER['fontsize'] == 'large')
	  $max_length_of_torrent_name = 60;
	elseif ($CURUSER['fontsize'] == 'small')
	  $max_length_of_torrent_name = 80;
	else $max_length_of_torrent_name = 70;

	if($count_dispname > $max_length_of_torrent_name)
	  $dispname=mb_substr($dispname, 0, $max_length_of_torrent_name-2,"UTF-8") . "..";

	print('<name>' . htmlspecialchars($dispname) . '</name>');

	if ($row['pos_state'] == 'sticky' && $CURUSER['appendsticky'] == 'yes')
	  $stickyicon = "<img class=\"sticky\" src=\"pic/trans.gif\" alt=\"Sticky\" title=\"".$lang_functions['title_sticky']."\" />&nbsp;";
	else $stickyicon = "";
	
	if ($displaysmalldescr) {
	  $dissmall_descr = trim($row["small_descr"]);
	  $count_dissmall_descr=mb_strlen($dissmall_descr,"UTF-8");
	  $max_lenght_of_small_descr=$max_length_of_torrent_name; // maximum length
	  if($count_dissmall_descr > $max_lenght_of_small_descr)
	    {
	      $dissmall_descr=mb_substr($dissmall_descr, 0, $max_lenght_of_small_descr-2,"UTF-8") . "..";
	    }
	}
	print('<desc>' . htmlspecialchars($dissmall_descr) . '</desc>');
	if ($row['pos_state'] == 'sticky') {
	  print('<sticky>true</sticky>');
	}


	$sp_torrent = get_torrent_promotion_append($row['sp_state'],"",true,$row["added"], $row['promotion_time_type'], $row['promotion_until']);
	$sp_torrent_sub = get_torrent_promotion_append_sub($row['sp_state'],"",true,$row["added"], $row['promotion_time_type'], $row['promotion_until']);
	$picked_torrent = "";
	if ($CURUSER['appendpicked'] != 'no'){
	  print('<picktype>' . $row['picktype'] . '</picktype>');

	  //Added by bluemonster 20111026
	  if($row['oday']=="yes")
	    print('<oday>true</oday>');
	}

	if ($CURUSER['appendnew'] != 'no' && strtotime($row["added"]) >= $last_browse) {
	  print('<new>true</new>');
	}

	if ($row["banned"] == 'yes') {
	  print('<banned>true</banned>');
	}

	/* print($sp_torrent_sub."</td>"); */

	/* if ($wait) */
	/*   { */
	/*     $elapsed = floor((TIMENOW - strtotime($row["added"])) / 3600); */
	/*     if ($elapsed < $wait) */
	/*       { */
	/* 	$color = dechex(floor(127*($wait - $elapsed)/48 + 128)*65536); */
	/* 	print("<td class=\"rowfollow nowrap\"><a href=\"faq.php#id46\"><font color=\"".$color."\">" . number_format($wait - $elapsed) . $lang_functions['text_h']."</font></a></td>\n"); */
	/*       } */
	/*     else */
	/*       print("<td class=\"rowfollow nowrap\">".$lang_functions['text_none']."</td>\n"); */
	/*   } */
	
	if ($CURUSER['showcomnum'] != 'no')
	  {
	    $nl = "";

	    //comments
	    print('<comments>' . $row["comments"]);

	    if ($row["comments"]) {
	      if ($enabletooltip_tweak == 'yes' && $CURUSER['showlastcom'] != 'no') {
		if (!$lastcom = $Cache->get_value('torrent_'.$id.'_last_comment_content')){
		  $res2 = sql_query("SELECT user, added, text FROM comments WHERE torrent = $id ORDER BY id DESC LIMIT 1");
		  $lastcom = mysql_fetch_array($res2);
		  $Cache->cache_value('torrent_'.$id.'_last_comment_content', $lastcom, 1855);
		}
		$timestamp = strtotime($lastcom["added"]);
		$hasnewcom = ($lastcom['user'] != $CURUSER['id'] && $timestamp >= $last_browse);
		if ($lastcom) {
		  if ($CURUSER['timetype'] != 'timealive') 
		    $lastcomtime = $lang_functions['text_at_time'].$lastcom['added'];
		  else
		    $lastcomtime = $lang_functions['text_blank'].gettime($lastcom["added"],true,false,true);
		}

		if ($hasnewcom) {
		  print('<new><author>'. $lastcom['user'] .'</author><time>' . $timestamp . '</time><content>' . $lastcom['text'] . '</content></new>');
		}
	      } 
	    }

	    print('</comments>');
	  }
       

	$time = $row["added"];
	print('<added>' . $time . '</added>');
	$time = gettime($time,false,true);


	//size
	print('<size>' . $row['size'] . '</size>');

	print('<seeders>' . $row['seeders'] . '</seeders>');
	print('<leechers>' . $row['leechers'] . '</leechers>');
	print('<times_completed>' . $row['times_completed'] . '</times_completed>');

	print('<owner');
	if ($row["anonymous"] == "yes") {
	  print(' anonymous="true">');
	  if (get_user_class() >= $torrentmanage_class) {
	    print(get_user_prop($row["owner"]));
	  }
	}
	else {
	  print('>');

	  print(get_user_prop($row["owner"]));
#	    print("<td class=\"rowfollow\">" . (isset($row["owner"]) ? get_username($row["owner"]) : "<i>".$lang_functions['text_orphaned']."</i>") . "</td>\n");
	  }
	print('</owner>');


	if (get_user_class() >= $torrentmanage_class) {
	  }
	print("</torrent>\n");
	$counter++;
      }
    }
  print("</torrents></query></api>");

  if($enabletooltip_tweak == 'yes' && (!isset($CURUSER) || $CURUSER['showlastcom'] == 'yes'))
    create_tooltip_container($lastcom_tooltip, 400);
  create_tooltip_container($torrent_tooltip, 500);
}


?>

<?php
require_once("include/bittorrent.php");
dbconn(true);
require_once(get_langfile_path("torrents.php"));
loggedinorreturn();
parked();
if ($showextinfo['imdb'] == 'yes')
	require_once ("imdb/imdb.class.php");
//check searchbox
$sectiontype = $browsecatmode;
$showsubcat = get_searchbox_value($sectiontype, 'showsubcat');//whether show subcategory (i.e. sources, codecs) or not
$showsource = get_searchbox_value($sectiontype, 'showsource'); //whether show sources or not
$showmedium = get_searchbox_value($sectiontype, 'showmedium'); //whether show media or not
$showcodec = get_searchbox_value($sectiontype, 'showcodec'); //whether show codecs or not
$showstandard = get_searchbox_value($sectiontype, 'showstandard'); //whether show standards or not
$showprocessing = get_searchbox_value($sectiontype, 'showprocessing'); //whether show processings or not
$showteam = get_searchbox_value($sectiontype, 'showteam'); //whether show teams or not
$showaudiocodec = get_searchbox_value($sectiontype, 'showaudiocodec'); //whether show audio codec or not
$catsperrow = get_searchbox_value($sectiontype, 'catsperrow'); //show how many cats per line in search box
$catpadding = get_searchbox_value($sectiontype, 'catpadding'); //padding space between categories in pixel

$cats = genrelist($sectiontype);
if ($showsubcat){
	if ($showsource) $sources = searchbox_item_list("sources");
	if ($showmedium) $media = searchbox_item_list("media");
	if ($showcodec) $codecs = searchbox_item_list("codecs");
	if ($showstandard) $standards = searchbox_item_list("standards");
	if ($showprocessing) $processings = searchbox_item_list("processings");
	if ($showteam) $teams = searchbox_item_list("teams");
	if ($showaudiocodec) $audiocodecs = searchbox_item_list("audiocodecs");
}

$searchstr_ori = htmlspecialchars(trim($_GET["search"]));
$searchstr = mysql_real_escape_string(trim($_GET["search"]));
if (empty($searchstr))
	unset($searchstr);

// sorting by MarkoStamcar
if ($_GET['sort'] && $_GET['type']) {

	$column = '';
	$ascdesc = '';

	switch($_GET['sort']) {
		case '1': $column = "name"; break;
		case '2': $column = "numfiles"; break;
		case '3': $column = "comments"; break;
		case '4': $column = "added"; break;
		case '5': $column = "size"; break;
		case '6': $column = "times_completed"; break;
		case '7': $column = "seeders"; break;
		case '8': $column = "leechers"; break;
		case '9': $column = "owner"; break;
		default: $column = "id"; break;
	}

	switch($_GET['type']) {
		case 'asc': $ascdesc = "ASC"; $linkascdesc = "asc"; break;
		case 'desc': $ascdesc = "DESC"; $linkascdesc = "desc"; break;
		default: $ascdesc = "DESC"; $linkascdesc = "desc"; break;
	}

	if($column == "owner")
	{
		$orderby = "ORDER BY pos_state DESC, torrents.anonymous, users.username " . $ascdesc;
	}
	else
	{
		$orderby = "ORDER BY pos_state DESC, torrents." . $column . " " . $ascdesc;
	}

	$pagerlink = "sort=" . intval($_GET['sort']) . "&type=" . $linkascdesc . "&";

} else {

	$orderby = "ORDER BY pos_state DESC, torrents.id DESC";
	$pagerlink = "";

}

$addparam = "";
$wherea = array();
$wherecatina = array();
if ($showsubcat){
	if ($showsource) $wheresourceina = array();
	if ($showmedium) $wheremediumina = array();
	if ($showcodec) $wherecodecina = array();
	if ($showstandard) $wherestandardina = array();
	if ($showprocessing) $whereprocessingina = array();
	if ($showteam) $whereteamina = array();
	if ($showaudiocodec) $whereaudiocodecina = array();
}
//----------------- start whether show torrents from all sections---------------------//
if ($_GET)
	$allsec = 0 + $_GET["allsec"];
else $allsec = 0;
if ($allsec == 1)		//show torrents from all sections
{
	$addparam .= "allsec=1&";
}
// ----------------- end whether ignoring section ---------------------//
// ----------------- start bookmarked ---------------------//
if ($_GET)
	$inclbookmarked = 0 + $_GET["inclbookmarked"];
elseif ($CURUSER['notifs']){
	if (strpos($CURUSER['notifs'], "[inclbookmarked=0]") !== false)
		$inclbookmarked = 0;
	elseif (strpos($CURUSER['notifs'], "[inclbookmarked=1]") !== false)
		$inclbookmarked = 1;
	elseif (strpos($CURUSER['notifs'], "[inclbookmarked=2]") !== false)
		$inclbookmarked = 2;
}
else $inclbookmarked = 0;

if (!in_array($inclbookmarked,array(0,1,2)))
{
	$inclbookmarked = 0;
	write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking inclbookmarked field in" . $_SERVER['SCRIPT_NAME'], 'mod');
}
if ($inclbookmarked == 0)  //all(bookmarked,not)
{
	$addparam .= "inclbookmarked=0&";
}
elseif ($inclbookmarked == 1)		//bookmarked
{
	$addparam .= "inclbookmarked=1&";
	if(isset($CURUSER))
	$wherea[] = "torrents.id IN (SELECT torrentid FROM bookmarks WHERE userid=" . $CURUSER['id'] . ")";
}
elseif ($inclbookmarked == 2)		//not bookmarked
{
	$addparam .= "inclbookmarked=2&";
	if(isset($CURUSER))
	$wherea[] = "torrents.id NOT IN (SELECT torrentid FROM bookmarks WHERE userid=" . $CURUSER['id'] . ")";
}
// ----------------- end bookmarked ---------------------//

// if (!isset($CURUSER) || get_user_class() < $seebanned_class)
if (!isset($CURUSER))
	$wherea[] = "banned != 'yes'";

if ($_GET["swaph"]) {
  $addparam .= "swaph=1&";
}

// ----------------- start include dead ---------------------//
if (isset($_GET["incldead"]))
	$include_dead = 0 + $_GET["incldead"];
elseif ($CURUSER['notifs']){
	if (strpos($CURUSER['notifs'], "[incldead=0]") !== false)
		$include_dead = 0;
	elseif (strpos($CURUSER['notifs'], "[incldead=1]") !== false)
		$include_dead = 1;
	elseif (strpos($CURUSER['notifs'], "[incldead=2]") !== false)
		$include_dead = 2;
	else $include_dead = 1;
}
else $include_dead = 1;

if (!in_array($include_dead,array(0,1,2)))
{
	$include_dead = 0;
	write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking incldead field in" . $_SERVER['SCRIPT_NAME'], 'mod');
}
if ($include_dead == 0)  //all(active,dead)
{
	$addparam .= "incldead=0&";
}
elseif ($include_dead == 1)		//active
{
	$addparam .= "incldead=1&";
	$wherea[] = "visible = 'yes'";
}
elseif ($include_dead == 2)		//dead
{
	$addparam .= "incldead=2&";
	$wherea[] = "visible = 'no'";
}
// ----------------- end include dead ---------------------//
if ($_GET)
	$special_state = 0 + $_GET["spstate"];
elseif ($CURUSER['notifs']){
	if (strpos($CURUSER['notifs'], "[spstate=0]") !== false)
		$special_state = 0;
	elseif (strpos($CURUSER['notifs'], "[spstate=1]") !== false)
		$special_state = 1;
	elseif (strpos($CURUSER['notifs'], "[spstate=2]") !== false)
		$special_state = 2;
	elseif (strpos($CURUSER['notifs'], "[spstate=3]") !== false)
		$special_state = 3;
	elseif (strpos($CURUSER['notifs'], "[spstate=4]") !== false)
		$special_state = 4;
	elseif (strpos($CURUSER['notifs'], "[spstate=5]") !== false)
		$special_state = 5;
	elseif (strpos($CURUSER['notifs'], "[spstate=6]") !== false)
		$special_state = 6;
	elseif (strpos($CURUSER['notifs'], "[spstate=6]") !== false)
		$special_state = 7;
}
else $special_state = 0;

if (!in_array($special_state,array(0,1,2,3,4,5,6,7)))
{
	$special_state = 0;
	write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking spstate field in " . $_SERVER['SCRIPT_NAME'], 'mod');
}
if($special_state == 0)	//all
{
	$addparam .= "spstate=0&";
}
elseif ($special_state == 1)	//normal
{
	$addparam .= "spstate=1&";

	$wherea[] = "sp_state = 1";

	if(get_global_sp_state() == 1)
	{
		$wherea[] = "sp_state = 1";
	}
}
elseif ($special_state == 2)	//free
{
	$addparam .= "spstate=2&";

	if(get_global_sp_state() == 1)
	{
		$wherea[] = "sp_state = 2";
	}
	else if(get_global_sp_state() == 2)
	{
		;
	}
}
elseif ($special_state == 3)	//2x up
{
	$addparam .= "spstate=3&";
	if(get_global_sp_state() == 1)	//only sp state
	{
		$wherea[] = "sp_state = 3";
	}
	else if(get_global_sp_state() == 3)	//all
	{
		;
	}
}
elseif ($special_state == 4)	//2x up and free
{
	$addparam .= "spstate=4&";

	if(get_global_sp_state() == 1)	//only sp state
	{
		$wherea[] = "sp_state = 4";
	}
	else if(get_global_sp_state() == 4)	//all
	{
		;
	}
}
elseif ($special_state == 5)	//half down
{
	$addparam .= "spstate=5&";

	if(get_global_sp_state() == 1)	//only sp state
	{
		$wherea[] = "sp_state = 5";
	}
	else if(get_global_sp_state() == 5)	//all
	{
		;
	}
}
elseif ($special_state == 6)	//half down
{
	$addparam .= "spstate=6&";

	if(get_global_sp_state() == 1)	//only sp state
	{
		$wherea[] = "sp_state = 6";
	}
	else if(get_global_sp_state() == 6)	//all
	{
		;
	}
}
elseif ($special_state == 7)	//30% down
{
	$addparam .= "spstate=7&";

	if(get_global_sp_state() == 1)	//only sp state
	{
		$wherea[] = "sp_state = 7";
	}
	else if(get_global_sp_state() == 7)	//all
	{
		;
	}
}

$category_get = 0 + $_GET["cat"];
if ($showsubcat){
if ($showsource) $source_get = 0 + $_GET["source"];
if ($showmedium) $medium_get = 0 + $_GET["medium"];
if ($showcodec) $codec_get = 0 + $_GET["codec"];
if ($showstandard) $standard_get = 0 + $_GET["standard"];
if ($showprocessing) $processing_get = 0 + $_GET["processing"];
if ($showteam) $team_get = 0 + $_GET["team"];
if ($showaudiocodec) $audiocodec_get = 0 + $_GET["audiocodec"];
}

$all = 0 + $_GET["all"];

if (!$all)
{
	if (!$_GET && $CURUSER['notifs'])
	{
		$all = true;
		foreach ($cats as $cat)
		{
			$all &= $cat[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[cat'.$cat['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$catcheck = false;
			else
			$catcheck = true;

			if ($catcheck)
			{
				$wherecatina[] = $cat[id];
				$addparam .= "cat$cat[id]=1&";
			}
		}
		if ($showsubcat){
		if ($showsource)
		foreach ($sources as $source)
		{
			$all &= $source[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[sou'.$source['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$sourcecheck = false;
			else
			$sourcecheck = true;

			if ($sourcecheck)
			{
				$wheresourceina[] = $source[id];
				$addparam .= "source$source[id]=1&";
			}
		}
		if ($showmedium)
		foreach ($media as $medium)
		{
			$all &= $medium[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[med'.$medium['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$mediumcheck = false;
			else
			$mediumcheck = true;

			if ($mediumcheck)
			{
				$wheremediumina[] = $medium[id];
				$addparam .= "medium$medium[id]=1&";
			}
		}
		if ($showcodec)
		foreach ($codecs as $codec)
		{
			$all &= $codec[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[cod'.$codec['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$codeccheck = false;
			else
			$codeccheck = true;

			if ($codeccheck)
			{
				$wherecodecina[] = $codec[id];
				$addparam .= "codec$codec[id]=1&";
			}
		}
		if ($showstandard)
		foreach ($standards as $standard)
		{
			$all &= $standard[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[sta'.$standard['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$standardcheck = false;
			else
			$standardcheck = true;

			if ($standardcheck)
			{
				$wherestandardina[] = $standard[id];
				$addparam .= "standard$standard[id]=1&";
			}
		}
		if ($showprocessing)
		foreach ($processings as $processing)
		{
			$all &= $processing[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[pro'.$processing['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$processingcheck = false;
			else
			$processingcheck = true;

			if ($processingcheck)
			{
				$whereprocessingina[] = $processing[id];
				$addparam .= "processing$processing[id]=1&";
			}
		}
		if ($showteam)
		foreach ($teams as $team)
		{
			$all &= $team[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[tea'.$team['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$teamcheck = false;
			else
			$teamcheck = true;

			if ($teamcheck)
			{
				$whereteamina[] = $team[id];
				$addparam .= "team$team[id]=1&";
			}
		}
		if ($showaudiocodec)
		foreach ($audiocodecs as $audiocodec)
		{
			$all &= $audiocodec[id];
			$mystring = $CURUSER['notifs'];
			$findme  = '[aud'.$audiocodec['id'].']';
			$search = strpos($mystring, $findme);
			if ($search === false)
			$audiocodeccheck = false;
			else
			$audiocodeccheck = true;

			if ($audiocodeccheck)
			{
				$whereaudiocodecina[] = $audiocodec[id];
				$addparam .= "audiocodec$audiocodec[id]=1&";
			}
		}
		}	
	}
	// when one clicked the cat, source, etc. name/image
	elseif ($category_get)
	{
		int_check($category_get,true,true,true);
		$wherecatina[] = $category_get;
		$addparam .= "cat=$category_get&";
	}
	elseif ($medium_get)
	{
		int_check($medium_get,true,true,true);
		$wheremediumina[] = $medium_get;
		$addparam .= "medium=$medium_get&";
	}
	elseif ($source_get)
	{
		int_check($source_get,true,true,true);
		$wheresourceina[] = $source_get;
		$addparam .= "source=$source_get&";
	}
	elseif ($codec_get)
	{
		int_check($codec_get,true,true,true);
		$wherecodecina[] = $codec_get;
		$addparam .= "codec=$codec_get&";
	}
	elseif ($standard_get)
	{
		int_check($standard_get,true,true,true);
		$wherestandardina[] = $standard_get;
		$addparam .= "standard=$standard_get&";
	}
	elseif ($processing_get)
	{
		int_check($processing_get,true,true,true);
		$whereprocessingina[] = $processing_get;
		$addparam .= "processing=$processing_get&";
	}
	elseif ($team_get)
	{
		int_check($team_get,true,true,true);
		$whereteamina[] = $team_get;
		$addparam .= "team=$team_get&";
	}
	elseif ($audiocodec_get)
	{
		int_check($audiocodec_get,true,true,true);
		$whereaudiocodecina[] = $audiocodec_get;
		$addparam .= "audiocodec=$audiocodec_get&";
	}
	else	//select and go
	{
		$all = True;
		foreach ($cats as $cat)
		{
			$all &= $_GET["cat$cat[id]"];
			if ($_GET["cat$cat[id]"])
			{
				$wherecatina[] = $cat[id];
				$addparam .= "cat$cat[id]=1&";
			}
		}
		if ($showsubcat){
		if ($showsource)
		foreach ($sources as $source)
		{
			$all &= $_GET["source$source[id]"];
			if ($_GET["source$source[id]"])
			{
				$wheresourceina[] = $source[id];
				$addparam .= "source$source[id]=1&";
			}
		}
		if ($showmedium)
		foreach ($media as $medium)
		{
			$all &= $_GET["medium$medium[id]"];
			if ($_GET["medium$medium[id]"])
			{
				$wheremediumina[] = $medium[id];
				$addparam .= "medium$medium[id]=1&";
			}
		}
		if ($showcodec)
		foreach ($codecs as $codec)
		{
			$all &= $_GET["codec$codec[id]"];
			if ($_GET["codec$codec[id]"])
			{
				$wherecodecina[] = $codec[id];
				$addparam .= "codec$codec[id]=1&";
			}
		}
		if ($showstandard)
		foreach ($standards as $standard)
		{
			$all &= $_GET["standard$standard[id]"];
			if ($_GET["standard$standard[id]"])
			{
				$wherestandardina[] = $standard[id];
				$addparam .= "standard$standard[id]=1&";
			}
		}
		if ($showprocessing)
		foreach ($processings as $processing)
		{
			$all &= $_GET["processing$processing[id]"];
			if ($_GET["processing$processing[id]"])
			{
				$whereprocessingina[] = $processing[id];
				$addparam .= "processing$processing[id]=1&";
			}
		}
		if ($showteam)
		foreach ($teams as $team)
		{
			$all &= $_GET["team$team[id]"];
			if ($_GET["team$team[id]"])
			{
				$whereteamina[] = $team[id];
				$addparam .= "team$team[id]=1&";
			}
		}
		if ($showaudiocodec)
		foreach ($audiocodecs as $audiocodec)
		{
			$all &= $_GET["audiocodec$audiocodec[id]"];
			if ($_GET["audiocodec$audiocodec[id]"])
			{
				$whereaudiocodecina[] = $audiocodec[id];
				$addparam .= "audiocodec$audiocodec[id]=1&";
			}
		}
		}
	}
}

if ($all)
{
	//stderr("in if all","");
	$wherecatina = array();
	if ($showsubcat){
	$wheresourceina = array();
	$wheremediumina = array();
	$wherecodecina = array();
	$wherestandardina = array();
	$whereprocessingina = array();
	$whereteamina = array();
	$whereaudiocodecina = array();}
	$addparam .= "";
}
//stderr("", count($wherecatina)."-". count($wheresourceina));

if (count($wherecatina) > 1)
$wherecatin = implode(",",$wherecatina);
elseif (count($wherecatina) == 1)
$wherea[] = "category = $wherecatina[0]";

if ($showsubcat){
if ($showsource){
if (count($wheresourceina) > 1)
$wheresourcein = implode(",",$wheresourceina);
elseif (count($wheresourceina) == 1)
$wherea[] = "source = $wheresourceina[0]";}

if ($showmedium){
if (count($wheremediumina) > 1)
$wheremediumin = implode(",",$wheremediumina);
elseif (count($wheremediumina) == 1)
$wherea[] = "medium = $wheremediumina[0]";}

if ($showcodec){
if (count($wherecodecina) > 1)
$wherecodecin = implode(",",$wherecodecina);
elseif (count($wherecodecina) == 1)
$wherea[] = "codec = $wherecodecina[0]";}

if ($showstandard){
if (count($wherestandardina) > 1)
$wherestandardin = implode(",",$wherestandardina);
elseif (count($wherestandardina) == 1)
$wherea[] = "standard = $wherestandardina[0]";}

if ($showprocessing){
if (count($whereprocessingina) > 1)
$whereprocessingin = implode(",",$whereprocessingina);
elseif (count($whereprocessingina) == 1)
$wherea[] = "processing = $whereprocessingina[0]";}
}
if ($showteam){
if (count($whereteamina) > 1)
$whereteamin = implode(",",$whereteamina);
elseif (count($whereteamina) == 1)
$wherea[] = "team = $whereteamina[0]";}

if ($showaudiocodec){
if (count($whereaudiocodecina) > 1)
$whereaudiocodecin = implode(",",$whereaudiocodecina);
elseif (count($whereaudiocodecina) == 1)
$wherea[] = "audiocodec = $whereaudiocodecina[0]";}

$wherebase = $wherea;

if (isset($searchstr))
{
	if (!$_GET['notnewword']){
		insert_suggest($searchstr, $CURUSER['id']);
		$notnewword="";
	}
	else{
		$notnewword="notnewword=1&";
	}
	$search_mode = 0 + $_GET["search_mode"];
	if (!in_array($search_mode,array(0,1,2)))
	{
		$search_mode = 0;
		write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking search_mode field in" . $_SERVER['SCRIPT_NAME'], 'mod');
	}

	$search_area = 0 + $_GET["search_area"];

	if ($search_area == 4) {
		$searchstr = (int)parse_imdb_id($searchstr);
	}
	$like_expression_array =array();
	unset($like_expression_array);

	switch ($search_mode)
	{
		case 0:	// AND, OR
		case 1	:
			{
				$searchstr = str_replace(".", " ", $searchstr);
				$searchstr_exploded = explode(" ", $searchstr);
				$searchstr_exploded_count= 0;
				foreach ($searchstr_exploded as $searchstr_element)
				{
					$searchstr_element = trim($searchstr_element);	// furthur trim to ensure that multi space seperated words still work
					$searchstr_exploded_count++;
					if ($searchstr_exploded_count > 10)	// maximum 10 keywords
					break;
					$like_expression_array[] = " LIKE '%" . $searchstr_element. "%'";
				}
				break;
			}
		case 2	:	// exact
		{
			$like_expression_array[] = " LIKE '%" . $searchstr. "%'";
			break;
		}
		/*case 3 :	// parsed
		{
		$like_expression_array[] = $searchstr;
		break;
		}*/
	}
	$ANDOR = ($search_mode == 0 ? " AND " : " OR ");	// only affects mode 0 and mode 1

	switch ($search_area)
	{
		case 0   :	// torrent name
		{
			foreach ($like_expression_array as &$like_expression_array_element)
			$like_expression_array_element = "(torrents.name" . $like_expression_array_element." OR torrents.small_descr". $like_expression_array_element.")";
			$wherea[] =  implode($ANDOR, $like_expression_array);
			break;
		}
		case 1	:	// torrent description
		{
			foreach ($like_expression_array as &$like_expression_array_element)
			$like_expression_array_element = "torrents.descr". $like_expression_array_element;
			$wherea[] =  implode($ANDOR,  $like_expression_array);
			break;
		}
		/*case 2	:	// torrent small description
		{
			foreach ($like_expression_array as &$like_expression_array_element)
			$like_expression_array_element =  "torrents.small_descr". $like_expression_array_element;
			$wherea[] =  implode($ANDOR, $like_expression_array);
			break;
		}*/
		case 3	:	// torrent uploader
		{
			foreach ($like_expression_array as &$like_expression_array_element)
			$like_expression_array_element =  "users.username". $like_expression_array_element;

			if(!isset($CURUSER))	// not registered user, only show not anonymous torrents
			{
				$wherea[] =  implode($ANDOR, $like_expression_array) . " AND torrents.anonymous = 'no'";
			}
			else
			{
				if(get_user_class() > $torrentmanage_class)	// moderator or above, show all
				{
					$wherea[] =  implode($ANDOR, $like_expression_array);
				}
				else // only show normal torrents and anonymous torrents from hiself
				{
					$wherea[] =   "(" . implode($ANDOR, $like_expression_array) . " AND torrents.anonymous = 'no') OR (" . implode($ANDOR, $like_expression_array). " AND torrents.anonymous = 'yes' AND users.id=" . $CURUSER["id"] . ") ";
				}
			}
			break;
		}
		case 4  :  //imdb url
			foreach ($like_expression_array as &$like_expression_array_element)
			$like_expression_array_element = "torrents.url". $like_expression_array_element;
			$wherea[] =  implode($ANDOR,  $like_expression_array);
			break;
		default :	// unkonwn
		{
			$search_area = 0;
			$wherea[] =  "torrents.name LIKE '%" . $searchstr . "%'";
			write_log("User " . $CURUSER["username"] . "," . $CURUSER["ip"] . " is hacking search_area field in" . $_SERVER['SCRIPT_NAME'], 'mod');
			break;
		}
	}
	$addparam .= "search_area=" . $search_area . "&";
	$addparam .= "search=" . rawurlencode($searchstr) . "&".$notnewword;
	$addparam .= "search_mode=".$search_mode."&";
}

$where = implode(" AND ", $wherea);

if ($wherecatin)
$where .= ($where ? " AND " : "") . "category IN(" . $wherecatin . ")";
if ($showsubcat){
if ($wheresourcein)
$where .= ($where ? " AND " : "") . "source IN(" . $wheresourcein . ")";
if ($wheremediumin)
$where .= ($where ? " AND " : "") . "medium IN(" . $wheremediumin . ")";
if ($wherecodecin)
$where .= ($where ? " AND " : "") . "codec IN(" . $wherecodecin . ")";
if ($wherestandardin)
$where .= ($where ? " AND " : "") . "standard IN(" . $wherestandardin . ")";
if ($whereprocessingin)
$where .= ($where ? " AND " : "") . "processing IN(" . $whereprocessingin . ")";
if ($whereteamin)
$where .= ($where ? " AND " : "") . "team IN(" . $whereteamin . ")";
if ($whereaudiocodecin)
$where .= ($where ? " AND " : "") . "audiocodec IN(" . $whereaudiocodecin . ")";
}


if ($allsec == 1 || $enablespecial != 'yes')
{
	if ($where != "")
		$where = "WHERE $where ";
	else $where = "";
	$sql = "SELECT COUNT(*) FROM torrents " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . $where;
}
else
{
	if ($where != "")
		$where = "WHERE $where AND categories.mode = '$sectiontype'";
	else $where = "WHERE categories.mode = '$sectiontype'";
	$sql = "SELECT COUNT(*), categories.mode FROM torrents LEFT JOIN categories ON category = categories.id " . ($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "") . $where." GROUP BY categories.mode";
}

$res = sql_query($sql) or die(mysql_error());
$count = 0;
while($row = mysql_fetch_array($res))
	$count += $row[0];

if ($CURUSER["torrentsperpage"])
$torrentsperpage = (int)$CURUSER["torrentsperpage"];
elseif ($torrentsperpage_main)
	$torrentsperpage = $torrentsperpage_main;
else $torrentsperpage = 50;

if ($count)
{
	if ($addparam != "")
	{
		if ($pagerlink != "")
		{
			if ($addparam{strlen($addparam)-1} != ";")
			{ // & = &amp;
				$addparam = $addparam . "&" . $pagerlink;
			}
			else
			{
				$addparam = $addparam . $pagerlink;
			}
		}
	}
	else
	{
		//stderr("in else","");
		$addparam = $pagerlink;
	}
	//stderr("addparam",$addparam);
	//echo $addparam;

	list($pagertop, $pagerbottom, $limit) = pager($torrentsperpage, $count, "?" . $addparam);
if ($allsec == 1 || $enablespecial != 'yes'){
	//$query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp FROM torrents ".($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "")." $where $orderby $limit";
	//Modified by bluemonster 20111026
	$query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp,torrents.oday FROM torrents ".($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "")." $where $orderby $limit";
}
else{
	//$query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp FROM torrents ".($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "")." LEFT JOIN categories ON torrents.category=categories.id $where $orderby $limit";
	//Modified by bluemonster 20111026
	$query = "SELECT torrents.id, torrents.sp_state, torrents.promotion_time_type, torrents.promotion_until, torrents.banned, torrents.picktype, torrents.pos_state, torrents.category, torrents.source, torrents.medium, torrents.codec, torrents.standard, torrents.processing, torrents.team, torrents.audiocodec, torrents.leechers, torrents.seeders, torrents.name, torrents.small_descr, torrents.times_completed, torrents.size, torrents.added, torrents.comments,torrents.anonymous,torrents.owner,torrents.url,torrents.cache_stamp,torrents.oday FROM torrents ".($search_area == 3 || $column == "owner" ? "LEFT JOIN users ON torrents.owner = users.id " : "")." LEFT JOIN categories ON torrents.category=categories.id $where $orderby $limit";
}

	$res = sql_query($query) or die(mysql_error());
}
else
	unset($res);


?>

<?php

torrenttable_api($res, "torrents");

?>