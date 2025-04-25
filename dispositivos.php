<?php
require_once 'config.php';
verificarLogin();

// Configuração de paginação
$itens_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtro por status (se existir)
$filtro_status = isset($_GET['status']) ? $_GET['status'] : null;
$filtro_query = '';

if ($filtro_status && $filtro_status !== 'todos') {
    $filtro_query = "&status=eq.$filtro_status";
}

// Buscar dispositivos com paginação
$dispositivos = supabaseRequest("devices?select=*&order=name.asc$filtro_query&limit=$itens_por_pagina&offset=$offset", 'GET');

// Contar total de registros para paginação
$total_result = supabaseRequest("devices?select=count", 'GET');
$total_registros = $total_result['statusCode'] === 200 && !empty($total_result['body']) ? $total_result['body'][0]['count'] : 0;
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Obter estatísticas de dispositivos
$dispositivos_online = supabaseRequest('devices?select=count&status=eq.online', 'GET');
$total_online = isset($dispositivos_online['body'][0]['count']) ? $dispositivos_online['body'][0]['count'] : 0;

$dispositivos_offline = supabaseRequest('devices?select=count&status=eq.offline', 'GET');
$total_offline = isset($dispositivos_offline['body'][0]['count']) ? $dispositivos_offline['body'][0]['count'] : 0;

// Mensagens
$mensagem = isset($_GET['mensagem']) ? $_GET['mensagem'] : '';
$erro = isset($_GET['erro']) ? $_GET['erro'] : '';
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Dispositivos</h1>
        <p class="text-gray-600">Controle todos os dispositivos da sua rede</p>
    </div>
    <a href="dispositivo_adicionar.php" class="mt-4 md:mt-0 px-6 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
        <i class="fas fa-plus mr-2"></i> Novo Dispositivo
    </a>
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

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total de Dispositivos</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_registros; ?></h3>
            </div>
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-tv text-xl"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Dispositivos Online</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_online; ?></h3>
            </div>
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Dispositivos Offline</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_offline; ?></h3>
            </div>
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filtros e Pesquisa -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="flex items-center space-x-4 mb-4 md:mb-0">
            <span class="text-gray-700 font-medium">Filtrar por:</span>
            <div class="flex space-x-2">
                <a href="dispositivos.php?status=todos" class="px-4 py-2 rounded-full text-sm <?php echo (!$filtro_status || $filtro_status === 'todos') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                    Todos
                </a>
                <a href="dispositivos.php?status=online" class="px-4 py-2 rounded-full text-sm <?php echo ($filtro_status === 'online') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                    Online
                </a>
                <a href="dispositivos.php?status=offline" class="px-4 py-2 rounded-full text-sm <?php echo ($filtro_status === 'offline') ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                    Offline
                </a>
            </div>
        </div>
        <div class="w-full md:w-auto">
            <form action="dispositivos.php" method="get" class="flex">
                <input type="text" name="busca" placeholder="Buscar dispositivo..." class="border border-gray-300 rounded-l-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 flex-grow">
                <button type="submit" class="bg-blue-500 text-white rounded-r-lg px-4 py-2 hover:bg-blue-600">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Devices Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Atividade</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (isset($dispositivos['body']) && is_array($dispositivos['body']) && !empty($dispositivos['body'])): ?>
                <?php foreach ($dispositivos['body'] as $dispositivo): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <i class="fas fa-tv text-blue-500 mr-3"></i>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dispositivo['name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($dispositivo['model']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($dispositivo['floor']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($dispositivo['specific_location']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $dispositivo['status'] == 'online' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo ucfirst($dispositivo['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php 
                                $ultima_atividade = new DateTime($dispositivo['last_activity']);
                                $agora = new DateTime();
                                $diff = $ultima_atividade->diff($agora);
                                
                                if ($diff->days > 0) {
                                    echo $diff->days . ' dias atrás';
                                } elseif ($diff->h > 0) {
                                    echo $diff->h . ' horas atrás';
                                } elseif ($diff->i > 0) {
                                    echo $diff->i . ' minutos atrás';
                                } else {
                                    echo 'Agora';
                                }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                            <a href="dispositivo_visualizar.php?id=<?php echo $dispositivo['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="dispositivo_editar.php?id=<?php echo $dispositivo['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="dispositivo_toggle.php?id=<?php echo $dispositivo['id']; ?>" class="text-<?php echo $dispositivo['status'] == 'online' ? 'red' : 'green'; ?>-600 hover:text-<?php echo $dispositivo['status'] == 'online' ? 'red' : 'green'; ?>-900 mr-3">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <a href="dispositivo_excluir.php?id=<?php echo $dispositivo['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza que deseja excluir este dispositivo?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        <i class="fas fa-tv text-gray-300 text-5xl mb-4"></i>
                        <p>Nenhum dispositivo encontrado.
                            <?php if ($filtro_status && $filtro_status !== 'todos'): ?>
                                <a href="dispositivos.php" class="text-blue-500">Limpar filtros</a> ou 
                            <?php endif; ?>
                            <a href="dispositivo_adicionar.php" class="text-blue-500">adicionar um novo dispositivo</a>.
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
    <div class="flex justify-center my-8">
        <div class="flex space-x-1">
            <?php if ($pagina_atual > 1): ?>
                <a href="dispositivos.php?pagina=<?php echo $pagina_atual - 1; ?><?php echo $filtro_status ? '&status=' . urlencode($filtro_status) : ''; ?>" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            // Determinar range de páginas a mostrar
            $range = 2; // Quantidade de páginas antes e depois da atual
            $inicio_range = max(1, $pagina_atual - $range);
            $fim_range = min($total_paginas, $pagina_atual + $range);
            
            // Mostrar páginas no range
            for ($i = $inicio_range; $i <= $fim_range; $i++) {
                $ativo = $i == $pagina_atual ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-100';
                echo '<a href="dispositivos.php?pagina=' . $i . ($filtro_status ? '&status=' . urlencode($filtro_status) : '') . '" class="px-4 py-2 ' . $ativo . ' rounded-md">' . $i . '</a>';
            }
            ?>
            
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="dispositivos.php?pagina=<?php echo $pagina_atual + 1; ?><?php echo $filtro_status ? '&status=' . urlencode($filtro_status) : ''; ?>" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Mapa de Dispositivos -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Mapa de Dispositivos</h2>
    <div class="bg-gray-100 rounded-lg h-64 flex items-center justify-center mb-4">
        <div class="text-center text-gray-500">
            <i class="fas fa-map-marker-alt text-4xl mb-2"></i>
            <p>Mapa interativo indisponível na versão atual</p>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4">
            <h3 class="font-medium text-blue-800 mb-2">Andar Térreo</h3>
            <p class="text-sm text-gray-600"><?php echo supabaseRequest('devices?select=count&floor=eq.Térreo', 'GET')['body'][0]['count'] ?? 0; ?> dispositivos</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-4">
            <h3 class="font-medium text-purple-800 mb-2">1º Andar</h3>
            <p class="text-sm text-gray-600"><?php echo supabaseRequest('devices?select=count&floor=eq.1', 'GET')['body'][0]['count'] ?? 0; ?> dispositivos</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <h3 class="font-medium text-green-800 mb-2">2º Andar</h3>
            <p class="text-sm text-gray-600"><?php echo supabaseRequest('devices?select=count&floor=eq.2', 'GET')['body'][0]['count'] ?? 0; ?> dispositivos</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 