<div id="chatContainer" style="height: 100%; width: 100%; position: absolute; right: 0; bottom: 0;">

    <div id="chatTopBar" style="float: left; height: 80%;"></div>
    <div id="chatLineHolder"></div>

    <div id="chatUsers" style="float: right; width: 120px; height: 100%; border: 1px solid #000;" ></div>

	<div class="clear"></div>
    <div id="chatBottomBar" style="position: absolute; bottom: 0; height: 150px;">
        <div class="tip"></div>

        <form id="chat-form" method="post" action="#">
			<input type="hidden" name="room_id" value="{{$room_id}}" />
            <textarea id="chatText" name="chat_text" rows=3 cols=80></textarea><br />
            <input type="submit" name="submit" value="{{$submit}}" />
        </form>

    </div>

</div>

<script>
var room_id = {{$room_id}};
var last_chat = 0;
var chat_timer = null;

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

}

function update_chats(chats) {

}

</script>
