<?php
    setcookie("registered", 1, time()+3600, "", "", false, true);
    echo $_POST["json"];
?>
