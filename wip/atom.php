<?php 

$month = 0;
$year = 0;
$sort='';
$category='';
$category_id = 0;
$author = '';
$dest = '';
$rums = false;
$blgs = false;

$q = '';
$glbs = false;

$pagenum = 1;
$articles_page = 20;
$startrecord = 0;

  $query_saved = substr($_SERVER['QUERY_STRING'],2); // strip '?=', save
  





  $query_original = substr($query_saved,4);      // strip 'atom'

  $query_original = trim($query_original,'/');

  ereg("[/]?page=([0-9]*)",$query_original,$va);
  
  if(((count($va)) > 1) && ($va[1] > 0)) {
    $pagenum = $va[1];
    $query_original = ereg_replace("([/]?page=[0-9]*)","",$query_original);
    $startrecord = ($pagenum * $articles_page) - $articles_page;
  }


  if(substr($query_original,-4,4) == 'text') {
    $type = 'text';
    $query_original = substr($query_original,0,-4);
  }

  $query_original = trim($query_original,'/');
  
  if(substr($query_original,0,5) == 'forum') {
    $rums = true;
    $query_original = substr($query_original,5);
  }

  $query_original = trim($query_original,'/');
  
  $cmd = explode('/',$query_original);

  if($rums) {
    if($cmd[0] == 'category') {
      $category = $cmd[1];       
    }
    else
      $dest = $cmd[0];
  }
  else {
    switch (count($cmd)) {
      case 2:
        if($cmd[0] == 'share') {
          $share = $cmd[1];
          break;
        }
        if(strlen($cmd[1])) {
          $blgs     = true;
          $category = $cmd[1];
        }

        $author     = $cmd[0];
        break;

      case 1:
        if(strlen($cmd[0])) {
          $blgs       = true;
          $author     = $cmd[0];
        }
        break;

      default:
        break;

    }
  }

// build the return link

$href = $SITE_URL;
if($blgs)
  $href .= preg_replace("@^atom@","/weblog",$query_saved);
else
  $href .= preg_replace("@^atom@","",$query_saved);
$href = preg_replace("@/text$@","",$href);
$href = preg_replace("@/share/@","/view/",$href);

session_start();
session_write_close();

if((xint($SITE_RESTRICTED)) && (! xint($_SESSION['authenticated'])))
  exit;

if($type == "text")
  header("Content-type: text/plain");
else
  header("Content-type: application/atom+xml");
echo '<?xml version="1.0" encoding="utf-8" ?>'."\r\n"; 

include("globs.php");
include("sql.php");

$sitesubs = get_default_subs();

// system defaults
$blog_url    = $SITE_URL;
$blog_title  = xmlificator($SITE_TITLE);
$blog_desc   = xmlificator($BLOG_DESC);
$blog_author = xmlificator($BLOG_AUTHOR);
$blog_email  = xmlificator($BLOG_EMAIL);
$blog_logo   = $BLOG_LOGO;
if((strlen($blog_logo)) && (! strstr($blog_logo,"://")))
  $blog_logo = $SITE_URL.'/'.$blog_logo;

$category = dbesc($category);
$author = dbesc($author);
$dest = dbesc($dest);

if(strlen($category) && (! ($category_id = category::get_cat_id($category,$author))))
  $category = '';

if(strlen($author)) {
  $author_info = get_author($author);
  if(count($author_info)) {
    $blog_url = xmlificator($SITE_URL.'/weblog/'.$author);
    if(strlen($author_info[0]['blogname']))
      $blog_title = xmlificator($author_info[0]['blogname']);
    if(strlen($author_info[0]['blogdesc']))
      $blog_desc = xmlificator($author_info[0]['blogdesc']);
    if(strlen($author_info[0]['fullname']))
      $blog_author = xmlificator($author_info[0]['fullname']);
    if(strlen($author_info[0]['email']))
      $blog_email = xmlificator($author_info[0]['email']);
    if(strlen($author_info[0]['bloglogo'])) {
      $blog_logo = xmlificator($author_info[0]['bloglogo']);
    if((strlen($blog_logo)) && (! strstr($blog_logo,"://")))
        $blog_logo = $SITE_URL.'/'.$blog_logo;
    }
  }
}

if(strlen($share)) {
  $x = get_user($share);
  if(count($x) 
    && ($x[0]['share']) 
    && (! $x[0]['lurker'])
    && (! $x[0]['sharecensor'])
    && (! $x[0]['sharemutual'])) {

    if(xstr($x[0]['sitetitle']))
      $blog_title = $x[0]['sitetitle'];
    if(xstr($x[0]['sitedesc']))
      $blog_desc = $x[0]['sitedesc'];
    if(xstr($x[0]['sitelogo']))
      $blog_logo = $x[0]['sitelogo'];

    $newsubs = get_subs($x[0]['name']);
    $groups = groupsinit();
    unset($sitesubs);
    $sitesubs = array();
    foreach($newsubs as $sub) {
      $p = fetch_permissions('forum',$sub);
      if(check_access($_SESSION['username'], $groups, '',$p))
        $sitesubs[] = $sub;
    }

  }

}


$media = getmediamap();
$build_date = gmdate('c');
$copyright = 'Copyright '.date('Y').' '.$blog_author;
$canonical = $SITE_URL;
if(substr($canonical,0,-1) != '/')
  $canonical .= '/';

$self = $canonical;
if(substr($query_saved,0,1) == '/')
 $self .= substr($query_saved,1);
else
 $self .= $query_saved;

$subtitle = '';
if(strlen($blog_desc))
  $subtitle = "<subtitle>$blog_desc</subtitle>";

echo <<< EOT
<feed xmlns="http://www.w3.org/2005/Atom"
      xmlns:thr="http://purl.org/syndication/thread/1.0" >

 <id>$canonical</id>
 <title>$blog_title</title>
 <link rel="alternate" href="$href" />
 <updated>$build_date</updated>
 $subtitle

EOT;

if(strlen($blog_logo)) {
echo <<< EOT
 <logo>$blog_logo</logo>

EOT;
}

  $article_id_list = array();

    $ss = array();

    $ss['tolerance'] = 0;

    if($category_id)
      $ss['category_id'] = $category_id;

    if($blgs) {
      $ss['type'] = 'weblog';
      $ss['author'] = $author;
    }

    if($rums) {
      $ss['type'] = 'forum';
      $ss['forum'] = $dest;
    }


    $ss['countonly'] = true;

    $rt = article_query($ss);
    $totalarticles = $rt[0]['total'];

    echo " <link rel=\"self\" href=\"$self\" />\r\n";

    $self = ereg_replace("([/]?page=[0-9]*)","",$self);

    if($totalarticles > $articles_page) {

      if($pagenum != 1) {
        $ppage = $pagenum - 1;
        echo " <link rel=\"previous\" href=\"$self/page=$ppage\" />\r\n";
      }
      echo " <link rel=\"first\" href=\"$self/".'page=1'."\" />\r\n";

      $numpages = $totalarticles / $articles_page;

      $lastpage = (($numpages > intval($numpages)) 
         ? intval($numpages)+1 
         : $numpages);

      echo " <link rel=\"last\" href=\"$self/page=$lastpage\" />\r\n";

      if(($totalarticles - ($articles_page * $pagenum)) > 0) {
        $npage = $pagenum + 1;
        echo " <link rel=\"next\" href=\"$self/page=$npage\" />\r\n";
      }
    }


    unset($ss['countonly']);

    $ss['startat'] = $startrecord;
    $ss['articles_page'] = $articles_page;

    $r = article_query($ss);    

  if(count($r)) {
    foreach($r as $rr) {
      $article_id_list[] = "'".$rr['id']."'";
    }
  }

  if(count($article_id_list)) {
    $rcc = fetch_comments_by_list($article_id_list,'article');
    $ratt = fetch_attachments_by_list($article_id_list);
  }

  foreach($r as $rr) {

    $link = "{$SITE_URL}/article/{$rr['guid']}";
    $datestr = $rr['created'];    
    $pubdate = gmdate('c',strtotime($datestr.' +0000'));
    $datestr = $rr['edited'];    
    $editdate = gmdate('c',strtotime($datestr.' +0000'));
    if(strstr($rr['type'],'news')) {
      if(strlen($rr['ext_author']))
        $authorstr = "<author><name>{$rr['ext_author']}</name></author>\r\n";
      else
        $authorstr = "<author><name>External Author</name></author>\r\n";
       
    }
    else {
      if(strlen($rr['fullname']))
        $authorstr = "<author><name>{$rr['fullname']}</name></author>\r\n";
      else
        $authorstr = "<author><name>{$rr['author']}</name></author>\r\n";
    }
    $guid = "urn:uuid:{$rr[guid]}";
    $comments = "{$SITE_URL}/comments/{$rr['guid']}";

    $contents = $rr['body'];

    if((! $rr['approved']) && (! strstr($rr['type'], 'news')))
      $contents = filter_images($contents);

    $contents = macro_youtube($contents);
    $contents = reltoabs($contents,$SITE_URL.'/');

    $title = xmlificator($rr['title']);
    if(! strlen($title)) {
      $title = xmlificator(substr($datestr,0,11));

      // Use the first 28 chars of text for the title-summary. Watch that 
      // we don't end up with a  _partial_ escape sequence at the end.

      $stripped = xmlificator(strip_tags($contents));

      $len = 28;
      $ok = 0;
      while(! $ok) {
        $summary=substr($stripped,0,$len);
        if(($teststr = strrchr($summary,"&")) && (! strchr($teststr,";"))) {
          $len --;
        }
        else {
          $ok = 1;
          break;
        }
      }

      // Chopped off words don't look pretty in an RSS title
 
      if(strrpos($summary,' ') > 10)
        $title .= ' '.substr($summary,0,(strrpos($summary,' ')));
    }

    $contents .= feed_attachments($ratt,$rr['id']);
    $contents .= feed_comments($rcc,$rr['id'],$rr['guid'],$rr['rank']);

    $contents = reltoabs($contents,$SITE_URL.'/');

    $contents = xmlificator($contents);

    // output the item

echo <<< EOT

<entry>
 <id>$guid</id>
 <title type="html">$title</title>
 <published>$pubdate</published>
 <updated>$editdate</updated>
 <link rel="alternate" href="$link" />
 <content type="html">$contents</content>
 $authorstr

EOT;

    $rc = category::categories_by_article($rr['id']);
    if(count($rc))
      for($x = 0; $x < count($rc); $x ++)
        echo ' <category term="'.xmlificator($rc[$x]).'"/>'."\r\n";

    $rat = attachments_fetch($rr['id']);
    if(count($rat)) {
      foreach($rat as $att) {
        $filename = $att['filename'];
        $ext = substr($filename,strrpos($filename,'.'));
        $type = 'application/octet-stream';
        foreach($media as $m) {
          if($m[0] != $ext)
            continue;
          $type = $m[2];
            break;
        }
        echo ' <link rel="enclosure" href="'.$SITE_URL.'/'.xmlificator($filename).'" length="'.$att['size'].'" type="'.$type.'" />'."\r\n";
        
      }
    }
    echo "</entry>\r\n\r\n";

//    if(($ALLOW_COMMENTS == "1") && (count($rcc))) {
//      foreach($rcc as $c) { 
//        if($c['tid'] != $rr['id'])
//          continue;
//        $cguid = "urn:uuid:{$c['guid']}";
//        $datestr = $c['created'];    
//        $cpubdate = date(c,strtotime($datestr));
//        
//        $ccontents = format_avatar($c['avatar'],$c['author'],$c['userid'],
//                                   $c['censored'],0,'',64).'<br />';
//        $ccontents .= $c['body'];
//
//        $ccontents = filter_images($ccontents);
//        $ccontents = reltoabs($ccontents,$SITE_URL.'/');
//        $ccontents = xmlificator($ccontents);
//        if(strlen($c['fullname']))
//          $cauthor = xmlificator($c['fullname']);
//        else
//          $cauthor = xmlificator($c['author']);        
//        $cauthorstr = '<author><name>'.$cauthor.'</name>';
//        if(strlen($c['url']))
//          $cauthorstr .= '<uri>'.xmlificator($c['url']).'</uri>';
//        $cauthorstr .= '</author>';
//
//echo <<< EOT
//<entry>
//<thr:in-reply-to ref="$guid" />
//<id>$cguid</id>
//<title type="html">Re: $title</title>
//<link rel="related" href="$link" />
//<published>$cpubdate</published>
//<updated>$cpubdate</updated>
//<content type="html">$ccontents</content>
//$cauthorstr
//</entry>
//
//EOT;
//}
//}


  }

  echo "</feed>\r\n";

?>
