<div id="chatContainer" style="height: 100%; width: 100%;">

    <div id="chatTopBar" style="float: left; height: 400px; width: 650px; overflow-y: auto;">
    	<div id="chatLineHolder"></div>
	</div>

    <div id="chatUsers" style="float: right; width: 120px; height: 100%; border: 1px solid #000;" ></div>

	<div class="clear"></div>
    <div id="chatBottomBar" style="position: relative; bottom: 0; height: 150px; margin-top: 20px;">
        <div class="tip"></div>

        <form id="chat-form" method="post" action="#">
			<input type="hidden" name="room_id" value="{{$room_id}}" />
            <textarea id="chatText" name="chat_text" rows=3 cols=80></textarea><br />
            <input type="submit" name="submit" value="{{$submit}}" />
        </form>

    </div>

</div>
<style>
section {
	padding-bottom: 0;
}
</style>

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

	$.get("chatsvc?f=&room_id=" + room_id + '&last=' + last_chat,function(data) {
		if(data.success) {
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
		$(newNode).html('<img style="height: 32px; width: 32px;" src="' + item.img + '" alt="' + item.name + '" />');
		html.appendChild(newNode); 		
	});
	$('#chatUsers').html(html);	
}

function update_chats(chats) {

	var count = chats.length;
	$.each( chats, function(index, item) {
		last_chat = item.id;
		var newNode = document.createElement('div');
		$(newNode).html('<div style="margin-bottom: 5px; clear: both;"><img style="height: 32px; width: 32px; float: left;" src="' + item.img + '" alt="' + item.name + '" /><div class="chat-body; style="float: left; width: 80%;">' + item.text + '</div></div>');
		$('#chatLineHolder').append(newNode);
		});
	var elem = document.getElementById('chatTopBar');
	elem.scrollTop = elem.scrollHeight;

}

</script>
