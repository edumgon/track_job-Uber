<?php
require_once 'database.php'; // Inclui a classe de conexão com o banco de dados
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    //echo "E-mail: $email <br> Senha: $password <br>";
    $pwd = password_hash("123456", PASSWORD_DEFAULT);
    //$pwd = password_hash($password, PASSWORD_DEFAULT);

    try {
        $db = DatabaseConnection::getInstance()->getConnection();

        // Consulta para validar usuário e senha
        $stmt = $db->prepare("SELECT email FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtém o número de linhas
        $rowCount = $stmt->rowCount();
        //echo "Número de usuários encontrados: $rowCount <br> Senha: $pwd <br>";

        if ($user && password_verify($password, $pwd)) {
            // Gera um token de sessão único
            $session_token = bin2hex(random_bytes(32));

            // Atualiza o token e o último acesso no banco de dados
            $stmt = $db->prepare("UPDATE users SET session_token = :session_token, session_last_active = NOW() WHERE email = :email");
            $stmt->bindParam(':session_token', $session_token);
            $stmt->bindParam(':email', $user['email']);
            $stmt->execute();

            // Salva o token na sessão
            $_SESSION['session_token'] = $session_token;

            // Redireciona para a página inicial
            header('Location: index.php');
            exit;
        } else {
            echo "Usuário ou senha inválidos.";
        }
    } catch (PDOException $e) {
        echo "Erro ao conectar ao banco: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Candidatura</title>
    <!-- Inclui o Bootstrap -->
    <link href="bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Formulário de Login -->
<form method="POST"  class="needs-validation" action="login.php">
    <label class="form-label" for="email">E-mail:</label>
    <input type="text" name="email" id="email"class="form-control" required>
    <br>
    <label class="form-label" for="password">Senha:</label>
    <input type="password" name="password" id="password"  class="form-control" required>
    <br>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
</body>
</html>
