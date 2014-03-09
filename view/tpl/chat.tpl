<h1>{{$room_name}}</h1>
<div id="chatContainer">

    <div id="chatTopBar">
    	<div id="chatLineHolder"></div>
	</div>

    <div id="chatUsers"></div>

	<div class="clear"></div>
    <div id="chatBottomBar">
        <div class="tip"></div>

        <form id="chat-form" method="post" action="#">
			<input type="hidden" name="room_id" value="{{$room_id}}" />
            <textarea id="chatText" name="chat_text" rows=3 cols=80></textarea><br />
            <input type="submit" name="submit" value="{{$submit}}" />
        </form>

		<a href="{{$baseurl}}/chat/{{$nickname}}/{{$room_id}}/leave">{{$leave}}</a> | <a href="{{$baseurl}}/chatsvc?f=&room_id={{$room_id}}&status=away">{{$away}}</a> | <a href="{{$baseurl}}/chatsvc?f=&room_id={{$room_id}}&status=online">{{$online}}</a>{{if $bookmark_link}} | <a href="{{$bookmark_link}}" target="_blank" >{{$bookmark}}</a>{{/if}}

    </div>

</div>

<script>
var room_id = {{$room_id}};
var last_chat = 0;
var chat_timer = null;

$(document).ready(function() {
	chat_timer = setTimeout(load_chats,300);

});


$('#chat-form').submit(function(ev) {
	$('body').css('cursor','wait');
	$.post("chatsvc", $('#chat-form').serialize(),function(data) {
			if(chat_timer) clearTimeout(chat_timer);
			$('#chatText').val('');
			load_chats();
			$('body').css('cursor','auto');
		},'json');
	ev.preventDefault();
});

function load_chats() {

	$.get("chatsvc?f=&room_id=" + room_id + '&last=' + last_chat + ((stopped) ? '&stopped=1' : ''),function(data) {
		if(data.success && (! stopped)) {
			update_inroom(data.inroom);
			update_chats(data.chats);
		}
	});
	
	chat_timer = setTimeout(load_chats,10000);

}

function update_inroom(inroom) {
	var html = document.createElement('div');
	var count = inroom.length;
	$.each( inroom, function(index, item) {
		var newNode = document.createElement('div');
		$(newNode).html('<img style="height: 32px; width: 32px;" src="' + item.img + '" alt="' + item.name + '" /> ' + item.status + '<br />' + item.name + '<br/>');
		html.appendChild(newNode);
	});
	$('#chatUsers').html(html);	
}

function update_chats(chats) {

	var count = chats.length;
	$.each( chats, function(index, item) {
		last_chat = item.id;
		var newNode = document.createElement('div');
		newNode.setAttribute('class', 'chat-item');
		$(newNode).html('<img class="chat-item-photo" src="' + item.img + '" alt="' + item.name + '" /><div class="chat-body"><span class="chat-item-name">' + item.name + ' </span><span class="autotime chat-item-time" title="' + item.isotime + '">' + item.localtime + '</span><br /><span class="chat-item-text">' + item.text + '</span></div><div class="chat-item-end"></div>');
		$('#chatLineHolder').append(newNode);
		$(".autotime").timeago();

		});
	var elem = document.getElementById('chatTopBar');
	elem.scrollTop = elem.scrollHeight;

}

</script>
<script>
function isMobile() {
if( navigator.userAgent.match(/Android/i)
 || navigator.userAgent.match(/webOS/i)
 || navigator.userAgent.match(/iPhone/i)
 || navigator.userAgent.match(/iPad/i)
 || navigator.userAgent.match(/iPod/i)
 || navigator.userAgent.match(/BlackBerry/i)
 || navigator.userAgent.match(/Windows Phone/i)
 ){
    return true;
  }
 else {
    return false;
  }
}
$(function(){

  $('#chatText').keypress(function(e){
  	if (e.keyCode == 13 && e.shiftKey||isMobile()) {
	}
    else if (e.keyCode == 13) {
	  e.preventDefault();
      $(this).parent('form').trigger('submit');
    }
  });
});
</script>
