<script type="text/javascript" src="$baseurl/view/theme/diabook/js/jquery.autogrow.textarea.js"></script>
<script type="text/javascript">
$(document).ready(function() {

});
function tautogrow(id){
		$("textarea#comment-edit-text-" +id).autogrow(); 	
 	};
$(document).ready(function() {
    $("iframe").each(function(){
        var ifr_source = $(this).attr("src");
        var wmode = "wmode=transparent";
        if(ifr_source.indexOf("?") != -1) {
            var getQString = ifr_source.split("?");
            var oldString = getQString[1];
            var newString = getQString[0];
            $(this).attr("src",newString+"?"+wmode+"&"+oldString);
        }
        else $(this).attr("src",ifr_source+"?"+wmode);
       
    });
      
	});

	function yt_iframe() {

	$("iframe").load(function() { 
	var ifr_src = $(this).contents().find("body iframe").attr("src");
	$("iframe").contents().find("body iframe").attr("src", ifr_src+"&wmode=transparent");
    });

	};

	function scrolldown(){
			$("html, body").animate({scrollTop:$(document).height()}, "slow");
			return false;
		};
		
	function scrolltop(){
			$("html, body").animate({scrollTop:0}, "slow");
			return false;
		};
	 	
	$(window).scroll(function () { 
		
		var footer_top = $(document).height() - 30;
		$("div#footerbox").css("top", footer_top);
	
		var scrollInfo = $(window).scrollTop();      
		
		if (scrollInfo <= "900"){
      $("a#top").attr("id","down");
      $("a#down").attr("onclick","scrolldown()");
	 	$("img#scroll_top_bottom").attr("src","view/theme/diabook/icons/scroll_bottom.png");
	 	} 
	 	    
      if (scrollInfo > "900"){
      $("a#down").attr("id","top");
      $("a#top").attr("onclick","scrolltop()");
	 	$("img#scroll_top_bottom").attr("src","view/theme/diabook/icons/scroll_top.png");
	 	}
		
    });
  

	function insertFormatting(comment,BBcode,id) {
	
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == comment) {
			tmpStr = "";
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
								}

	textarea = document.getElementById("comment-edit-text-" +id);
	if (document.selection) {
		textarea.focus();
		selected = document.selection.createRange();
		if (BBcode == "url"){
			selected.text = "["+BBcode+"]" + "http://" +  selected.text + "[/"+BBcode+"]";
			} else			
		selected.text = "["+BBcode+"]" + selected.text + "[/"+BBcode+"]";
	} else if (textarea.selectionStart || textarea.selectionStart == "0") {
		var start = textarea.selectionStart;
		var end = textarea.selectionEnd;
		if (BBcode == "url"){
			textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + "http://" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
			} else
		textarea.value = textarea.value.substring(0, start) + "["+BBcode+"]" + textarea.value.substring(start, end) + "[/"+BBcode+"]" + textarea.value.substring(end, textarea.value.length);
	}
	return true;
	}

	function cmtBbOpen(id) {
	$(".comment-edit-bb-" + id).show();
	}
	function cmtBbClose(id) {
	$(".comment-edit-bb-" + id).hide();
	}
</script>
