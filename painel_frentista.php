<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'frentista') {
    header('Location: processa_login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Frentista</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <style>
        body {
            padding-top: 56px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <a class="navbar-brand" href="#">Painel do Frentista</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item active">
                    <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <h1>Painel do Frentista</h1>
        <button onclick="startScanner()" class="btn btn-primary">Ler QR Code</button>
        <div id="reader" style="width: 100%; height: auto; max-width: 500px; margin-top: 20px;"></div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="qrCodeModal" tabindex="-1" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrCodeModalLabel">Confirmar QR Code</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="qrCodeForm" action="utilizar_qrcode.php" method="POST">
                        <div class="form-group">
                            <label for="qrCodeText">QR Code:</label>
                            <input type="text" class="form-control" id="qrCodeText" name="qrCodeText" readonly>
                        </div>
                        <div class="form-group">
                            <label for="nomeFrentista">Nome do Frentista:</label>
                            <input type="text" class="form-control" id="nomeFrentista" name="nomeFrentista" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function startScanner() {
            let html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start(
                { facingMode: "environment" }, // câmera voltada para o ambiente
                {
                    fps: 10,    // Quadros por segundo
                    qrbox: { width: 250, height: 250 }  // Caixa delimitadora do QR code
                },
                qrCodeMessage => {
                    html5QrCode.stop().then(ignore => {
                        document.getElementById('qrCodeText').value = qrCodeMessage;
                        $('#qrCodeModal').modal('show');
                    }).catch(err => {
                        console.error('Failed to stop scanning: ', err);
                    });
                },
                errorMessage => {
                    console.log(`QR Code no match: ${errorMessage}`);
                }
            ).catch(err => {
                console.error(`Unable to start scanning, error: ${err}`);
            });
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
