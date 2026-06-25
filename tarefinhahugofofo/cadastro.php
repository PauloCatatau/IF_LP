<html>
<head>
<style>
    body {
        background-image: url('https://media1.tenor.com/m/LK628grSBnEAAAAC/cat-ai-pufferfish-cat.gif');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: auto;
        margin: 0;
        color: greenyellow; 
    }
</style>
</head>
<body>
<h1>SE CADASTRA SE É CUIUDO</h1>

<form method="post" action="">
    <label>Email ou Numero</label>
    <input name="emailnumero" size="25" type="text" autocomplete="off" required placeholder="Ex: cuiudo@email.com, 1199999999">
    <br><br>

    <label>Senha</label>
    <input name="senha1" size="25" type="password" autocomplete="off" required placeholder="Mín. 8 caracteres, 1 maiúscula, 1 número, 1 símbolo">
    <br><br>
    
    <label> Confirmar senha</label>
    <input name="senha2" size="25" type="password" autocomplete="off" required placeholder="Repita a senha">
    <br><br>	

    <button type="submit" name="cadastrar">Cadastrar</button>
</form>

<form method="post" action="">
    <button type="submit" name="1">Já é Cuiudo? não perdi tempo, entra em nois zé!</button>
</form>
</body>
</html>

<?php
include "conexao.php";

if (isset($_POST['cadastrar'])):

    $emailnumero = trim($_POST['emailnumero']); 
    $senha1 = trim($_POST['senha1']);
    $senha2 = trim($_POST['senha2']);

    if ($senha1 !== $senha2) {
        echo "Senha diferente, digita igual aí!";
        exit;
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[\W_]).{8,}$/', $senha1)) {
        echo "Senha fraca! Precisa: 8+ caracteres, maiúscula, minúscula, número e símbolo!";
        exit;
    }

    $is_email = filter_var($emailnumero, FILTER_VALIDATE_EMAIL);
    $is_telefone = preg_match('/^[0-9]{10,11}$/', $emailnumero);

    if (!$is_email && !$is_telefone) {
        echo "Bota algo que presta macho! >:(";
        exit;
    }

    $senhafinal = password_hash($senha1, PASSWORD_DEFAULT);

    $sql = mysqli_query($conexao, 
        "INSERT INTO cad_user (emailnumero, senha) 
         VALUES ('$emailnumero','$senhafinal')"
    );

    if ($sql) {
        echo "É cuiudo mesmo!!!!";
    } else {
        echo "Erro ao comprovar cuiosidade: é macio (lá ele). " . mysqli_error($conexao);
    }

endif;

if (isset($_POST['1'])):
    header("Location: login.php");
    exit;
endif;
?>
