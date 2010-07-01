
<div id="profile-jot-wrapper" >
<p id="profile-jot-desc" >
What's on your mind?
</p>
<form id="profile-jot-form" action="item" method="post" onclick="doCheck();" >
<input type="hidden" name="type" value="jot" />
        <div class="richeditor">
                <div class="editbar">
                        <button title="bold" onclick="doClick('bold');" type="button"><b>B</b></button>
                        <button title="italic" onclick="doClick('italic');" type="button"><i>I</i></button>
                        <button title="underline" onclick="doClick('underline');" type="button"><u>U</u></button>
                        <button title="hyperlink" onclick="doLink();" type="button" style="background-image:url('editor/images/url.gif');"></button>
                        <button title="image" onclick="doImage();" type="button" style="background-image:url('editor/images/img.gif');"></button>
                        <button title="list" onclick="doClick('InsertUnorderedList');" type="button" style="background-image:url('editor/images/icon_list.gif');"></button>
                        <button title="color" onclick="showColorGrid2('none')" type="button" style="background-image:url('$baseurl/editor/images/colors.gif');"></button><span id="colorpicker201" class="colorpicker201"></span>
                        <button title="quote" onclick="doQuote();" type="button" style="background-image:url('editor/images/icon_quote.png');"></button>
                        <button title="youtube" onclick="InsertYoutube();" type="button" style="background-image:url('editor/images/icon_youtube.gif');"></button>
                        <button title="switch to source" type="button" onclick="javascript:SwitchEditor()" style="background-image:url('editor/images/icon_html.gif');"></button>
		</div>

<textarea rows="5" cols="64" id="profile-jot-text" name="body" ></textarea>
<script type="text/javascript">initEditor("profile-jot-text", true);</script>

</div>
<div id="profile-jot-submit-wrapper" >
<input type="submit" id="profile-jot-submit" name="submit" value="Submit" onclick="doCheck();" />

</div>
<div id="profile-jot-end"></div>
</div>