	$(document).ready(function() {
//		$("#id_permissions_role").sSelect();
		$("#newchannel-name").blur(function() {
			$("#name-spinner").spin('small');
			var zreg_name = $("#newchannel-name").val();
			$.get("new_channel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
				$("#newchannel-nickname").val(data);
				zFormError("#newchannel-name-feedback",data.error);
				$("#name-spinner").spin(false);
			});
		});
		$("#newchannel-nickname").blur(function() {
			$("#nick-spinner").spin('small');
			var zreg_nick = $("#newchannel-nickname").val();
			$.get("new_channel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
				$("#newchannel-nickname").val(data);
				zFormError("#newchannel-nickname-feedback",data.error);
				$("#nick-spinner").spin(false);
			});
		});

	});
