<html>
  <body>
   <h1>SE CADASTRA SE É CUIUDO</h1>
   <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      background-image: url('https://media.tenor.com/WlJsOVX2lysAAAAj/cat-tongue-cat.gif');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
  </style>
   <form method="post" action="">
    <label>Email ou Numero</label>
    <input name="emailnumero" size="25" type="text" autocomplete="off" required>
    <br><br>
    <label>Senha</label>
    <input name="senha" size="10" type="password" autocomplete="off" required>
    <br><br>
    <button type="submit" name="cadastrar">Cadastrar</button>
   </form>
  </body>
</html>

<?php
include "conexao.php";

if (isset($_POST['cadastrar'])):

    $emailnumero = trim($_POST['emailnumero']); 
    $senha = trim($_POST['senha']);    
   if (strlen($senha) < 8 || !preg_match('/[A-Z]/', $senha) || !preg_match('/[a-z]/', $senha) || !preg_match('/[0-9]/', $senha) || !preg_match('/[!@#$%^&*()]/', $senha)) {
    echo "Senha: 8+ caracteres, letra maiúscula, letra minúscula, número e caractere especial paizão!!!!!!";
    exit;
}

    // Função para validar CPF
    function validaCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf); // só números
        if (strlen($cpf) != 11) return false; // conta o número de coiso
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false; // a soma dá errado zé
        }
        return true;
    }

    $is_cpf = validaCPF($emailnumero); 

    $is_email = filter_var($emailnumero, FILTER_VALIDATE_EMAIL);

    $is_telefone = preg_match('/^[0-9]{10,11}$/', $emailnumero);

    if (!$is_email && !$is_telefone && !$is_cpf) {
        echo "Bota algo que presta macho! >:(";
        exit;
    }


    if ($is_cpf) {
        $emailnumero_final = password_hash($emailnumero, PASSWORD_DEFAULT);
    } else {
        $emailnumero_final = $emailnumero;
    } // esconde o cpf dos vilão

    $senha_final = password_hash($senha, PASSWORD_DEFAULT);

    $sql = mysqli_query($conexao, 
        "INSERT INTO `teste`(`emailnumero`, `senha`) 
         VALUES ('$emailnumero_final','$senha_final')"
    );

    if ($sql) {
        echo "É cuiudo mesmo!!!!"; // se conecta com o sql
    } else {
        echo "Erro ao comprovar cuiosidade: " . mysqli_error($conexao); // se der errado manda essa 
    } 

endif;
?>