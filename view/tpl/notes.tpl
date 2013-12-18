<div class="widget">
<script>
$("#note-text").live('input paste',function(e){
	$.post('notes', { 'note_text' : $('#note-text').val() });
});
</script>

<h3>{{$banner}}</h3>
<textarea name="note_text" id="note-text">{{$text}}</textarea>
</div>
