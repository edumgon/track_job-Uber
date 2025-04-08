<?php
session_start();
require_once 'database.php';
require_once 'uber_func.php';

$session_id = false;
$time = 0;
cleanExpiredCodes();

// Controlador de autenticação
$auth_error = '';
$auth_success = '';
$eu = false;

if (isset($_POST['send_access'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Verifica se o e-mail passado contem os usuários permitidos
        if(!preg_match(M_to_filter, $email)) {
            $auth_error = "E-mail $email não permitido.";
        } else {
            $access_code = generateCode($email);
            if (sendAccessEmail($email, $access_code)) {
                $auth_success = "Link de acesso enviado para $email. <br>Válido por 10 minutos. <br>Essa janela já pode ser fechada.";
            } else {
            $auth_error = "Erro ao enviar e-mail. Tente novamente.";
            }
        }
    } else {
        $auth_error = "E-mail inválido. Tente novamente.";
    }
}

// Verificação de código de acesso via link
if (isset($_REQUEST['code'])) {
    $code = $_REQUEST['code'];
    $codeData = validateCode($code);
    if (!empty($codeData)) {
        $time = $codeData['expires_at'] - time();
        $session_id = true;
        if (isset($codeData['email']) && M_USER == $codeData['email'] ) {
            $eu = true;
            //echo "Olá, " . htmlspecialchars($codeData['email']) . "!<br>";
        } else {
            $auth_error = "Código de acesso inválido.";
        }
    } else {
        $auth_error = "Código de acesso inválido ou expirado.";
    }
}

// Função para obter dados do Uber
function getUberData() {
    $conn = DatabaseConnection::getInstance()->getConnection();
    $sql = "SELECT * FROM uber WHERE data_pgto is null ORDER BY data_hr DESC, hr_origem DESC";
    $result = $conn->query($sql);
    $data = [];
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
    } 
    return $data;
}
// Função para calcular o total
function calculateTotal() {
    $conn = DatabaseConnection::getInstance()->getConnection();
    $sql = "SELECT SUM(valor_total) as total FROM uber WHERE data_pgto is null";
    $result = $conn->query($sql);
    
    if ($result->rowCount() > 0) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }   
    return 0;
}
// Função para logout
if (isset($_GET['logout'])) {
    //session_destroy();
    removeCode($_REQUEST['code']);
    $session_id = false;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// Baixa de corrida
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $code = $_POST['code'];
    $codeData = validateCode($code);
    if ($codeData !== false) {
        $conn = DatabaseConnection::getInstance()->getConnection();
        $stmt = $conn->prepare("UPDATE uber SET data_pgto = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $id);
        if ($stmt->execute()) {
            echo "Baixa $id realizada com sucesso!";
        } else {
            echo "Erro ao realizar a baixa $id.";
        }
        //echo "Baixa $id realizada com sucesso!";
    } else {
        echo "Código de acesso inválido.";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Uber</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        :root {
            --bs-body-bg: #121212;
            --bs-body-color: #e0e0e0;
            --bs-primary: #1DB954;
            --bs-primary-rgb: 29, 185, 84;
            --bs-secondary: #535353;
            --bs-table-bg: #212121;
            --bs-table-color: #e0e0e0;
            --bs-table-striped-bg: #2d2d2d;
            --bs-table-hover-bg: #363636;
            --bs-card-bg: #212121;
            --bs-card-cap-bg: #303030;
        }
        
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }
        
        .card {
            background-color: var(--bs-card-bg);
            border: 1px solid #303030;
        }
        
        .card-header {
            background-color: var(--bs-card-cap-bg);
            border-bottom: 1px solid #404040;
        }
        
        .table {
            color: var(--bs-table-color);
            background-color: var(--bs-table-bg);
        }
        
        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: var(--bs-table-striped-bg);
        }
        
        .table-hover tbody tr:hover {
            background-color: var(--bs-table-hover-bg);
        }
        
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .btn-primary:hover {
            background-color: #19a347;
            border-color: #19a347;
        }
        
        .form-control, .form-select {
            background-color: #303030;
            border-color: #404040;
            color: #e0e0e0;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: #303030;
            border-color: var(--bs-primary);
            color: #e0e0e0;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--bs-primary);
        }
        
        .nav-link {
            color: #e0e0e0;
        }
        
        .nav-link:hover {
            color: var(--bs-primary);
        }
        
        .paid {
            color: var(--bs-primary);
        }
        
        .unpaid {
            color: #ff5252;
        }
        
        .session-timer {
            font-size: 0.8rem;
            color: #999;
        }
        
        #sessionTimer {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <?php if (!$session_id): ?>
            <!-- Tela de Login -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header">
                            <h3 class="mb-0 text-center">Acesso ao Dashboard Uber</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($auth_error): ?>
                                <div class="alert alert-danger"><?php echo $auth_error; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($auth_success): ?>
                                <div class="alert alert-success"><?php echo $auth_success; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">E-mail para acesso</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="send_access" class="btn btn-primary">Enviar Link de Acesso</button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center">
                            <small>Um link de acesso será enviado para seu e-mail</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($uber_data = getUberData()) : ?>
            <?php
                $uber_data = getUberData();
                $total_value = calculateTotal();
            ?>
            <!-- Dashboard -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h1>Dashboard Uber</h1>
                </div>
                <div class="col-md-6 text-end">
                    <div class="session-timer">
                        Sessão expira em: <span id="sessionTimer">10:00</span>
                        <a href="?logout=1&code=<? echo $code?>" class="btn btn-sm btn-outline-danger ms-2">Sair</a>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header">
                            <h5 class="mb-0">Total em aberto</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="mb-0">R$ <?php echo number_format($total_value, 2, ',', '.'); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header">
                            <h5 class="mb-0">Total de Corridas</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="mb-0"><?php echo count($uber_data); ?></h2>
                        </div>
                    </div>
                </div>
                <!--div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-header">
                            <h5 class="mb-0">Média por Corrida</h5>
                        </div>
                        <div class="card-body">
                            <h2 class="mb-0">R$ <?php echo count($uber_data) > 0 ? number_format($total_value / count($uber_data), 2, ',', '.') : '0,00'; ?></h2>
                        </div>
                    </div>
                </div-->
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Histórico de Corridas</h4>
                    <div class="d-flex">
                        <input type="text" id="searchInput" class="form-control form-control-sm me-2" placeholder="Pesquisar...">
                        <!--select id="filterStatus" class="form-select form-select-sm" style="width: 150px;">
                            <option value="all">Todos</option>
                            <option value="paid">Pagos</option>
                            <option value="unpaid">Não Pagos</option>
                        </select-->
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="uberTable">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Motorista</th>
                                    <th>Passageiro</th>
                                    <th>Origem</th>
                                    <th>Destino</th>
                                    <th>Distância</th>
                                    <th>Tempo</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <!--th>Status</th-->
                                    <?php if ($eu): ?><th>Baixa</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uber_data as $ride): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($ride['data_hr'])); ?></td>
                                    <td><?php echo substr($ride['hr_origem'], 0, 5) . ' - ' . substr($ride['hr_destino'], 0, 5); ?></td>
                                    <td><?php echo htmlspecialchars($ride['motorista']); ?></td>
                                    <td><?php echo htmlspecialchars($ride['passageiro']); ?></td>
                                    <td><?php echo htmlspecialchars($ride['origem']); ?></td>
                                    <td><?php echo htmlspecialchars($ride['destino']); ?></td>
                                    <td><?php echo $ride['distancia']; ?> km</td>
                                    <td><?php echo $ride['tempo']; ?></td>
                                    <td><?php echo htmlspecialchars($ride['tipo']); ?></td>
                                    <td>R$ <?php echo number_format($ride['valor_total'], 2, ',', '.'); ?></td>
                                    <?php if ($eu): ?>
                                        <td> 
                                            <form method="post" action="uber.php" onsubmit="confirmBaixa(event);">
                                                <input type="hidden" name="id" value="<?php echo $ride['id']; ?>">
                                                <input type="hidden" name="code" value="<?php echo $code ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Baixar</button>
                                            </form>
                                        </td>

                                    <?php endif; ?>
                                    <!--td class="<?php echo $ride['pgto'] ? 'paid' : 'unpaid'; ?>">
                                        <?php echo $ride['pgto'] ? '<i class="bi bi-check-circle"></i> Pago' : '<i class="bi bi-x-circle"></i> Pendente'; ?>
                                    </td-->
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($uber_data)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-3">Nenhum registro encontrado</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h4 class="mb-0">Resumo por Tipo</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Corridas</th>
                                        <th>Total</th>
                                        <th>Média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $tipo_summary = [];
                                    foreach ($uber_data as $ride) {
                                        $tipo = $ride['tipo'];
                                        if (!isset($tipo_summary[$tipo])) {
                                            $tipo_summary[$tipo] = [
                                                'count' => 0,
                                                'total' => 0
                                            ];
                                        }
                                        $tipo_summary[$tipo]['count']++;
                                        $tipo_summary[$tipo]['total'] += $ride['valor_total'];
                                    }
                                    
                                    foreach ($tipo_summary as $tipo => $data):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tipo); ?></td>
                                        <td><?php echo $data['count']; ?></td>
                                        <td>R$ <?php echo number_format($data['total'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($data['total'] / $data['count'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!--div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h4 class="mb-0">Status de Pagamento</h4>
                        </div>
                        <div class="card-body">
                            <?php
                            $paid_count = 0;
                            $paid_total = 0;
                            $unpaid_count = 0;
                            $unpaid_total = 0;
                            
                            foreach ($uber_data as $ride) {
                                if ($ride['pgto']) {
                                    $paid_count++;
                                    $paid_total += $ride['valor_total'];
                                } else {
                                    $unpaid_count++;
                                    $unpaid_total += $ride['valor_total'];
                                }
                            }
                            
                            $total_count = count($uber_data);
                            $paid_percent = $total_count > 0 ? ($paid_count / $total_count) * 100 : 0;
                            $unpaid_percent = $total_count > 0 ? ($unpaid_count / $total_count) * 100 : 0;
                            ?>
                            
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $paid_percent; ?>%;" 
                                     aria-valuenow="<?php echo $paid_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo number_format($paid_percent, 1); ?>%
                                </div>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $unpaid_percent; ?>%;" 
                                     aria-valuenow="<?php echo $unpaid_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo number_format($unpaid_percent, 1); ?>%
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-success bg-opacity-25 text-center mb-0">
                                        <div class="card-body">
                                            <h5 class="mb-0">Pagos (<?php echo $paid_count; ?>)</h5>
                                            <p class="mb-0">R$ <?php echo number_format($paid_total, 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-danger bg-opacity-25 text-center mb-0">
                                        <div class="card-body">
                                            <h5 class="mb-0">Pendentes (<?php echo $unpaid_count; ?>)</h5>
                                            <p class="mb-0">R$ <?php echo number_format($unpaid_total, 2, ',', '.'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div-->
            </div>
            
            <!-- JavaScript para cronômetro de sessão e filtros -->
            <script>
                function confirmBaixa(event) {
                    if (!confirm("Tem certeza que deseja dar baixa na corrida?")) {
                        event.preventDefault();
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    // Temporizador de sessão
                    let timeLeft = <? echo $time?>; // 5 minutos em segundos
                    const timerElement = document.getElementById('sessionTimer');
                    
                    function updateTimer() {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        
                        if (timeLeft <= 0) {
                            window.location.href = '?logout=1';
                        } else {
                            timeLeft--;
                            setTimeout(updateTimer, 1000);
                        }
                    }
                    
                    updateTimer();
                    
                    // Pesquisa e filtros
                    const searchInput = document.getElementById('searchInput');
                    const filterStatus = document.getElementById('filterStatus');
                    const table = document.getElementById('uberTable');
                    const rows = table.getElementsByTagName('tr');
                    
                    function filterTable() {
                        const searchText = searchInput.value.toLowerCase();
                        const statusFilter = filterStatus.value;
                        
                        for (let i = 1; i < rows.length; i++) {
                            const row = rows[i];
                            const showBySearch = searchText === '' || row.textContent.toLowerCase().includes(searchText);
                            
                            let showByStatus = true;
                            if (statusFilter === 'paid') {
                                showByStatus = row.classList.contains('paid-row');
                            } else if (statusFilter === 'unpaid') {
                                showByStatus = row.classList.contains('unpaid-row');
                            }
                            
                            row.style.display = (showBySearch && showByStatus) ? '' : 'none';
                        }
                    }
                    
                    searchInput.addEventListener('keyup', filterTable);
                    filterStatus.addEventListener('change', filterTable);
                });
            </script>
        <?php else: ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <h1>Dashboard Uber</h1>
                </div>
                <div class="col-md-6 text-end">
                        <div class="session-timer">
                            Sessão expira em: <span id="sessionTimer">?:00</span>
                            <a href="?logout=1&code=<? echo $code?>" class="btn btn-sm btn-outline-danger ms-2">Sair</a>
                        </div>
                </div>
                <div class="alert alert-info text-center">
                    <h4 class="alert-heading">Nenhum registro encontrado!</h4>
                    <p>Não há corridas registradas no sistema.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>