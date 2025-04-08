<?php
require_once 'database.php'; // Inclui a classe de conexão

//validação de sessao
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
        $stmt = $db->prepare("UPDATE users SET session_last_active = NOW() WHERE email = :email");
        $stmt->bindParam(':email', $user['email']);
        $stmt->execute();
    } else {
        // Token inválido, redireciona para o login
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco: " . $e->getMessage();
}
//fim da validação

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_link = $_POST['job_link'];
    $company_name = $_POST['company_name'];
    $job_title = $_POST['job_title'];
    $application_date = date('Y-m-d H:i:s'); // Data atual
    $status = $_POST['status'];
    $return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
    $created_at = date('Y-m-d H:i:s'); // Data atual
    $updated_at = $created_at;

    // Verifica se os campos obrigatórios estão preenchidos
    if (empty($job_link) || empty($company_name) || empty($job_title) || empty($status)) {
        $error = "Todos os campos obrigatórios devem ser preenchidos.";
    } else {
        try {
            // Conecta ao banco de dados
            $db = DatabaseConnection::getInstance()->getConnection();

            // Prepara a inserção
            $stmt = $db->prepare("INSERT INTO applications (job_link, company_name, job_title, application_date, status, return_date, created_at, updated_at) 
                                  VALUES (:job_link, :company_name, :job_title, :application_date, :status, :return_date, :created_at, :updated_at)");
            
            // Executa a consulta
            $stmt->execute([
                ':job_link' => $job_link,
                ':company_name' => $company_name,
                ':job_title' => $job_title,
                ':application_date' => $application_date,
                ':status' => $status,
                ':return_date' => $return_date,
                ':created_at' => $created_at,
                ':updated_at' => $updated_at,
            ]);

            $success = "Candidatura cadastrada com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao inserir registro: " . $e->getMessage();
        }
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
    <style>
        /* Hamburger menu */
        .hamburger-menu {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .menu-icon {
            cursor: pointer;
            padding: 10px;
            background: transparent;
            border: none;
        }

        .menu-icon span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: #17a2b8;
            margin: 5px 0;
            transition: 0.4s;
        }

        .menu-links {
            position: fixed;
            top: 60px;
            right: 20px;
            background-color: #1e1e1e;
            border-radius: 5px;
            padding: 10px;
            display: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .menu-links.show {
            display: block;
        }

        .menu-links a {
            display: block;
            color: #17a2b8;
            text-decoration: none;
            padding: 10px 20px;
            white-space: nowrap;
        }

        .menu-links a:hover {
            background-color: #373737;
            color: #ffffff;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <h1 class="text-center">Cadastrar Nova Candidatura</h1>

        <!-- Mensagem de sucesso ou erro -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="job_link" class="form-label">Link da Vaga <span class="text-danger">*</span></label>
                <input type="url" name="job_link" id="job_link" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="company_name" class="form-label">Nome da Empresa <span class="text-danger">*</span></label>
                <input type="text" name="company_name" id="company_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="job_title" class="form-label">Nome da Vaga <span class="text-danger">*</span></label>
                <input type="text" name="job_title" id="job_title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select" required>
                    <option value="inicial">Inicial</option>
                    <option value="entrevista">Entrevista</option>
                    <option value="proposta">Proposta</option>
                    <option value="negada">Negada</option>
                    <option value="aprovado">Aprovado</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="return_date" class="form-label">Data de Retorno</label>
                <input type="date" name="return_date" id="return_date" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
    </div>
    <!-- Scripts do Bootstrap -->
    <script src="bootstrap.bundle.min.js"></script>
    <!-- Navbar -->
    <div class="hamburger-menu">
        <button class="menu-icon" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="menu-links" id="menuLinks">
            <a href="index.php">Home</a>
            <a href="cadastro.php">Cadastro</a>
            <a href="edit.php">Editar</a>
            <a href="logout.php">Sair</a>
        </div>
    </div>
    <!-- Script para o menu responsivo -->
    <script>
    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('menuLinks').classList.toggle('show');
    });

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('menuLinks');
        const menuButton = document.getElementById('menuToggle');
        
        if (!menuButton.contains(event.target) && !menu.contains(event.target)) {
            menu.classList.remove('show');
        }
    });
    </script>
</body>
</html>
