<?php
function oembed_replacecb($matches){
  $embedurl=$matches[1];
  $ourl = "http://oohembed.com/oohembed/?url=".urlencode($embedurl);  
  $txt = fetch_url($ourl);
  $j = json_decode($txt);
  $ret="<!-- oembed $embedurl -->";
  switch ($j->type) {
    case "video": {
       if (isset($j->thumbnail_url)) {
         $tw = (isset($j->thumbnail_width)) ? $j->thumbnail_width:200;
         $th = (isset($j->thumbnail_height)) ? $j->thumbnail_height:180;
         $ret = "<a href='#' onclick='this.innerHTML=unescape(\"".urlencode($j->html)."\").replace(/\+/g,\" \"); return false;' >";
         $ret.= "<img width='$tw' height='$th' src='".$j->thumbnail_url."'>";
         $ret.= "</a>";
       } else {
         $ret=$j->html;
       }
       $ret.="<br>";
    }; break;
    case "photo": {
      $ret = "<img width='".$j->width."' height='".$j->height."' src='".$j->url."'>";
      $ret.="<br>";
    }; break;  
    case "link": {
      //$ret = "<a href='".$embedurl."'>".$j->title."</a>";
    }; break;  
    case "rich": {
      // not so safe.. 
      $ret = "<blockquote>".$j->html."</blockquote>";
    }; break;
  }
  
  $embedlink = (isset($j->title))?$j->title:$embedurl;
  $ret .= "<a href='$embedurl'>$embedlink</a>";
  if (isset($j->author_name)) $ret.=" by ".$j->author_name;
  if (isset($j->provider_name)) $ret.=" on ".$j->provider_name;
  $ret.="<!-- /oembed $embedurl -->";
  return $ret;
}

function oembed_bbcode($text){
	$stopoembed = get_config("system","no_oembed");
	if ($stopoembed == True):
		return preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", "$1" ,$text);
	return preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", oembed_replacecb ,$text);
}
?>