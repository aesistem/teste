<?php
require 'vendor/autoload.php';
require 'conexao.php'; // Inclua o arquivo de conexão com o banco de dados

use Mpdf\Mpdf;

// Verifique se o botão de download do PDF foi clicado
if (isset($_POST['download_pdf'])) {
    if (isset($_POST['vales_selecionados'])) {
        $valesSelecionados = $_POST['vales_selecionados'];
        $mpdf = new Mpdf();
        $output = '';

        foreach ($valesSelecionados as $valeId) {
            // Consulte o banco de dados para obter as informações do vale
            $stmt = $pdo->prepare("SELECT * FROM vales_combustivel WHERE id = :id");
            $stmt->execute(['id' => $valeId]);
            $vale = $stmt->fetch();

            if ($vale) {
                if ($vale['imprimiu'] === 'nao') {
                    // Atualize o status para "sim" no banco de dados
                    $stmtUpdate = $pdo->prepare("UPDATE vales_combustivel SET imprimiu = 'sim' WHERE id = :id");
                    $stmtUpdate->execute(['id' => $valeId]);
                } else {
                    // Pergunte ao usuário se deseja imprimir novamente
                    echo '<script>
                        if (confirm("O vale já foi impresso. Deseja imprimir novamente?")) {
                            // Se o usuário confirmar, atualize o status para "sim" no banco de dados
                            $.post("atualizar_impressao.php", { id: ' . $valeId . ' });
                        }
                    </script>';
                }

                // Gere o conteúdo do PDF
                $output .= '
                    <div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px;">
                        <img src="img/vale_combustivel.png" style="width: 100%; height: auto;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <img src="' . $vale['cod_qrcode'] . '" alt="QR Code">
                        </div>
                    </div>
                ';
            }
        }

        // Adicione o conteúdo ao PDF e gere o arquivo
        $mpdf->WriteHTML($output);
        $mpdf->Output('vales_combustivel.pdf', 'D');
    } else {
        echo "Nenhum vale selecionado.";
    }
}
?>
