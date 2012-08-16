	$(document).ready(function() {
		$("#zregister-email").blur(function() {
			var zreg_email = $("#zregister-email").val();
			$.get("zregister/email_check.json?f=&email=" + encodeURIComponent(zreg_email),function(data) {
				$("#zregister-email-feedback").html(data.message);
				zFormError("#zregister-email-feedback",data.error);
			});
		});
		$("#zregister-password").blur(function() {
			if(($("#zregister-password").val()).length < 6 ) {
				$("#zregister-password-feedback").html(aStr['pwshort']);
				zFormError("#zregister-password-feedback",true);
			}
			else {
				$("#zregister-password-feedback").html("");
				zFormError("#zregister-password-feedback",false);
			}
		});
		$("#zregister-password2").blur(function() {
			if($("#zregister-password").val() != $("#zregister-password2").val()) {
				$("#zregister-password2-feedback").html(aStr['pwnomatch']);
				zFormError("#zregister-password2-feedback",true);
			}
			else {
				$("#zregister-password2-feedback").html("");
				zFormError("#zregister-password2-feedback",false);
			}
		});
	});

</script>
