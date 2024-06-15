<?php
// Inicie a sessão
session_start();

// Verifique se o usuário está logado como dono
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] != 'dono') {
    // Se não estiver logado como dono, redirecione para a página de login
    header("Location: login.php");
    exit();
}

// Inclua o arquivo de conexão com o banco de dados
require_once 'conexao.php';
require 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\ValidationException;

// Processar o formulário de cadastro de cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nomeCliente'])) {
    $nomeCliente = mysqli_real_escape_string($conn, $_POST['nomeCliente']);
    $query = "INSERT INTO clientes (nome) VALUES ('$nomeCliente')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['mensagem_sucesso'] = "Cliente cadastrado com sucesso!";
        // Redirecionar para evitar múltiplos cadastros ao atualizar a página
        header("Location: painel_dono.php");
        exit();
    } else {
        echo "Erro ao cadastrar cliente: " . mysqli_error($conn);
    }
} elseif (isset($_POST['nomeFrentista'])) {
    $nomeFrentista = mysqli_real_escape_string($conn, $_POST['nomeFrentista']);
    $query = "INSERT INTO usuarios (username) VALUES ('$nomeFrentista')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['mensagem_sucesso'] = "Frentista cadastrado com sucesso!";
        // Redirecionar para evitar múltiplos cadastros ao atualizar a página
        header("Location: painel_dono.php");
        exit();
    } else {
        echo "Erro ao cadastrar frentista: " . mysqli_error($conn);
    }
} elseif (isset($_POST['clienteId'])) {
    $clienteId = mysqli_real_escape_string($conn, $_POST['clienteId']);
    $codigo = bin2hex(random_bytes(16));

    $query = "INSERT INTO vales (cliente_id, cod_interno, cod_qrcode, data_geracao, status) VALUES ('$clienteId', '$codigo', '$codigo', NOW(), 'ativo')";

    if (mysqli_query($conn, $query)) {
        // Gerar QR Code
        try {
            $result = Builder::create()
                ->data($codigo)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->build();
            
            // Diretório onde o QR code será salvo
            $result->saveToFile(__DIR__ . '/qrcodes/' . $codigo . '.png');

            $_SESSION['mensagem_sucesso'] = "Vale gerado com sucesso! <br> <img src='qrcodes/$codigo.png'>";
            // Redirecionar para evitar múltiplos envios ao atualizar a página
            header("Location: painel_dono.php");
            exit();
        } catch (ValidationException $e) {
            echo 'Erro ao gerar QR code: ' . $e->getMessage();
        }
    } else {
        echo "Erro ao gerar vale: " . mysqli_error($conn);
    }
}

// Consulta SQL para obter os vales de combustível do banco de dados
$query = "SELECT v.*, c.nome AS nome_cliente FROM vales v INNER JOIN clientes c ON v.cliente_id = c.id;";
$result = mysqli_query($conn, $query);

// Consulta SQL para obter os clientes do banco de dados
$query2 = "SELECT * from clientes";
$result2 = mysqli_query($conn, $query2);

// Consulta SQL para obter os frentistas do banco de dados
$query3 = "SELECT * from usuarios";
$result3 = mysqli_query($conn, $query3);

// Contar quantidades para o dashboard
$queryClientes = "SELECT COUNT(*) as total FROM clientes";
$queryFrentistas = "SELECT COUNT(*) as total FROM usuarios where level= 'frentista'";
$queryVales = "SELECT COUNT(*) as total FROM vales";
$queryValesAtivos = "SELECT COUNT(*) as total FROM vales WHERE status = 'ativo'";
$queryValesUtilizados = "SELECT COUNT(*) as total FROM vales WHERE status = 'utilizado'";

$totalClientes = mysqli_fetch_assoc(mysqli_query($conn, $queryClientes))['total'];
$totalFrentistas = mysqli_fetch_assoc(mysqli_query($conn, $queryFrentistas))['total'];
$totalVales = mysqli_fetch_assoc(mysqli_query($conn, $queryVales))['total'];
$totalValesAtivos = mysqli_fetch_assoc(mysqli_query($conn, $queryValesAtivos))['total'];
$totalValesUtilizados = mysqli_fetch_assoc(mysqli_query($conn, $queryValesUtilizados))['total'];

// Iniciar as variáveis como arrays vazios
$vales_combustivel = $clientes = $frentista = array();

// Verificar se a consulta de vales retornou resultados
if (mysqli_num_rows($result) > 0) {
    // Loop através dos resultados da consulta e adicionar cada vale de combustível ao array
    while ($row = mysqli_fetch_assoc($result)) {
        $vales_combustivel[] = $row;
    }
} else {
    echo "Nenhum vale de combustível encontrado.";
}

// Verificar se a consulta de clientes retornou resultados
if (mysqli_num_rows($result2) > 0) {
    // Loop através dos resultados da consulta e adicionar cada cliente ao array
    while ($row = mysqli_fetch_assoc($result2)) {
        $clientes[] = $row;
    }
} else {
    echo "Nenhum cliente encontrado.";
}

// Verificar se a consulta de frentistas retornou resultados
if (mysqli_num_rows($result3) > 0) {
    // Loop através dos resultados da consulta e adicionar cada frentista ao array
    while ($row = mysqli_fetch_assoc($result3)) {
        $frentista[] = $row;
    }
} else {
    echo "Nenhum frentista encontrado.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Dono</title>
    <!-- Inclua o Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Adicione estilos personalizados, se necessário */
    </style>
</head>
<body>
    <!-- Barra de navegação -->
    <nav class="navbar navbar-dark bg-dark">
        <span class="navbar-brand mb-0 h1">Painel do Dono</span>
        <!-- Botão para logout -->
        <form class="form-inline">
            <button class="btn btn-outline-light my-2 my-sm-0 mr-2" type="button" onclick="window.location.href='logout.php'">Logout</button>
        </form>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral -->
            <div class="col-md-3 bg-light">
                <div class="list-group mt-4">
                    <a href="#" class="list-group-item list-group-item-action active">Dashboard</a>
                    <!-- Subcategoria para Vales -->
                    <a href="#subMenuVales" class="list-group-item list-group-item-action" data-toggle="collapse" aria-expanded="false">Vales</a>
                    <div id="subMenuVales" class="collapse">
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnGerarVales">Gerar Vales</a>
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnMostrarVales">Mostrar Vales</a>
                    </div>
                    <!-- Subcategoria para Clientes -->
                    <a href="#subMenuClientes" class="list-group-item list-group-item-action" data-toggle="collapse" aria-expanded="false">Clientes</a>
                    <div id="subMenuClientes" class="collapse">
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnCadastrarCliente">Cadastrar Cliente</a>
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnMostrarClientes">Mostrar Clientes</a>
                    </div>
                    <!-- Subcategoria para Frentistas -->
                    <a href="#subMenuFrentistas" class="list-group-item list-group-item-action" data-toggle="collapse" aria-expanded="false">Frentistas</a>
                    <div id="subMenuFrentistas" class="collapse">
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnCadastrarFrentista">Cadastrar Frentista</a>
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnMostrarFrentistas">Mostrar Frentistas</a>
                    </div>
                </div>
            </div>
            <!-- Conteúdo principal -->
            <div class="col-md-9">
                <div class="container mt-4">
                    <!-- Mensagem de sucesso -->
                    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                        <div class="alert alert-success">
                            <?php
                                echo $_SESSION['mensagem_sucesso'];
                                unset($_SESSION['mensagem_sucesso']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3">
                                <div class="card-header">Total de Clientes</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalClientes; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-secondary mb-3">
                                <div class="card-header">Total de Frentistas</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalFrentistas; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3">
                                <div class="card-header">Total de Vales</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalVales; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-danger mb-3">
                                <div class="card-header">Vales Ativos</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalValesAtivos; ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3">
                                <div class="card-header">Vales Utilizados</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $totalValesUtilizados; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de geração de vale -->
                <div id="formularioGerarVale" style="display: none;">
                    <h2>Gerar Vale</h2>
                    <form id="formGerarVale" method="post">
                        <div class="form-group">
                            <label for="clienteId">Cliente:</label>
                            <select class="form-control" id="clienteId" name="clienteId" required>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Gerar</button>
                    </form>
                </div>

                <!-- Tabela de vales -->
                <div id="tabelaVales" style="display: none;">
                    <h2>Vales</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Código Interno</th>
                                <th>Código QR</th>
                                <th>Data de Geração</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vales_combustivel as $vale): ?>
                                <tr>
                                    <td><?php echo $vale['nome_cliente']; ?></td>
                                    <td><?php echo $vale['cod_interno']; ?></td>
                                    <td><?php echo $vale['cod_qrcode']; ?></td>
                                    <td><?php echo $vale['data_geracao']; ?></td>
                                    <td><?php echo $vale['status']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulário de cadastro de cliente -->
                <div id="formularioCadastroCliente" style="display: none;">
                    <h2>Cadastrar Cliente</h2>
                    <form id="formCadastroCliente" method="post">
                        <div class="form-group">
                            <label for="nomeCliente">Nome do Cliente:</label>
                            <input type="text" class="form-control" id="nomeCliente" name="nomeCliente" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </form>
                </div>

                <!-- Tabela de clientes -->
                <div id="tabelaClientes" style="display: none;">
                    <h2>Clientes</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo $cliente['id']; ?></td>
                                    <td><?php echo $cliente['nome']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulário de cadastro de frentista -->
                <div id="formularioCadastroFrentista" style="display: none;">
                    <h2>Cadastrar Frentista</h2>
                    <form id="formCadastroFrentista" method="post">
                        <div class="form-group">
                            <label for="nomeFrentista">Nome do Frentista:</label>
                            <input type="text" class="form-control" id="nomeFrentista" name="nomeFrentista" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </form>
                </div>

                <!-- Tabela de frentistas -->
                <div id="tabelaFrentistas" style="display: none;">
                    <h2>Frentistas</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($frentista as $frentista): ?>
                                <tr>
                                    <td><?php echo $frentista['id']; ?></td>
                                    <td><?php echo $frentista['username']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Inclua os scripts do Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Script para alternar entre os formulários e tabelas -->
    <script>
        document.getElementById('btnGerarVales').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'block';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
        });

        document.getElementById('btnMostrarVales').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'block';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
        });

        document.getElementById('btnCadastrarCliente').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'block';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
        });

        document.getElementById('btnMostrarClientes').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'block';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
        });

        document.getElementById('btnCadastrarFrentista').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'block';
            document.getElementById('tabelaFrentistas').style.display = 'none';
        });

        document.getElementById('btnMostrarFrentistas').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'block';
        });
    </script>
</body>
</html>
