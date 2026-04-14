<?php
$host="localhost"; 
$user="root"; 
$pass=""; 
$banco="aula4"; 
$conexao=mysqli_connect($host, $user, $pass , $banco); 
mysqli_select_db($conexao, $banco); 
?>