	$(document).ready(function() {
		$("#zentity-name").blur(function() {
			var zreg_name = $("#zentity-name").val();
			$.get("zentity/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
				$("#zentity-nickname").val(data);
				zFormError("#zentity-name-feedback",data.error);
			});
		});
		$("#zentity-nickname").blur(function() {
			var zreg_nick = $("#zentity-nickname").val();
			$.get("zentity/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
				$("#zentity-nickname").val(data);
				zFormError("#zentity-nickname-feedback",data.error);
			});
		});

	});
