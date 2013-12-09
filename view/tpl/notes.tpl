<div class="widget">
<script>
function notePost() {
	$('#note-rotator').spin('tiny');
	$.post('notes', { 'note_text' : $('#note-text').val() },function(data) { $('#note-rotator').spin(false); });
}
</script>

<h3>{{$banner}}</h3>
<textarea name="note_text" id="note-text">{{$text}}</textarea>
<input type="submit" name="submit" id="note-save" value="{{$save}}" onclick="notePost(); return true;">
<div id="note-rotator"></div>
</div>
