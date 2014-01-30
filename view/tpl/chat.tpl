<div id="chatContainer">

    <div id="chatTopBar" class="rounded"></div>
    <div id="chatLineHolder"></div>

    <div id="chatUsers" class="rounded"></div>

    <div id="chatBottomBar" class="rounded">
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

$('#chat-form').submit(function(ev) {
	$('body').css('cursor','wait');
	$.post("chatsvc", $('#chat-form').serialize(),function(data) {
			load_chats(data);
			$('body').css('cursor','auto');
		},'json');
	ev.preventDefault();
});

function load_chats(data) {
	var chat_data = data;
	if(! data) {
		$.get("chatsvc?f=&room_id=" + room_id,function(data) {
			chat_data = $this;
		});
	}


}
</script>
