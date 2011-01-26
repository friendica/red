<?php
/**
 * oembed plugin
 * 
 * oEmbed is a format for allowing an embedded representation of a URL on third party sites
 * http://www.oembed.com/
 * 
 */
 
function oembed_install() {
  register_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
  register_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
  register_hook('bbcode', 'addon/oembed/oembed.php', 'oembed_hook_bbcode');
}

function oembed_uninstall() {
  unregister_hook('jot_tool', 'addon/oembed/oembed.php', 'oembed_hook_jot_tool');
  unregister_hook('page_header', 'addon/oembed/oembed.php', 'oembed_hook_page_header');
  unregister_hook('bbcode', 'addon/oembed/oembed.php', 'oembed_hook_bbcode');
}

function oembed_hook_page_header($a, &$b){
  $b .= '<script src="addon/oembed/oembed.js"></script>
  <style>#oembed.hide { display: none } 
  #oembed {
     display:block; position: absolute; width: 300px; height:200px;
     background-color:#fff; color: #000;
     border:2px solid #8888FF; padding: 1em;
     top: 200px; left: 400px; z-index:2000;  
   }
  #oembed_url { width: 100%; margin-bottom:3px;}
   </style>';
  
  $b .= '
  <div id="oembed" class="hide"><input id="oembed_url">&nbsp;
    <input type="button" value="Embed" onclick="oembed_do()" style="float:left;">
    <a onclick="oembed(); return false;" style="float:right;"><img onmouseout="imgdull(this);" onmouseover="imgbright(this);" class="wall-item-delete-icon" src="images/b_drophide.gif" style="width: 16px; height: 16px;"></a>
    <p style="clear:both">Paste a link from 5min.com, Amazon Product Image, blip.tv, Clikthrough, CollegeHumor Video, 
      Daily Show with Jon Stewart, Dailymotion, dotSUB.com, Flickr Photos, Funny or Die Video, 
      Google Video, Hulu, Kinomap, LiveJournal UserPic, Metacafe, National Film Board of Canada, 
      Phodroid Photos, Photobucket, Qik Video, Revision3, Scribd, SlideShare, TwitPic, Twitter Status, 
      Viddler Video, Vimeo, Wikipedia, Wordpress.com, XKCD Comic, YFrog, YouTube</p> 
  </div>
  ';
}


function oembed_hook_jot_tool($a, &$b) {
  $b .= '
    <div class="tool-wrapper" style="display: $visitor;" >
      <img class="tool-link" src="addon/oembed/oembed.png" alt="Embed" title="Embed" onclick="oembed();" />
    </div> 
  ';
}

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

function oembed_hook_bbcode($a, &$text){
  $text = preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", oembed_replacecb ,$text);
}


?>