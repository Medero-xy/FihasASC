<?php
session_start();
require 'config.php';

// Verifica se veio dados do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao']; // 'login' ou 'registrar'

    // --- REGISTRAR NOVO USUÁRIO ---
    if ($acao === 'registrar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // Verifica se usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "<script>alert('Email já cadastrado!'); window.location.href='../index.html';</script>";
            exit;
        }

        // Criptografa a senha (Segurança básica)
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

        // Insere no banco
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        if ($stmt->execute([$nome, $email, $hashSenha])) {
            echo "<script>alert('Conta criada! Faça login.'); window.location.href='../index.html';</script>";
        } else {
            echo "Erro ao registrar.";
        }
    }

    // --- FAZER LOGIN ---
    elseif ($acao === 'login') {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se achou usuário e se a senha bate com o hash
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // LOGIN SUCESSO!
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
             $_SESSION['is_mestre']   = isset($usuario['is_mestre']) ? (int)$usuario['is_mestre'] : 0;
            
            // Redireciona para o Dashboard (que criaremos depois)
            header("Location: ../dashboard.php");
            exit;
        } else {
            echo "<script>alert('Email ou senha incorretos!'); window.location.href='../index.html';</script>";
        }
    }
 
     // --- REDEFINIR SENHA ---
     elseif ($acao === 'reset_senha') {
         $email = $_POST['email'];
         $novaSenha = $_POST['nova_senha'];
         $confirmarSenha = $_POST['confirmar_senha'];
 
         if ($novaSenha !== $confirmarSenha) {
             echo "<script>alert('As senhas não conferem!'); window.location.href='../index.html';</script>";
             exit;
         }
 
         // Verifica se o usuário existe
         $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
         $stmt->execute([$email]);
         $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
 
         if (!$usuario) {
             echo "<script>alert('Email não encontrado!'); window.location.href='../index.html';</script>";             exit;
         }
 
         // Atualiza a senha com hash
        $hashSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
         $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
 
         if ($stmt->execute([$hashSenha, $email])) {
             echo "<script>alert('Senha redefinida com sucesso! Faça login.'); window.location.href='../index.html';</script>";
         } else {
             echo "<script>alert('Erro ao redefinir a senha. Tente novamente.'); window.location.href='../index.html';</script>";         }
     }

}
?>