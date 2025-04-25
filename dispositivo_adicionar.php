<?php
require_once 'config.php';
verificarLogin();

$mensagem = '';
$erro = '';

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $modelo = filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING);
    $localizacao = filter_input(INPUT_POST, 'localizacao', FILTER_SANITIZE_STRING);
    $andar = filter_input(INPUT_POST, 'andar', FILTER_SANITIZE_STRING);
    $local_especifico = filter_input(INPUT_POST, 'local_especifico', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    
    // Validação básica
    if (empty($nome) || empty($modelo) || empty($localizacao) || empty($andar)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Inserir o dispositivo
        $dispositivo_data = [
            'name' => $nome,
            'model' => $modelo,
            'location' => $localizacao,
            'floor' => $andar,
            'specific_location' => $local_especifico,
            'status' => $status ?: 'offline',
            'token' => bin2hex(random_bytes(16)), // Gerar token aleatório
            'orientation' => 'landscape' // Orientação padrão
        ];
        
        $result = supabaseRequest('devices', 'POST', $dispositivo_data);
        
        if ($result['statusCode'] === 201 && !empty($result['body'])) {
            $mensagem = 'Dispositivo adicionado com sucesso!';
            
            // Redirecionar para a página de dispositivos após 2 segundos
            header('Refresh: 2; URL=dispositivos.php');
        } else {
            $erro = 'Erro ao adicionar dispositivo. Por favor, tente novamente.';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Adicionar Novo Dispositivo</h1>
        <a href="dispositivos.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    <p class="text-gray-600 mt-1">Adicione um novo dispositivo à sua rede de mídia indoor</p>
</div>

<?php if (!empty($mensagem)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p><?php echo $mensagem; ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p><?php echo $erro; ?></p>
    </div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <form action="dispositivo_adicionar.php" method="post" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-1">Nome do Dispositivo <span class="text-red-500">*</span></label>
                <input type="text" id="nome" name="nome" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Ex: TV Recepção, Monitor Sala de Reuniões</p>
            </div>
            
            <div>
                <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo <span class="text-red-500">*</span></label>
                <input type="text" id="modelo" name="modelo" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Ex: Samsung QLED 55", LG 65"</p>
            </div>
            
            <div>
                <label for="localizacao" class="block text-sm font-medium text-gray-700 mb-1">Localização <span class="text-red-500">*</span></label>
                <input type="text" id="localizacao" name="localizacao" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Ex: Matriz, Filial São Paulo</p>
            </div>
            
            <div>
                <label for="andar" class="block text-sm font-medium text-gray-700 mb-1">Andar <span class="text-red-500">*</span></label>
                <select id="andar" name="andar" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Selecione o andar</option>
                    <option value="Térreo">Térreo</option>
                    <option value="1">1º Andar</option>
                    <option value="2">2º Andar</option>
                    <option value="3">3º Andar</option>
                    <option value="4">4º Andar</option>
                    <option value="5">5º Andar</option>
                </select>
            </div>
            
            <div>
                <label for="local_especifico" class="block text-sm font-medium text-gray-700 mb-1">Local Específico</label>
                <input type="text" id="local_especifico" name="local_especifico" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Ex: Recepção Principal, Sala de Reuniões A</p>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Inicial</label>
                <select id="status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="offline" selected>Offline</option>
                    <option value="online">Online</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Status inicial do dispositivo</p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Configurações de Rede</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ip" class="block text-sm font-medium text-gray-700 mb-1">Endereço IP</label>
                    <input type="text" id="ip" name="ip" placeholder="192.168.1.100" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="mac" class="block text-sm font-medium text-gray-700 mb-1">Endereço MAC</label>
                    <input type="text" id="mac" name="mac" placeholder="00:1A:2B:3C:4D:5E" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <p class="text-xs text-gray-500 mt-4">Nota: As configurações de rede são opcionais nesta versão do sistema.</p>
        </div>
        
        <div class="flex justify-end space-x-3">
            <a href="dispositivos.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Adicionar Dispositivo</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 