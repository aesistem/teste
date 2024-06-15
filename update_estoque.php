<?php
// update_estoque.php

// Conexão com o banco de dados
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente = $_POST['cliente'];
    $quantidade = intval($_POST['quantidade']);

    // Atualizar a tabela de estoque
    $sql = "UPDATE estoque SET est_contratado = est_contratado + ? WHERE cliente_id = ?";
    
    // Preparar a consulta
    $stmt = $conn->prepare($sql);
    
    // Verificar se a preparação da consulta foi bem-sucedida
    if ($stmt) {
        // Vincular parâmetros e executar a consulta
        $stmt->bind_param("is", $quantidade,  $cliente);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        
        // Fechar a instrução preparada
        $stmt->close();
    } else {
        // Se a preparação da consulta falhar
        echo json_encode(["status" => "error", "message" => "Falha ao preparar a consulta"]);
    }

    // Fechar a conexão com o banco de dados
    $conn->close();
}
?>
