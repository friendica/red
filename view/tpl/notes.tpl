<div class="widget">
<script>
$(document).on('focusout',"#note-text",function(e){
	$.post('notes', { 'note_text' : $('#note-text').val() });
});
</script>

<h3>{{$banner}}</h3>
<textarea name="note_text" id="note-text">{{$text}}</textarea>
</div>
