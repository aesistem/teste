<?php
// Inclua o arquivo de conexão com o banco de dados
require_once 'conexao.php';

// Inicie a sessão
session_start();

// Verifique se os dados do formulário foram enviados
if (isset($_POST['clienteNome'])) {
    $clienteNome = mysqli_real_escape_string($conn, $_POST['clienteNome']);

    // Consulta SQL para inserir um novo cliente
    $query = "INSERT INTO clientes (nome) VALUES ('$clienteNome')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Cliente cadastrado com sucesso!";
    } else {
        $_SESSION['message'] = "Erro ao cadastrar o cliente: " . mysqli_error($conn);
    }

    // Redirecionar de volta para o painel do dono
    header("Location: painel_dono.php");
    exit();
} else {
    $_SESSION['message'] = "Dados do formulário não enviados.";
    header("Location: painel_dono.php");
    exit();
}

// Fechar a conexão com o banco de dados
mysqli_close($conn);
?>
