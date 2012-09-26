	$(document).ready(function() {
		$("#zchannel-name").blur(function() {
			var zreg_name = $("#zchannel-name").val();
			$.get("zchannel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
				$("#zchannel-nickname").val(data);
				zFormError("#zchannel-name-feedback",data.error);
			});
		});
		$("#zchannel-nickname").blur(function() {
			var zreg_nick = $("#zchannel-nickname").val();
			$.get("zchannel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
				$("#zchannel-nickname").val(data);
				zFormError("#zchannel-nickname-feedback",data.error);
			});
		});

	});
