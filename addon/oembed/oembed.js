function oembed(){
  $("#oembed").toggleClass('hide');
}

function oembed_do(){
  embed = "[embed]"+$('#oembed_url').attr('value')+"[/embed]";
  
  tinyMCE.execCommand('mceInsertRawHTML',false,embed);
  oembed();
}
