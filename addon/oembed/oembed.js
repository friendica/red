function oembed(){
  $("#oembed").toggleClass('hide');
}

function oembed_do(){
  embedurl = $('#oembed_url').attr('value');
  var url = 'http://oohembed.com/oohembed/?url='+escape( embedurl )+"&callback=?";
  
  $.getJSON(url, function(data) {
    var ret="";
    switch(data.type){
      case "video": {
        if (data.thumbnail_url){
          tw = 200; if (data.thumbnail_width) tw=data.thumbnail_width;
          th = 180; if (data.thumbnail_height) tw=data.thumbnail_height;
          ret = "<a href='"+embedurl+"'>";
          // tiny mce bbcode plugin not support image size......
          ret += "<img width='"+tw+"' height='"+th+"' src='"+data.thumbnail_url+"'></a>";
        } else {
          ret = data.html;  
        }
      }; break;
      case "photo": {
          // tiny mce bbcode plugin not support image size......        
          ret = "<img width='"+data.width+"' height='"+data.height+"' src='"+data.url+"'>";
      }; break;
      case "link": {
          ret = "<a href='"+embedurl+"'>"+data.title+"</a>";
      }; break;
      case "rich": {
          ret = data.html; // not so safe... http://www.oembed.com/ : "Consumers may wish to load the HTML in an off-domain iframe to avoid XSS" 
      }; break;
      default: {
        alert("Error retriving data!");
        return;
      }
    }
    var embedlink = embedurl;
    if (data.title) embedlink = data.title
    ret+="<br><a href='"+embedurl+"'>"+embedlink+"</a>";
    if (data.author_name) {
      ret+=" by "+data.author_name;
    }
    if (data.provider_name) {
      ret+=" on "+data.provider_name;
    }
    tinyMCE.execCommand('mceInsertRawHTML',false,ret);
    oembed();
  });
  
}
