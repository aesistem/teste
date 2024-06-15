document.getElementById('btnDashboard').addEventListener('click', function() {
    document.getElementById('dashboard').style.display = 'block';
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'none';
    document.getElementById('formularioCadastroCliente').style.display = 'none';
    document.getElementById('tabelaClientes').style.display = 'none';
    document.getElementById('formularioCadastroFrentista').style.display = 'none';
    document.getElementById('tabelaFrentistas').style.display = 'none';
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
});

document.getElementById('btnMostrarVales').addEventListener('click', function() {
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'block';
    document.getElementById('formularioCadastroCliente').style.display = 'none';
    document.getElementById('tabelaClientes').style.display = 'none';
    document.getElementById('formularioCadastroFrentista').style.display = 'none';
    document.getElementById('tabelaFrentistas').style.display = 'none';
    document.getElementById('dashboard').style.display = 'none';
});

document.getElementById('btnCadastrarCliente').addEventListener('click', function() {
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'none';
    document.getElementById('formularioCadastroCliente').style.display = 'block';
    document.getElementById('tabelaClientes').style.display = 'none';
    document.getElementById('formularioCadastroFrentista').style.display = 'none';
    document.getElementById('tabelaFrentistas').style.display = 'none';
    document.getElementById('dashboard').style.display = 'none';
});

document.getElementById('btnMostrarClientes').addEventListener('click', function() {
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'none';
    document.getElementById('formularioCadastroCliente').style.display = 'none';
    document.getElementById('tabelaClientes').style.display = 'block';
    document.getElementById('formularioCadastroFrentista').style.display = 'none';
    document.getElementById('tabelaFrentistas').style.display = 'none';
    document.getElementById('dashboard').style.display = 'none';
});

document.getElementById('btnCadastrarFrentista').addEventListener('click', function() {
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'none';
    document.getElementById('formularioCadastroCliente').style.display = 'none';
    document.getElementById('tabelaClientes').style.display = 'none';
    document.getElementById('formularioCadastroFrentista').style.display = 'block';
    document.getElementById('tabelaFrentistas').style.display = 'none';
    document.getElementById('dashboard').style.display = 'none';
});

document.getElementById('btnMostrarFrentistas').addEventListener('click', function() {
    document.getElementById('formularioGerarVale').style.display = 'none';
    document.getElementById('tabelaVales').style.display = 'none';
    document.getElementById('formularioCadastroCliente').style.display = 'none';
    document.getElementById('tabelaClientes').style.display = 'none';
    document.getElementById('formularioCadastroFrentista').style.display = 'none';
    document.getElementById('tabelaFrentistas').style.display = 'block';
    document.getElementById('dashboard').style.display = 'none';
});


document.getElementById('btnDashboard').addEventListener('click', function() {
console.log("Botão de Dashboard clicado!");
// Recolher todos os submenus
document.getElementById('subMenuVales').classList.remove('show');
document.getElementById('subMenuClientes').classList.remove('show');
document.getElementById('subMenuFrentistas').classList.remove('show');
// Exibir o dashboard
document.getElementById('dashboard').style.display = 'block';
});


$(document).ready(function(){
    $('#successModal').modal('show');
});

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
