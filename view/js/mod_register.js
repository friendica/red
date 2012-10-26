	$(document).ready(function() {
		$("#register-email").blur(function() {
			var zreg_email = $("#register-email").val();
			$.get("register/email_check.json?f=&email=" + encodeURIComponent(zreg_email),function(data) {
				$("#register-email-feedback").html(data.message);
				zFormError("#register-email-feedback",data.error);
			});
		});
		$("#register-password").blur(function() {
			if(($("#register-password").val()).length < 6 ) {
				$("#register-password-feedback").html(aStr['pwshort']);
				zFormError("#register-password-feedback",true);
			}
			else {
				$("#register-password-feedback").html("");
				zFormError("#register-password-feedback",false);
			}
		});
		$("#register-password2").blur(function() {
			if($("#register-password").val() != $("#register-password2").val()) {
				$("#register-password2-feedback").html(aStr['pwnomatch']);
				zFormError("#register-password2-feedback",true);
			}
			else {
				$("#register-password2-feedback").html("");
				zFormError("#register-password2-feedback",false);
			}
		});
	});
