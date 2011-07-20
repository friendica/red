<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<base href="$baseurl/" />
<meta name="generator" content="$generator" />
<link rel="stylesheet" type="text/css" href="$stylesheet" media="all" />
<link rel="shortcut icon" href="$baseurl/images/friendika-32.png" />
<link rel="search"
         href="$baseurl/opensearch" 
         type="application/opensearchdescription+xml" 
         title="Search in Friendika" />

<!--[if IE]>
<script type="text/javascript" src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script type="text/javascript" src="$baseurl/include/jquery.js" ></script>
<script type="text/javascript" src="$baseurl/library/tinymce/jscripts/tiny_mce/tiny_mce_src.js" ></script>
<script type="text/javascript" src="$baseurl/include/acl.js" ></script>
<script type="text/javascript" src="$baseurl/include/main.js" ></script>
<script>

	function confirmDelete() { return confirm("$delitem"); }
	function commentOpen(obj,id) {
		if(obj.value == '$comment') {
			obj.value = '';
			obj.className = "comment-edit-text-full";
			openMenu("comment-edit-submit-wrapper-" + id);
		}
	}
	function commentClose(obj,id) {
		if(obj.value == '') {
			obj.value = '$comment';
			obj.className="comment-edit-text-empty";
			closeMenu("comment-edit-submit-wrapper-" + id);
		}
	}

</script>


