<?php
require_once 'database.php'; // Inclui a classe de conexão com o banco de dados
session_start();

// Função para gerar código de acesso (será usado no botão UBER)
function generateAccessCode() {
    return bin2hex(random_bytes(16));
}

// Verificação do formulário principal de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['email']) && isset($_POST['password'])) {
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
                $login_error = "Usuário ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $login_error = "Erro ao conectar ao banco: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Sistema</title>
    <!-- Inclui o Bootstrap -->
    <link href="bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: #1e1e1e;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            width: 100%;
        }
        @media (min-width: 768px) {
            .login-container {
                width: 450px;
            }
        }
        .form-control, .form-select {
            background-color: #2d2d2d;
            border-color: #444;
            color: #e0e0e0;
        }
        .form-control:focus, .form-select:focus {
            background-color: #333;
            border-color: #0d6efd;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .tab-content {
            padding-top: 20px;
        }
        .nav-tabs .nav-link {
            color: #b0b0b0;
            background-color: transparent;
            border: none;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-bottom: 2px solid #0d6efd;
        }
        .nav-tabs {
            border-bottom: 1px solid #444;
        }
        .form-label {
            color: #b0b0b0;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Acesso ao Sistema</h2>
        
        <!-- Mensagens de erro/sucesso -->
        <?php if (isset($login_error)): ?>
            <div class="alert alert-danger"><?php echo $login_error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($auth_error)): ?>
            <div class="alert alert-danger"><?php echo $auth_error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($auth_success)): ?>
            <div class="alert alert-success"><?php echo $auth_success; ?></div>
        <?php endif; ?>
        
        <!-- Abas de navegação -->
        <ul class="nav nav-tabs" id="loginTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">Login Padrão</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="uber-tab" data-bs-toggle="tab" data-bs-target="#uber" type="button" role="tab">Dashboard UBER</button>
            </li>
        </ul>
        
        <!-- Conteúdo das abas -->
        <div class="tab-content" id="loginTabsContent">
            <!-- Aba de login padrão -->
            <div class="tab-pane fade show active" id="login" role="tabpanel">
                <form method="POST" class="needs-validation" action="login.php">
                    <div class="mb-3">
                        <label class="form-label" for="email">E-mail:</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Senha:</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                </form>
            </div>
            
            <!-- Aba Dashboard UBER -->
            <div class="tab-pane fade" id="uber" role="tabpanel">
                <form method="POST" class="needs-validation" action="uber.php">
                    <div class="mb-3">
                        <label class="form-label" for="uber-email">E-mail:</label>
                        <input type="email" name="email" id="uber-email" class="form-control" required>
                    </div>
                    <input type="hidden" name="send_access" value="1">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Acessar Dashboard UBER</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Scripts JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>