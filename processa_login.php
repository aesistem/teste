<?php
// Inclua o arquivo de conexão com o banco de dados
require_once 'conexao.php';

// Inicie a sessão
session_start();

// Verifique se os campos de usuário e senha foram enviados pelo formulário
if(isset($_POST['username']) && isset($_POST['password'])) {
    // Limpe os dados do formulário
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Consulta SQL para verificar as credenciais do usuário
    $query = "SELECT * FROM usuarios WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    // Verifique se a consulta retornou um resultado
    if(mysqli_num_rows($result) == 1) {
        // O usuário foi autenticado com sucesso, obtenha os detalhes do usuário
        $row = mysqli_fetch_assoc($result);

        // Defina as variáveis de sessão para o usuário autenticado
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_level'] = $row['level'];

        // Redirecione o usuário para o painel apropriado com base no nível de acesso
        switch($_SESSION['user_level']) {
            case 'dono':
                header("Location: painel_dono.php");
                break;
            case 'frentista':
                header("Location: painel_frentista.php");
                break;
            case 'cliente':
                header("Location: painel_cliente.php");
                break;
            default:
                // Caso o nível de usuário não seja reconhecido
                header("Location: index.php");
                break;
        }
        exit();
    } else {
        // As credenciais fornecidas são inválidas, redirecione de volta para o formulário de login
        header("Location: index.php");
        exit();
    }
} else {
    // Se os campos de usuário e senha não foram enviados, redirecione de volta para o formulário de login
    header("Location: index.php");
    exit();
}
?>
