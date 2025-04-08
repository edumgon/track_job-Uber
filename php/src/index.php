<?php
require_once 'database.php'; // Inclui a classe de conexão

// Validação de sessão
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
// Fim da validação

// Função para exibir candidaturas em HTML
function renderApplications()
{
    $db = DatabaseConnection::getInstance()->getConnection();
    try {
        
        //cria tabela 100% responsiva
        echo "<div class='table-responsive'><table width='100%'><tr><td>";

        //Realiza contagem de candidaturas por status
        $query = "SELECT status, COUNT(*) as total FROM applications GROUP BY status";
        $stmt = $db->query($query);
        $statusCount = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='table-responsive'>";
        echo "<table class='table table-dark table-striped table-bordered rounded'>
                <thead class='thead-dark'>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($statusCount as $row) {
            echo "<tr>
                    <td>{$row['status']}</td>
                    <td>{$row['total']}</td>
                   </tr>";
        }
        echo "</tbody></table>";
        echo "</div>";

        echo "</td><td align='right'>";

        // Verifica se há um filtro de status
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        // Consulta para listar todas as candidaturas
        $query = "SELECT * FROM applications ";
        if (!empty($statusFilter)) {
            $query .= " WHERE status = \"$statusFilter\" ";
        } else {
            $query .= " WHERE status not in (\"negada\") ";
        }
        $query .= " ORDER BY application_date DESC";
        
        $stmt = $db->query($query);

        // Escreve as opções de filtro
        echo "<div class='table-responsive'>";
        echo "<form method='GET' class='mb-3'>";
        echo "<label for='status'>Filtrar por Status:</label> ";
        echo "<select name='status' id='status' onchange='this.form.submit()'>";
        echo "<option value=''>Todos (-negada)</option>";
        $statusOptions = ['inicial', 'entrevista', 'proposta', 'negada', 'aprovado'];
        foreach ($statusOptions as $option) {
            $selected = ($statusFilter == $option) ? "selected" : "";
            echo "<option value='$option' $selected>$option</option>";
        }      
        echo "</select>";
        echo "</form>";
        echo "</div>";

        echo "</td></tr></table></div>";

        // Verifica se há resultados
        if ($stmt->rowCount() > 0) {
            // Exibe os resultados em uma tabela responsiva
            echo "<div class='table-responsive'>";
            echo "<table class='table table-dark table-striped table-bordered rounded'>
                    <thead class='thead-dark'>
                        <tr>
                            <th>Link da Vaga</th>
                            <th>Empresa</th>
                            <th>Vaga</th>
                            <th>Data da Candidatura</th>
                            <th>Status</th>
                            <th>Data de Retorno</th>
                            <th>Data de Criação</th>
                            <th>Última Atualização</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $link = substr($row['job_link'],8,17);
                echo "<tr>
                        <td><a href='{$row['job_link']}' target='_blank'>{$link}</a></td>
                        <td>{$row['company_name']}</td>
                        <td>
                        <form method='POST' action='edit.php' style='display:inline;'>
                                <input type='hidden' name='job_link_hash' value='{$row['job_link_hash']}'>
                                <input type='hidden' name='edit' value='edit'>
                                <button type='submit' class='btn btn-link'>{$row['job_title']}</button>
                            </form>
                        </td>
                        <td>{$row['application_date']}</td>
                        <td>{$row['status']}</td>
                        <td>" . ($row['return_date'] ?? 'N/A') . "</td>
                        <td>{$row['created_at']}</td>
                        <td>{$row['updated_at']}</td>
                      </tr>";
            }
            echo "</tbody></table>";
            echo "</div>";


        } else {
            echo "<p class='alert alert-info text-center'>Nenhuma candidatura encontrada.</p>";
        }
    } catch (PDOException $e) {
        echo "<h1>Erro ao buscar dados:</h1><p>" . $e->getMessage() . "</p>";
    }
}

// HTML para exibição
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Candidaturas</title>
    <!-- Incluindo o Bootstrap -->
    <link href="bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212; /* Fundo escuro */
            color: #ffffff; /* Texto claro */
        }
        .container {
            max-width: 100%;
            padding: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .table thead th {
            border-bottom: 2px solid #444;
        }
        .table tbody tr:nth-child(even) {
            background-color: #1e1e1e;
        }
        .table tbody tr:hover {
            background-color: #373737;
        }
        .alert-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .text-center {
            text-align: center;
        }
        .table.rounded {
            border-radius: 10px;
            overflow: hidden; /* Para garantir que as bordas internas não ultrapassem a curvatura */
        }
            /* Style for links in the table */
        .table a {
            color: #17a2b8; /* Bright teal color for links */
            text-decoration: none; /* Remove underline */
        }
        .table a:hover {
            color: #ffffff; /* White color on hover */
            text-decoration: underline; /* Add underline on hover */
        }
        /* Style for buttons in the table */
        .table .btn-link {
            color: #17a2b8; /* Match the link color */
            text-decoration: none; /* Remove underline */
        }
        .table .btn-link:hover {
            color: #ffffff; /* White color on hover */
            text-decoration: underline; /* Add underline on hover */
        }
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
        <h1 class="text-center">Lista de Candidaturas</h1>
        <?php renderApplications(); ?>
    </div>
    <div class="container">
        <p class="text-center">
            <a href="cadastro.php" class="btn btn-primary">Cadastrar Nova</a>
            <a href="logout.php" class="btn btn-danger">Sair</a>
        </p>
    </div>

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
    
    <!-- Scripts do Bootstrap -->
    <script src="jquery-3.5.1.slim.min.js"></script>
    <script src="popper.min.js"></script>
    <script src="bootstrap.min.js"></script>

</body>
</html>