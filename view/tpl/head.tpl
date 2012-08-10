<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl/" />
<meta name="generator" content="$generator" />

<!--[if IE]>
<script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

$head_css

$head_js

<link rel="shortcut icon" href="$baseurl/images/friendica-32.png" />
<link rel="search"
         href="$baseurl/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in Friendica" />

<script>

	var updateInterval = $update_interval;
	var localUser = {{ if $local_user }}$local_user{{ else }}false{{ endif }};

	function confirmDelete() { return confirm("$delitem"); }
	function commentOpen(obj,id) {
		if(obj.value == '$comment') {
			obj.value = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).show();
			openMenu("comment-edit-submit-wrapper-" + id);
		}
	}
	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = '$comment';
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).addClass("comment-edit-text-empty");
			$("#mod-cmnt-wrap-" + id).hide();
			closeMenu("comment-edit-submit-wrapper-" + id);
		}
	}

	function showHideCommentBox(id) {
		if( $('#comment-edit-form-' + id).is(':visible')) {
			$('#comment-edit-form-' + id).hide();
		}
		else {
			$('#comment-edit-form-' + id).show();
		}
	}


	function commentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == '$comment') {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).html();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
	}

	function qCommentInsert(obj,id) {
		var tmpStr = $("#comment-edit-text-" + id).val();
		if(tmpStr == '$comment') {
			tmpStr = '';
			$("#comment-edit-text-" + id).addClass("comment-edit-text-full");
			$("#comment-edit-text-" + id).removeClass("comment-edit-text-empty");
			openMenu("comment-edit-submit-wrapper-" + id);
		}
		var ins = $(obj).val();
		ins = ins.replace('&lt;','<');
		ins = ins.replace('&gt;','>');
		ins = ins.replace('&amp;','&');
		ins = ins.replace('&quot;','"');
		$("#comment-edit-text-" + id).val(tmpStr + ins);
		$(obj).val('');
	}

	function showHideComments(id) {
		if( $('#collapsed-comments-' + id).is(':visible')) {
			$('#collapsed-comments-' + id).hide();
			$('#hide-comments-' + id).html('$showmore');
		}
		else {
			$('#collapsed-comments-' + id).show();
			$('#hide-comments-' + id).html('$showfewer');
		}
	}

	// jquery.timeago localisations

	var tago01 = $t01 ;
	var tago02 = $t02 ;
	var tago03 = "$t03" ;
	var tago04 = "$t04" ;
	var tago05 = "$t05" ;
	var tago06 = "$t06" ;
	var tago07 = "$t07" ;
	var tago08 = "$t08" ;
	var tago09 = "$t09" ;
	var tago10 = "$t10" ;
	var tago11 = "$t11" ;
	var tago12 = "$t12" ;
	var tago13 = "$t13" ;
	var tago14 = "$t14" ;
	var tago15 = "$t15" ;
	var tago16 = "$t16" ;
	var tago17 = $t17 ;

</script>


