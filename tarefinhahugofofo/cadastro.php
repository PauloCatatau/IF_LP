<?php
include "conexao.php";
include "cadastro.html";

if (isset($_POST['cadastrar'])):

    $emailnumero = trim($_POST['emailnumero']); 
    $senha = trim($_POST['senha']);
    $confirmar_senha = trim($_POST['confirmar_senha']);

    // Confere se as senhas batem
    if ($senha !== $confirmar_senha) {
        echo "As senha tão diferente, macho! Digita igual aí!";
        exit;
    }

    // Força da senha (a sua original)
    if (strlen($senha) < 8 || 
        !preg_match('/[A-Z]/', $senha) || 
        !preg_match('/[a-z]/', $senha) || 
        !preg_match('/[0-9]/', $senha) || 
        !preg_match('/[!@#$%^&*()]/', $senha)) {
        echo "Senha: 8+ caracteres, letra maiúscula, letra minúscula, número e caractere especial paizão!!!!!!";
        exit;
    }
 
    // Função para validar CPF (sua, não muda nada)
    function validaCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) return false;
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
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

    // CPF vai hasheado, email/telefone fica puro
    if ($is_cpf) {
        $emailnumero_final = password_hash($emailnumero, PASSWORD_DEFAULT);
    } else {
        $emailnumero_final = $emailnumero;
    }

    $senha_final = password_hash($senha, PASSWORD_DEFAULT);

    $sql = mysqli_query($conexao, 
        "INSERT INTO `usuarios`(`emailnumero`, `senha`) 
         VALUES ('$emailnumero_final','$senha_final')"
    );

    if ($sql) {
        echo "É cuiudo mesmo!!!!";
    } else {
        echo "Erro ao comprovar cuiosidade: " . mysqli_error($conexao);
    } 

endif;
?>