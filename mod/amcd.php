<?php

function amcd_content(&$a) {
header("Content-type: text/json");
echo <<< EOT
{
  "methods": {
    "username-password-form": {
      "connect": {
        "method":"POST",
        "path":"/login",
        "params": {
          "username":"login-name",
          "password":"password"
        }
      },
      "disconnect": {
        "method":"GET",
        "path":"/logout"
      }
    }
  }
}
EOT;
killme();
}