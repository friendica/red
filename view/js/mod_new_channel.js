	$(document).ready(function() {
		$("#newchannel-name").blur(function() {
			var zreg_name = $("#newchannel-name").val();
			$.get("new_channel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
				$("#newchannel-nickname").val(data);
				zFormError("#newchannel-name-feedback",data.error);
			});
		});
		$("#newchannel-nickname").blur(function() {
			var zreg_nick = $("#newchannel-nickname").val();
			$.get("new_channel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
				$("#newchannel-nickname").val(data);
				zFormError("#newchannel-nickname-feedback",data.error);
			});
		});

	});
