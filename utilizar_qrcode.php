<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'frentista') {
    header('Location: processa_login.php');
    exit;
}

// Conexão com o banco de dados
require_once 'conexao.php';

if (isset($_POST['qrCodeText']) && isset($_POST['nomeAbastecido'])) {
    $codigo = $_POST['qrCodeText'];
    $nomeFrentista = $_POST['nomeAbastecido'];

    $sql = "SELECT * FROM vales WHERE codigo = ? AND status = 'ativo'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "UPDATE vales SET status = 'utilizado', data_utilizacao = NOW(), nome_abastecido = ? WHERE codigo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $nomeFrentista, $codigo);
        if ($stmt->execute()) {
            echo "Vale utilizado com sucesso!";
        } else {
            echo "Erro ao utilizar vale: " . $conn->error;
        }
    } else {
        echo "Vale inválido ou já utilizado.";
    }
} else {
    echo "Dados do formulário não fornecidos.";
}



$conn->close();
?>
