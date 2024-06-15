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
    $cpf = mysqli_real_escape_string($conn, $_POST['cpf']);
    $telefone = mysqli_real_escape_string($conn, $_POST['telefone']);
    $endereco = mysqli_real_escape_string($conn, $_POST['endereco']);
    $cidade = mysqli_real_escape_string($conn, $_POST['cidade']);
    $rg = mysqli_real_escape_string($conn, $_POST['rg']);
    $estado = mysqli_real_escape_string($conn, $_POST['estado']);

    $query = "INSERT INTO clientes (nome, cpf, telefone, endereco, cidade, rg, estado) 
              VALUES ('$nomeCliente', '$cpf', '$telefone', '$endereco', '$cidade', '$rg', '$estado')";

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
$query2 = "SELECT id, nome, cpf, telefone, endereco, cidade, rg, estado FROM clientes";
$result2 = mysqli_query($conn, $query2);


// Consulta SQL para obter os frentistas do banco de dados
$query3 = "SELECT * from usuarios";
$result3 = mysqli_query($conn, $query3);



// Consulta SQL para obter os estoques do banco de dados
$query4 = "select v.*, c.nome AS nome_cliente FROM estoque v INNER JOIN clientes c ON v.cliente_id = c.id";
$result4 = mysqli_query($conn, $query4);

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


// Verificar se a consulta de estoque retornou resultados
if (mysqli_num_rows($result4) > 0) {
    // Loop através dos resultados da consulta e adicionar cada estoque ao array
    while ($row = mysqli_fetch_assoc($result4)) {
        $estoque[] = $row;
    }
} else {
    echo "Nenhum frentista encontrado.";
}

// Função para formatar CPF
function formatarCPF($cpf) {
    if (isset($cpf) && is_string($cpf)) {
        $cpf_formatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        return $cpf_formatado;
    } else {
        return ''; // Retornar uma string vazia ou outra indicação de erro, conforme apropriado
    }
}

// Função para formatar Telefone
function formatarTelefone($telefone) {
    if (isset($telefone) && is_string($telefone)) {
        $telefone_formatado = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        return $telefone_formatado;
    } else {
        return ''; // Retornar uma string vazia ou outra indicação de erro, conforme apropriado
    }
}
?>
<?php
// Verifique se o botão de download do PDF foi clicado
if (isset($_POST['download_pdf'])) {
    // Inclua o arquivo de geração de PDF
    include 'gerar_pdf.php';
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
        .custom-input {
        width: 300px; /* Defina o tamanho desejado para o campo de entrada */
    }

    .custom-button {
        min-width: 80px; /* Defina o tamanho mínimo desejado para o botão */
    }
    
    .linha-branca {
        background-color: #d3d3d3; /* branco */
    }
    
    .linha-cinza {
        background-color: #ffffff; /* cinza */
    }
    

    
</style>
    </style>
</head>
<body>
    <!-- Barra de navegação -->
    <nav class="navbar navbar-dark bg-dark">
        <span class="navbar-brand mb-0 h1">Painel do Rabelo</span>
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
                    <a href="#" class="list-group-item list-group-item-action active"id="btnDashboard">Dashboard</a>
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

                    <!-- Subcategoria para Estoque -->
                    <a href="#subMenuEstoque" class="list-group-item list-group-item-action" data-toggle="collapse" aria-expanded="false">Estoque</a>
                    <div id="subMenuEstoque" class="collapse">
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnCadastrarEstoque">Lançar Estoque</a>
                        <a href="#" class="list-group-item list-group-item-action ml-3" id="btnMostrarEstoque">Mostrar Estoque</a>
                    </div>
                </div>
            </div>
           <!-- Conteúdo principal -->
<div class="col-md-9" id="contentArea">
    <div class="container mt-4">
        <div class="row justify-content-center">
            
            <div id="dashboard" style="display: none;">
                
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
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-header">Total de Vales</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $totalVales; ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white  bg-success mb-3">
                            <div class="card-header">Vales Ativos</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $totalValesAtivos; ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white  bg-danger mb-3">
                            <div class="card-header">Vales Utilizados</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $totalValesUtilizados; ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        
                            <!-- Mensagem de sucesso em um modal -->
                                    <?php if (isset($_SESSION['mensagem_sucesso'])): ?>
                                        <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="successModalLabel">Sucesso!</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php echo $_SESSION['mensagem_sucesso']; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-primary" data-dismiss="modal">Fechar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>                 
                                    <?php
                                // Limpe a variável de sessão após exibir o modal
                        unset($_SESSION['mensagem_sucesso']);
                        endif; ?>
                    
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
                        <form method="post" action="gerar_pdf.php">
                            <div class="input-group mb-3 custom-input">
                                <input type="text" class="form-control" id="campo_busca" placeholder="Buscar por cliente">
                                <div class="input-group-append">
                                    <button class="btn btn-primary custom-button" type="button" id="botao_buscar">Buscar</button>
                                </div>
                            </div>

                            <label><input type="checkbox" id="selecionar_todos"> Selecionar Todos</label>
                            <button type="submit" name="download_pdf" class="btn btn-primary">
                                <i class="fas fa-file-pdf mr-2"></i>Download PDF
                            </button>

                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Selecionar</th>
                                        <th>Codigo</th>
                                        <th>Cliente</th>
                                        <th>Código Interno</th>
                                        <th>Código QR</th>
                                        <th>Data de Geração</th>
                                        <th>Data de Utilização</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_corpo" class="lista-alternada">
                        <?php foreach ($vales_combustivel as $index => $vale): ?>
                            <?php $linha_classe = $index % 2 == 0 ? 'linha-branca' : 'linha-cinza'; ?>
                            <tr class="<?php echo $linha_classe; ?>">
                                <td>
                                    <input class="form-check-input checkbox_vale" type="checkbox" value="<?php echo $vale['id']; ?>" id="flexCheckDefault<?php echo $vale['id']; ?>" name="vales_selecionados[]">
                                    <label class="form-check-label" for="flexCheckDefault<?php echo $vale['id']; ?>"></label>
                                </td>
                                <td><?php echo $vale['id']; ?></td>
                                <td><?php echo $vale['nome_cliente']; ?></td>
                                <td><?php echo $vale['cod_interno']; ?></td>
                                <td><?php echo $vale['cod_qrcode']; ?></td>
                                <td><?php echo $vale['data_geracao']; ?></td>
                                <td><?php echo $vale['data_utilizacao']; ?></td>
                                <td>
                                    <?php 
                                        $status_class = '';
                                        switch ($vale['status']) {
                                            case 'ativo':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'utilizado':
                                                $status_class = 'badge-danger';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                                break;
                                        }
                                    ?>
                                    <span class="badge rounded-pill text-white <?php echo $status_class; ?>"><?php echo $vale['status']; ?></span>
                                </td>
                            
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                            </table>
                        </form>
                    </div>


                
                <!-- Formulário de cadastro de cliente -->
                <div id="formularioCadastroCliente" style="display: none;">
                    <h2>Cadastrar Cliente</h2>
                    <form id="formCadastroCliente" method="post">
                        <div class="form-group">
                            <label for="nomeCliente">Nome do Cliente:</label>
                            <input type="text" class="form-control" id="nomeCliente" name="nomeCliente" required>
                        </div>
                        <div class="form-group">
                            <label for="cpf">CPF:</label>
                            <input type="text" class="form-control" id="cpf" name="cpf" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone">Telefone:</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" required>
                        </div>
                        <div class="form-group">
                            <label for="endereco">Endereço:</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" required>
                        </div>
                        <div class="form-group">
                            <label for="cidade">Cidade:</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" required>
                        </div>
                        <div class="form-group">
                            <label for="rg">RG:</label>
                            <input type="text" class="form-control" id="rg" name="rg" required>
                        </div>
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <input type="text" class="form-control" id="estado" name="estado" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cadastrar</button>
                    </form>
                </div>

               <!-- Tabela de clientes -->
                <div id="tabelaClientes" style="display: none;">
                    <h2>Clientes</h2>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Cidade</th>
                                <th>RG</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_corpo" class="lista-alternada">
                            <?php foreach ($clientes as $cliente): ?>
                                <?php $linha_classe = $index % 2 == 0 ? 'linha-branca' : 'linha-cinza'; ?>
                                <tr class="<?php echo $linha_classe; ?>">
                                    <td><?php echo $cliente['id']; ?></td>
                                    <td><?php echo $cliente['nome']; ?></td>
                                    <td><?php echo formatarCPF($cliente['cpf']); ?></td>
                                    <td><?php echo formatarTelefone($cliente['telefone']); ?></td>
                                    <td><?php echo $cliente['endereco']; ?></td>
                                    <td><?php echo $cliente['cidade']; ?></td>
                                    <td><?php echo $cliente['rg']; ?></td>
                                    <td><?php echo $cliente['estado']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>


                <!-- Tabela de mostrar Estoque -->
                <div id="tabelaEstoque" style="display: none;">
                    <h2>Estoque</h2>
                    <div class="form-group">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addStockModal">
                             Atualizar Estoque
                        </button>
                        
                        </div>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nome</th>
                                <th>Contratado</th>
                                <th>Utilizado</th>
                                <th>Restante</th>
                                <th>Status</th>
                                 
                            </tr>
                        </thead>
                        <tbody id="tabela_corpo" class="lista-alternada">
                            <?php foreach ($estoque as $index => $item): ?>
                                <?php
                    // Calcular estoque utilizado
                    $estoqueUtilizado = $item['est_contratado'] - $item['est_utilizado'];

                     // Formatar valores com três casas decimais
                     $contratadoFormatado = number_format($item['est_contratado'], 3, ',', '.');
                     $utilizadoFormatado = number_format($estoqueUtilizado, 3, ',', '.');
                     $restanteFormatado = number_format($item['est_restante'], 3, ',', '.');

                    // Definir o status com base no estoque restante
                    $status = ($item['est_restante'] > 0) ? 'aberto' : 'fechado';
                    $statusClass = ($status == 'aberto') ? 'badge-success' : 'badge-danger';
                ?>
                                <tr class="<?php echo $linha_classe; ?>" data-cliente="<?php echo $item['nome_cliente']; ?>">
                                <tr class="<?php echo $linha_classe; ?>">
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo $item['nome_cliente']; ?></td>
                                    <td class="est-contratado"><?php echo $contratadoFormatado; ?> LT</td>
                                    <td class="est-utilizado"><?php echo $utilizadoFormatado; ?> LT</td>
                                    <td class="est-restante"><?php echo $restanteFormatado; ?> LT</td>
                                    <td>
                                    <?php 
                                        $status_class = '';
                                        switch ($vale['status']) {
                                            case 'aberto':
                                                $status_class = 'badge-success';
                                                break;
                                            case 'fechado':
                                                $status_class = 'badge-danger';
                                                break;
                                            default:
                                                $status_class = 'badge-secondary';
                                                break;
                                        }
                                    ?>
                                    <span class="badge rounded-pill text-white <?php echo $status_class; ?>"><?php echo $item['status']; ?></span>
                                </td> 
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal adicioonar estoque-->
                <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addStockModalLabel">Adicionar Estoque</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="clienteSelect">Selecionar Cliente</label>
                                    <select class="form-control" id="clienteSelect">
                                    <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                                <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="quantidadeInput">Quantidade</label>
                                    <input type="number" class="form-control" id="quantidadeInput">
                                </div>
                                
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" id="adicionarEstoqueBtn">Adicionar</button>
                            </div>
                        </div>
                    </div>
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
          document.getElementById('btnDashboard').addEventListener('click', function() {
            document.getElementById('dashboard').style.display = 'block';
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        // Ocultar o dashboard ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('dashboard').style.display = 'block';
        });


        document.getElementById('btnGerarVales').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'block';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        document.getElementById('btnMostrarVales').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'block';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        document.getElementById('btnCadastrarCliente').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'block';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        document.getElementById('btnMostrarClientes').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'block';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        document.getElementById('btnMostrarEstoque').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'block';
        });

        document.getElementById('btnCadastrarFrentista').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'block';
            document.getElementById('tabelaFrentistas').style.display = 'none';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });

        document.getElementById('btnMostrarFrentistas').addEventListener('click', function() {
            document.getElementById('formularioGerarVale').style.display = 'none';
            document.getElementById('tabelaVales').style.display = 'none';
            document.getElementById('formularioCadastroCliente').style.display = 'none';
            document.getElementById('tabelaClientes').style.display = 'none';
            document.getElementById('formularioCadastroFrentista').style.display = 'none';
            document.getElementById('tabelaFrentistas').style.display = 'block';
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('tabelaEstoque').style.display = 'none';
        });


        document.getElementById('btnDashboard').addEventListener('click', function() {
    console.log("Botão de Dashboard clicado!");
    // Recolher todos os submenus
    document.getElementById('subMenuVales').classList.remove('show');
    document.getElementById('subMenuClientes').classList.remove('show');
    document.getElementById('subMenuFrentistas').classList.remove('show');
    document.getElementById('subMenuEstoque').classList.remove('show');
    // Exibir o dashboard
    document.getElementById('dashboard').style.display = 'block';
});
        
    </script>
    

    

<!-- Ativar o modal automaticamente -->
<script>
                        $(document).ready(function(){
                            $('#successModal').modal('show');
                        });
 </script>
 <script>
   // Função para selecionar todos os vales visíveis quando a caixa "Selecionar Todos" é marcada/desmarcada
   document.getElementById("selecionar_todos").addEventListener("change", function() {
        var checkboxes = document.querySelectorAll("#tabela_corpo .checkbox_vale");
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = document.getElementById("selecionar_todos").checked;
        });
    });

    // Função para filtrar a tabela de vales com base no nome do cliente
    function filtrarVales() {
        var input = document.getElementById("campo_busca").value.toLowerCase();
        var tableRows = document.querySelectorAll("#tabela_corpo tr");
        tableRows.forEach(function(row) {
            var cliente = row.cells[2].textContent.toLowerCase(); // Corrigido o índice da célula para corresponder à coluna do cliente
            if (cliente.includes(input)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        // Atualizar a seleção de "Marcar Todos" para refletir apenas os vales visíveis
        document.getElementById("selecionar_todos").checked = false;
    }

    // Adicione um evento de clique ao botão de busca
    document.getElementById("botao_buscar").addEventListener("click", filtrarVales);

    // Adicione um evento de tecla pressionada ao campo de busca para ativar a filtragem enquanto o usuário digita
    document.getElementById("campo_busca").addEventListener("keypress", function(event) {
        if (event.key === 'Enter') {
            filtrarVales();
        }
    });
</script>

<script>
  

    document.getElementById('adicionarEstoqueBtn').addEventListener('click', function() {
       

        var clienteSelect = document.getElementById('clienteSelect');
        var quantidadeInput = document.getElementById('quantidadeInput');

        var cliente = clienteSelect.value;
        var quantidade = parseInt(quantidadeInput.value);

     

        if (isNaN(quantidade) || quantidade <= 0) {
            alert('Por favor, insira uma quantidade válida.');
            return;
        }

        var formData = new FormData();
        formData.append('cliente', cliente);
        formData.append('quantidade', quantidade);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_estoque.php', true);
        xhr.onload = function () {
            
            
            if (xhr.status === 200) {
                
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === "success") {
                        $('#addStockModal').modal('hide');
                        
                        alert('Estoque atualizado com sucesso!');

                        location.reload();
                        
                        // Código para atualizar a tabela de estoque...
                    } else {
                        alert('Erro ao atualizar o estoque: ' + response.message);
                    }
                } catch (e) {
                   
                    alert('Erro ao processar a resposta do servidor.');
                }
            } else {
          
                alert('Erro na requisição AJAX: ' + xhr.status);
            }
        };
        xhr.onerror = function () {
            
            alert('Erro na requisição AJAX.');
        };
        xhr.send(formData);
    });
</script>


</body>
</html>
