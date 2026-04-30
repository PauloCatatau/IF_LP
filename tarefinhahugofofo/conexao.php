<?php
$host="localhost"; 
$user="root"; 
$pass=""; 
$banco="tarefinhahugofofo"; 
$conexao=mysqli_connect($host, $user, $pass , $banco); 
mysqli_select_db($conexao, $banco); 
?>