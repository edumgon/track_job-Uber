<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['session_token'])) {
    // Redireciona para a página de login se não houver sessão
    header('Location: login.php');
    exit;
}

try {
    $db = DatabaseConnection::getInstance()->getConnection();

    // Valida o token no banco de dados
    $stmt = $db->prepare("SELECT email FROM users WHERE session_token = :session_token");
    $stmt->bindParam(':session_token', $_SESSION['session_token']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Atualiza o último acesso
        $stmt = $db->prepare("UPDATE users SET session_token = null WHERE email = :email");
        $stmt->bindParam(':email', $user['email']);
        $stmt->execute();
        //echo "Sessão encerrada com sucesso! <br> Acessar novamente <a href='login.php'>Login</a>";
        header('Location: login.php');
        exit;
    } else {
        // Token inválido, redireciona para o login
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco: " . $e->getMessage();
}
?>
