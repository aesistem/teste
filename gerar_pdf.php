<?php
require_once('tcpdf/tcpdf.php');

// Verifique se os vales selecionados foram definidos
if (isset($_POST['vales_selecionados'])) {
    $vales_selecionados = $_POST['vales_selecionados'];
} else {
    // Se nenhum vale foi selecionado, encerre o script
    exit('Nenhum vale de combustível selecionado.');
}

// Crie um novo documento PDF
$pdf = new TCPDF('P', 'mm', 'A4');

// Defina informações básicas do documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Seu Nome');
$pdf->SetTitle('Vales Combustível');
$pdf->SetSubject('PDF de Vales Combustível');
$pdf->SetKeywords('TCPDF, PDF, vale, combustível');

// Defina o caminho da imagem do vale
$vale_image = 'img/vale_combustivel.png';

// Defina dimensões e margens
$vale_width = 75; // Largura do vale
$vale_height = 33; // Altura do vale
$margin_left = 1; // Margem esquerda
$margin_top = 1; // Margem superior
$space_h = 1; // Espaço horizontal entre os vales
$space_v = 1; // Espaço vertical entre os vales

// Contadores de posição
$col = 0;
$row = 0;

// Loop através dos vales selecionados
foreach ($vales_selecionados as $vale) {
    // Adicionar uma nova página se necessário
    if (($col + $row * 4) % 21 == 0) {
        if ($col > 0 || $row > 0) {
            $pdf->AddPage();
        } else {
            $pdf->AddPage();
        }
    }

    // Calcular posição do vale
    $x = $margin_left + ($col * ($vale_width + $space_h));
    $y = $margin_top + ($row * ($vale_height + $space_v));

    // Adicione a imagem do vale
    $pdf->Image($vale_image, $x, $y, $vale_width, $vale_height, 'PNG');

    // Defina o estilo do QR code
    $style = array(
        'border' => 2,
        'padding' => 'auto',
        'fgcolor' => array(252, 252, 252),
        'bgcolor' => false
    );

    // Adicione o QR code sobre a imagem
    $pdf->write2DBarcode('' . $vale, 'QRCODE,H', $x + 42, $y + 1, 30, 30, $style, 'N');

    // Atualizar posição
    $col++;
    if ($col == 4) {
        $col = 0;
        $row++;
        if ($row == 7) {
            $row = 0;
        }
    }
}

// Saída do PDF
$pdf->Output('vales_combustivel.pdf', 'D');
?>
