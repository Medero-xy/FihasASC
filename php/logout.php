<?php
session_start();
session_destroy(); // Destrói a sessão
header("Location: ../index.html"); // Volta para o login
exit;
?>