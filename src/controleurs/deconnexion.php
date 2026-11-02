<?php
include_once "getRacine.php";
include_once "$racine/src/modeles/authentification.php";

logout();
header("Location: index.php");
exit;

?>
