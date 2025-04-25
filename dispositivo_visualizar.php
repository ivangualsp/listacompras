<?php
require_once 'config.php';
verificarLogin();

// Evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$mensagem = '';
$tipo_mensagem = '';
$dispositivo = null;

// Obter ID do dispositivo da URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: dispositivos.php');
    exit;
}

// Parâmetro para forçar atualização (evitar cache)
$update_param = isset($_GET['update']) ? '&force_refresh=' . $_GET['update'] : '';

// Buscar dados do dispositivo
$result = supabaseRequest("devices?id=eq.$id$update_param", 'GET');

// Registrar resultado para depuração
error_log("Buscando dispositivo ID: $id - Resultado: " . json_encode($result));

if ($result['statusCode'] === 200 && !empty($result['body'])) {
    $dispositivo = $result['body'][0];
    error_log("Dados do dispositivo: " . json_encode($dispositivo));
} else {
    error_log("Erro ao buscar dispositivo: StatusCode=" . $result['statusCode'] . ", Body=" . json_encode($result['body']));
    header('Location: dispositivos.php?erro=Dispositivo não encontrado');
    exit;
}

// Buscar playlists atribuídas
$playlists_atribuidas = [];
$resultAtribuidas = supabaseRequest("device_playlists?device_id=eq.$id&select=*,playlists(*)", 'GET');
if ($resultAtribuidas['statusCode'] === 200) {
    $playlists_atribuidas = $resultAtribuidas['body'];
    // Verificar se há dados na resposta
    error_log('Resposta da API de playlists: ' . json_encode($resultAtribuidas));
}

// Verificar se a estrutura da tabela mudou, tentando buscar de outra forma se a primeira falhar
if (empty($playlists_atribuidas)) {
    $resultAtribuidas2 = supabaseRequest("playlists?devices=cs.{$id}&select=*", 'GET');
    if ($resultAtribuidas2['statusCode'] === 200) {
        $playlists_atribuidas = array_map(function($playlist) use ($id) {
            return [
                'playlist_id' => $playlist['id'],
                'device_id' => $id,
                'playlists' => $playlist
            ];
        }, $resultAtribuidas2['body']);
        error_log('Resposta da API alternativa: ' . json_encode($resultAtribuidas2));
    }
}

// Verificar se há mensagem via GET
if (isset($_GET['mensagem']) && isset($_GET['tipo'])) {
    $mensagem = $_GET['mensagem'];
    $tipo_mensagem = $_GET['tipo'];
}

// Verificar histórico de conexões
$conexoes = [];
$resultConexoes = supabaseRequest("device_connections?device_id=eq.$id&order=connected_at.desc&limit=10", 'GET');
if ($resultConexoes['statusCode'] === 200) {
    $conexoes = $resultConexoes['body'];
}

include 'includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Visualizar Dispositivo</h1>
        <p class="text-gray-600">Informações detalhadas do dispositivo</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        <a href="dispositivo_editar.php?id=<?php echo $id; ?>" class="px-4 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
            <i class="fas fa-edit mr-2"></i> Editar
        </a>
        <a href="dispositivos.php" class="px-4 py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition duration-300 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
</div>

<?php if (!empty($mensagem)): ?>
    <div class="<?php echo $tipo_mensagem === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?> p-4 mb-6" role="alert">
        <p><?php echo $mensagem; ?></p>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <!-- Informações do Dispositivo -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Informações do Dispositivo</h2>
                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                    <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 
                    (($dispositivo['status'] ?? '') === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                    <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'Ativo' : 
                    (($dispositivo['status'] ?? '') === 'maintenance' ? 'Em Manutenção' : 'Inativo'); ?>
                </span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Nome do Dispositivo</h3>
                        <p class="text-gray-900"><?php echo htmlspecialchars($dispositivo['name'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Localização</h3>
                        <p class="text-gray-900"><?php echo htmlspecialchars($dispositivo['location'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-1">Descrição</h3>
                    <p class="text-gray-900"><?php echo !empty($dispositivo['description']) ? htmlspecialchars($dispositivo['description']) : 'Sem descrição'; ?></p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Token de Acesso</h3>
                        <div class="flex">
                            <input type="text" class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50" value="<?php echo htmlspecialchars($dispositivo['token'] ?? ''); ?>" readonly>
                            <button class="bg-gray-200 px-3 py-2 rounded-r-lg border border-gray-300 border-l-0 hover:bg-gray-300" onclick="copiarToken()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Orientação da Tela</h3>
                        <p class="text-gray-900"><?php echo ($dispositivo['orientation'] ?? '') === 'landscape' ? 'Paisagem (Horizontal)' : 'Retrato (Vertical)'; ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Data de Criação</h3>
                        <p class="text-gray-900"><?php echo isset($dispositivo['created_at']) ? date('d/m/Y H:i', strtotime($dispositivo['created_at'])) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-700 mb-1">Última Atualização</h3>
                        <p class="text-gray-900"><?php echo isset($dispositivo['updated_at']) ? date('d/m/Y H:i', strtotime($dispositivo['updated_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Playlists Atribuídas -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Playlists Atribuídas</h2>
            </div>
            <div class="p-6">
                <?php if (empty($playlists_atribuidas)): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4">
                        <p>Nenhuma playlist atribuída a este dispositivo.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($playlists_atribuidas as $atribuicao): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($atribuicao['playlists']['name'] ?? 'Playlist Sem Nome'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $atribuicao['playlists']['description'] ? htmlspecialchars($atribuicao['playlists']['description']) : 'Sem descrição'; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="playlist_visualizar.php?id=<?php echo $atribuicao['playlist_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Histórico de Conexões -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Histórico de Conexões</h2>
            </div>
            <div class="p-6">
                <?php if (empty($conexoes)): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4">
                        <p>Nenhum histórico de conexão registrado.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data e Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endereço IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Navegador/Dispositivo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($conexoes as $conexao): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo isset($conexao['connected_at']) ? date('d/m/Y H:i:s', strtotime($conexao['connected_at'])) : 'N/A'; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($conexao['ip_address'] ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($conexao['user_agent'] ?? 'N/A'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                <?php echo ($conexao['status'] ?? '') === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ($conexao['status'] ?? '') === 'success' ? 'Sucesso' : 'Falha'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="lg:col-span-1">
        <!-- QR Code para Acesso -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">QR Code para Acesso</h2>
            </div>
            <div class="p-6 text-center">
                <div id="qrcode" class="mb-4 flex justify-center">
                    <!-- Fallback para o QR code se o JavaScript falhar -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode(SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? '')); ?>" alt="QR Code" class="qr-fallback" />
                </div>
                <p class="text-sm text-gray-700 mb-2">URL do Dispositivo:</p>
                <div class="flex mb-4">
                    <input type="text" id="device_url" class="w-full border border-gray-300 rounded-l-lg px-3 py-2 bg-gray-50" value="<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>" readonly>
                    <button class="bg-gray-200 px-3 py-2 rounded-r-lg border border-gray-300 border-l-0 hover:bg-gray-300" onclick="copiarURL()">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <a href="<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>" target="_blank" class="px-4 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 inline-flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Abrir Player
                </a>
            </div>
        </div>
        
        <!-- Status do Dispositivo -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Status do Dispositivo</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Status Atual:</span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800' : 
                            (($dispositivo['status'] ?? '') === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                            <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'Ativo' : 
                            (($dispositivo['status'] ?? '') === 'maintenance' ? 'Em Manutenção' : 'Inativo'); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Última Conexão:</span>
                        <span class="text-gray-500">
                            <?php echo isset($dispositivo['last_connection']) ? date('d/m/Y H:i', strtotime($dispositivo['last_connection'])) : 'Nunca'; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Endereço IP:</span>
                        <span class="text-gray-500">
                            <?php echo htmlspecialchars($dispositivo['last_ip'] ?? 'N/A'); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-700">Navegador/Dispositivo:</span>
                        <span class="text-gray-500">
                            <?php echo htmlspecialchars($dispositivo['last_user_agent'] ?? 'N/A'); ?>
                        </span>
                    </div>
                </div>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Ações Rápidas</h3>
                    <div class="flex flex-col space-y-2">
                        <button onclick="recarregarDispositivo()" class="w-full px-4 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-sync-alt mr-2"></i> Recarregar Dispositivo
                        </button>
                        <button onclick="reiniciarDispositivo()" class="w-full px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition duration-300 flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i> Reiniciar Player
                        </button>
                        <button onclick="toggleStatus()" class="w-full px-4 py-2 <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white font-medium rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-power-off mr-2"></i> <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'Desativar Dispositivo' : 'Ativar Dispositivo'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode.js/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Remover a imagem fallback
        const fallback = document.querySelector('.qr-fallback');
        if (fallback) {
            fallback.parentNode.removeChild(fallback);
        }
        
        // Gerar QR Code
        try {
            const qrcode = new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>",
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        } catch (error) {
            console.error("Erro ao gerar QR Code:", error);
        }
    });

    function copiarToken() {
        navigator.clipboard.writeText("<?php echo ($dispositivo['token'] ?? ''); ?>").then(() => {
            alert("Token copiado para a área de transferência!");
        });
    }

    function copiarURL() {
        navigator.clipboard.writeText("<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>").then(() => {
            alert("URL copiada para a área de transferência!");
        });
    }

    function recarregarDispositivo() {
        if (confirm("Deseja recarregar este dispositivo?")) {
            // Implementar ação de recarregar
            alert("Comando de recarga enviado!");
        }
    }

    function reiniciarDispositivo() {
        if (confirm("Deseja reiniciar o player neste dispositivo?")) {
            // Implementar ação de reiniciar
            alert("Comando de reinicialização enviado!");
        }
    }

    function toggleStatus() {
        const statusAtual = "<?php echo ($dispositivo['status'] ?? ''); ?>";
        const mensagem = statusAtual === 'active' 
            ? "Deseja desativar este dispositivo?" 
            : "Deseja ativar este dispositivo?";
            
        if (confirm(mensagem)) {
            // Redirecionar para a página de toggle com o ID do dispositivo
            window.location.href = "dispositivo_toggle.php?id=<?php echo $id; ?>";
        }
    }
</script>

<?php include 'includes/footer.php'; ?> 