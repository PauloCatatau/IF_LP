<?php
session_start();
include "conexao.php";

if (isset($_POST['login'])) {
    $emailnumero = trim($_POST['emailnumero']);
    $senha = trim($_POST['senha']);

    $is_email = filter_var($emailnumero, FILTER_VALIDATE_EMAIL);
    $is_telefone = preg_match('/^[0-9]{10,11}$/', $emailnumero);

    if (!$is_email && !$is_telefone) {
        $erro = "Bota algo que presta macho! >:(";
    } else {
        $stmt = $conexao->prepare("SELECT id, emailnumero, senha FROM cad_user WHERE emailnumero = ?");
        $stmt->bind_param("s", $emailnumero);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();

            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario'] = $emailnumero;
                $_SESSION['id'] = $usuario['id'];
                $_SESSION['emailnumero'] = $usuario['emailnumero'];

                header("Location: telainicial.php");
                exit;
            } else {
                $erro = "Senha errada!";
            }
        } else {
            $erro = "Usuário não encontrado!";
        }

        $stmt->close();
    }
}

if (isset($_POST['cadastro'])) {
    header("Location: cadastro.php");
    exit;
}
?>

<html>
<head>
<style>
    body {
        background-image: url('https://media1.tenor.com/m/COlIBa50gfQAAAAC/freaky-ahh-cat.gif');
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        height: auto;
        margin: 0;
        color: greenyellow;
        text-align: center;
    }

    input, button {
        padding: 8px;
        border-radius: 5px;
        border: none;
    }

    button { cursor: pointer; }
</style>
</head>
<body>

<h1>JÁ É CUIUDO? ENTRA EM NOIS!</h1>

<?php if (isset($erro)) echo "<p>$erro</p>"; ?>

<form method="post">
    <label>Email ou Numero</label>
    <input name="emailnumero" size="25" type="text" required>
    <br><br>

    <label>Senha</label>
    <input name="senha" size="25" type="password" required>
    <br><br>
    
    <button type="submit" name="login">Login</button>
</form>

<p><a href="senha.php" style="color: #3bff5f; text-decoration: none;">Esqueci minha senha</a></p>

<br>

<form method="post">
    <button type="submit" name="cadastro">Voltar para o cadastro</button>
</form>

</body>
</html>
