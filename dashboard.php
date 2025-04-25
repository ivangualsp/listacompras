<?php
require_once 'config.php';
verificarLogin();

// Obter estatísticas do banco de dados
// Dispositivos ativos
$dispositivos_result = supabaseRequest('devices?select=count&status=eq.online', 'GET');
$total_dispositivos_ativos = isset($dispositivos_result['body'][0]['count']) ? $dispositivos_result['body'][0]['count'] : 0;

// Conteúdos ativos
$conteudo_result = supabaseRequest('content?select=count', 'GET');
$total_conteudos = isset($conteudo_result['body'][0]['count']) ? $conteudo_result['body'][0]['count'] : 0;

// Simulação de estatísticas (em um sistema real, viriam do banco de dados)
$visualizacoes = 3200;
$taxa_engajamento = 78;

// Listar dispositivos
$dispositivos = supabaseRequest('devices?select=*&order=name.asc', 'GET');

// Listar conteúdos (limitar a 3 para a dashboard)
$conteudos = supabaseRequest('content?select=*,content_types(name)&order=created_at.desc&limit=3', 'GET');
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Dashboard Corporativo</h1>
        <p class="text-gray-600">Gerencie sua rede de mídia indoor</p>
    </div>
    <a href="conteudo_adicionar.php" class="mt-4 md:mt-0 px-6 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
        <i class="fas fa-plus mr-2"></i> Novo Conteúdo
    </a>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Dispositivos Ativos</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_dispositivos_ativos; ?></h3>
            </div>
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-tv text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-green-500">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>2 novos esta semana</span>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Conteúdo Ativo</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_conteudos; ?></h3>
            </div>
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-photo-film text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-blue-500">
            <i class="fas fa-sync-alt mr-1"></i>
            <span>5 atualizações hoje</span>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Visualizações</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($visualizacoes / 1000, 1, ',', '.'); ?>K</h3>
            </div>
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-eye text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-green-500">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>12% esta semana</span>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Engajamento</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $taxa_engajamento; ?>%</h3>
            </div>
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-red-500">
            <i class="fas fa-arrow-down mr-1"></i>
            <span>5% esta semana</span>
        </div>
    </div>
</div>

<!-- Content Tabs -->
<div class="mb-6 border-b border-gray-200">
    <div class="flex space-x-8">
        <button class="content-tab active pb-3 font-medium">Todos</button>
        <button class="content-tab pb-3 font-medium text-gray-500 hover:text-blue-500">Vídeos</button>
        <button class="content-tab pb-3 font-medium text-gray-500 hover:text-blue-500">Imagens</button>
        <button class="content-tab pb-3 font-medium text-gray-500 hover:text-blue-500">Anúncios</button>
        <button class="content-tab pb-3 font-medium text-gray-500 hover:text-blue-500">Social</button>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php if (isset($conteudos['body']) && is_array($conteudos['body'])): ?>
        <?php foreach ($conteudos['body'] as $conteudo): ?>
            <div class="media-card bg-white rounded-xl shadow-md overflow-hidden transition duration-300">
                <div class="screen-preview h-48 flex items-center justify-center <?php echo $conteudo['content_types']['name'] == 'Vídeo' ? 'bg-gray-800 text-white' : ''; ?>">
                    <div class="text-center px-4">
                        <?php if ($conteudo['content_types']['name'] == 'Vídeo'): ?>
                            <i class="fas fa-play-circle text-5xl text-blue-500 mb-2"></i>
                        <?php elseif ($conteudo['content_types']['name'] == 'Imagem'): ?>
                            <i class="fas fa-image text-5xl text-purple-400 mb-2"></i>
                        <?php elseif ($conteudo['content_types']['name'] == 'Texto'): ?>
                            <i class="fas fa-font text-5xl text-yellow-500 mb-2"></i>
                        <?php else: ?>
                            <i class="fas fa-photo-film text-5xl text-green-500 mb-2"></i>
                        <?php endif; ?>
                        <p class="font-medium"><?php echo htmlspecialchars($conteudo['title']); ?></p>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($conteudo['title']); ?></h3>
                        <span class="<?php
                            if ($conteudo['content_types']['name'] == 'Vídeo') echo 'bg-blue-100 text-blue-800';
                            elseif ($conteudo['content_types']['name'] == 'Imagem') echo 'bg-purple-100 text-purple-800';
                            elseif ($conteudo['content_types']['name'] == 'Texto') echo 'bg-yellow-100 text-yellow-800';
                            else echo 'bg-green-100 text-green-800';
                        ?> text-xs px-2 py-1 rounded"><?php echo htmlspecialchars($conteudo['content_types']['name']); ?></span>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">
                        <?php echo $conteudo['duration'] ? 'Duração: ' . htmlspecialchars($conteudo['duration']) : ''; ?>
                        <?php echo $conteudo['resolution'] ? ' | ' . htmlspecialchars($conteudo['resolution']) : ''; ?>
                    </p>
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center text-gray-500">
                            <i class="fas fa-calendar mr-1"></i>
                            <span>Até <?php echo date('d/m/Y', strtotime($conteudo['valid_until'])); ?></span>
                        </div>
                        <div class="flex space-x-2">
                            <a href="conteudo_editar.php?id=<?php echo $conteudo['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="conteudo_excluir.php?id=<?php echo $conteudo['id']; ?>" class="text-red-500 hover:text-red-700" 
                               onclick="return confirm('Tem certeza que deseja excluir este conteúdo?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-3 text-center py-8">
            <p class="text-gray-500">Nenhum conteúdo encontrado. <a href="conteudo_adicionar.php" class="text-blue-500">Adicionar conteúdo</a>.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Devices Section -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Dispositivos Conectados</h2>
        <a href="dispositivo_adicionar.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
            <i class="fas fa-plus mr-2"></i> Adicionar Dispositivo
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localização</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Atividade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (isset($dispositivos['body']) && is_array($dispositivos['body'])): ?>
                    <?php foreach ($dispositivos['body'] as $dispositivo): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-tv text-blue-500 mr-3"></i>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dispositivo['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($dispositivo['model']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($dispositivo['floor']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($dispositivo['specific_location']); ?></div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="dispositivo_editar.php?id=<?php echo $dispositivo['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-cog"></i></a>
                                <a href="dispositivo_toggle.php?id=<?php echo $dispositivo['id']; ?>" class="text-red-600 hover:text-red-900"><i class="fas fa-power-off"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Nenhum dispositivo encontrado. <a href="dispositivo_adicionar.php" class="text-blue-500">Adicionar dispositivo</a>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Preview Section -->
<div class="bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Pré-visualização do Conteúdo</h2>
    <div class="flex flex-col lg:flex-row gap-6">
        <div class="lg:w-2/3">
            <div class="screen-preview rounded-lg overflow-hidden" style="height: 400px; background-color: #000;">
                <div class="h-full flex flex-col">
                    <div class="bg-blue-600 text-white p-4 text-center">
                        <h3 class="text-xl font-bold">Bem-vindo à Nossa Empresa</h3>
                    </div>
                    <div class="flex-grow flex items-center justify-center bg-gray-900">
                        <div class="text-center text-white px-8">
                            <i class="fas fa-play-circle text-6xl text-blue-400 mb-4"></i>
                            <h4 class="text-2xl font-bold mb-2">Novo Vídeo Institucional</h4>
                            <p class="text-gray-300">Assista à nossa nova apresentação corporativa</p>
                        </div>
                    </div>
                    <div class="bg-gray-800 text-white p-3 text-center">
                        <div class="marquee text-sm">
                            ⚡️ PROMOÇÃO: 30% DE DESCONTO EM TODOS OS SERVIÇOS ESTA SEMANA ⚡️ CONTATE NOSSO TIME COMERCIAL ⚡️
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="lg:w-1/3">
            <h3 class="font-medium text-gray-800 mb-3">Configurações de Exibição</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tempo de Exibição</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>10 segundos</option>
                        <option selected>30 segundos</option>
                        <option>1 minuto</option>
                        <option>5 minutos</option>
                        <option>Contínuo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordem de Exibição</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <option>Aleatória</option>
                        <option selected>Sequencial</option>
                        <option>Prioridade</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dispositivos</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="checkbox" id="device1" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="device1" class="ml-2 block text-sm text-gray-700">TV Recepção</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="device2" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="device2" class="ml-2 block text-sm text-gray-700">TV Sala de Reuniões</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="device3" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="device3" class="ml-2 block text-sm text-gray-700">TV Área de Descanso</label>
                        </div>
                    </div>
                </div>
                <button class="w-full mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Salvar Configurações
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 