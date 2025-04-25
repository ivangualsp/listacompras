<?php
require_once 'config.php';
verificarLogin();

// Obter período para os relatórios
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'semana';
$periodos_validos = ['semana', 'mes', 'trimestre', 'ano'];

if (!in_array($periodo, $periodos_validos)) {
    $periodo = 'semana';
}

// Preparar datas para filtros
$data_fim = date('Y-m-d');
$data_inicio = '';

switch ($periodo) {
    case 'semana':
        $data_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'trimestre':
        $data_inicio = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'ano':
        $data_inicio = date('Y-m-d', strtotime('-365 days'));
        break;
}

// Em um sistema real, buscaríamos estatísticas reais do Supabase
// Simulando dados para a página de relatórios
function gerarDadosSimulados($data_inicio, $data_fim, $min = 100, $max = 5000) {
    $dados = [];
    
    // Converter para timestamp
    $inicio = strtotime($data_inicio);
    $fim = strtotime($data_fim);
    
    // Gerar dados para cada dia no intervalo
    for ($i = $inicio; $i <= $fim; $i += 86400) { // 86400 = segundos em um dia
        $data = date('Y-m-d', $i);
        $dados[$data] = rand($min, $max);
    }
    
    return $dados;
}

// Gerar dados simulados para visualizações
$visualizacoes = gerarDadosSimulados($data_inicio, $data_fim);

// Calcular totais
$total_visualizacoes = array_sum($visualizacoes);
$media_visualizacoes = count($visualizacoes) > 0 ? round($total_visualizacoes / count($visualizacoes)) : 0;

// Dispositivos mais visualizados
$dispositivos = [
    ['nome' => 'TV Recepção', 'visualizacoes' => rand(1000, 5000), 'engajamento' => rand(70, 95)],
    ['nome' => 'TV Sala de Reuniões', 'visualizacoes' => rand(500, 3000), 'engajamento' => rand(50, 85)],
    ['nome' => 'TV Área de Descanso', 'visualizacoes' => rand(800, 4000), 'engajamento' => rand(60, 90)],
];

// Conteúdos mais visualizados
$conteudos = [
    ['titulo' => 'Vídeo Institucional', 'tipo' => 'Vídeo', 'visualizacoes' => rand(1000, 5000), 'engajamento' => rand(70, 95)],
    ['titulo' => 'Banner Promocional', 'tipo' => 'Imagem', 'visualizacoes' => rand(500, 3000), 'engajamento' => rand(50, 85)],
    ['titulo' => 'Comunicado Importante', 'tipo' => 'Texto', 'visualizacoes' => rand(800, 4000), 'engajamento' => rand(60, 90)],
];

// Formatar dados para uso em gráficos
$labels_json = json_encode(array_keys($visualizacoes));
$dados_json = json_encode(array_values($visualizacoes));
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Relatórios e Estatísticas</h1>
        <p class="text-gray-600">Acompanhe o desempenho da sua rede de mídia indoor</p>
    </div>
    <div class="mt-4 md:mt-0">
        <form action="relatorios.php" method="get" class="flex items-center space-x-2">
            <label for="periodo" class="text-gray-700">Período:</label>
            <select id="periodo" name="periodo" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                <option value="semana" <?php echo $periodo === 'semana' ? 'selected' : ''; ?>>Última Semana</option>
                <option value="mes" <?php echo $periodo === 'mes' ? 'selected' : ''; ?>>Último Mês</option>
                <option value="trimestre" <?php echo $periodo === 'trimestre' ? 'selected' : ''; ?>>Último Trimestre</option>
                <option value="ano" <?php echo $periodo === 'ano' ? 'selected' : ''; ?>>Último Ano</option>
            </select>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total de Visualizações</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($total_visualizacoes, 0, ',', '.'); ?></h3>
            </div>
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-eye text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-green-500">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>12% em relação ao período anterior</span>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Média Diária</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($media_visualizacoes, 0, ',', '.'); ?></h3>
            </div>
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-chart-line text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-blue-500">
            <i class="fas fa-sync-alt mr-1"></i>
            <span>Atualizado hoje</span>
        </div>
    </div>

    <div class="dashboard-card bg-white rounded-xl shadow-sm p-6 transition duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Taxa de Engajamento</p>
                <h3 class="text-2xl font-bold text-gray-800 mt-1">78%</h3>
            </div>
            <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                <i class="fas fa-thumbs-up text-xl"></i>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-sm text-yellow-500">
            <i class="fas fa-equals mr-1"></i>
            <span>Estável em relação ao período anterior</span>
        </div>
    </div>
</div>

<!-- Gráfico de Visualizações -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Tendência de Visualizações</h2>
    <div class="h-80">
        <canvas id="visualizacoesChart"></canvas>
    </div>
</div>

<!-- Dispositivos e Conteúdos -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Dispositivos Mais Visualizados -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Dispositivos Mais Visualizados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispositivo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visualizações</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engajamento</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dispositivos as $dispositivo): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-tv text-blue-500 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-900"><?php echo $dispositivo['nome']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($dispositivo['visualizacoes'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $dispositivo['engajamento']; ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500"><?php echo $dispositivo['engajamento']; ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Conteúdos Mais Visualizados -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Conteúdos Mais Visualizados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visualizações</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Engajamento</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($conteudos as $conteudo): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo $conteudo['titulo']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full <?php 
                                    if ($conteudo['tipo'] == 'Vídeo') echo 'bg-blue-100 text-blue-800';
                                    elseif ($conteudo['tipo'] == 'Imagem') echo 'bg-purple-100 text-purple-800';
                                    else echo 'bg-yellow-100 text-yellow-800';
                                ?>">
                                    <?php echo $conteudo['tipo']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($conteudo['visualizacoes'], 0, ',', '.'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo $conteudo['engajamento']; ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500"><?php echo $conteudo['engajamento']; ?>%</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Download de Relatórios -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Exportar Relatórios</h2>
    <p class="text-gray-600 mb-6">Faça o download de relatórios detalhados para análise mais aprofundada.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-blue-500 mr-2 text-lg"></i>
                    <h3 class="font-medium">Relatório Completo</h3>
                </div>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">PDF</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">Relatório completo com todas as métricas e análises do período.</p>
            <a href="#" class="text-blue-500 text-sm font-medium flex items-center">
                <i class="fas fa-download mr-1"></i> Download
            </a>
        </div>
        
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-table text-green-500 mr-2 text-lg"></i>
                    <h3 class="font-medium">Dados Brutos</h3>
                </div>
                <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">Excel</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">Planilha com todos os dados brutos para análises personalizadas.</p>
            <a href="#" class="text-blue-500 text-sm font-medium flex items-center">
                <i class="fas fa-download mr-1"></i> Download
            </a>
        </div>
        
        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <i class="fas fa-chart-pie text-purple-500 mr-2 text-lg"></i>
                    <h3 class="font-medium">Relatório de Engajamento</h3>
                </div>
                <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded-full">PDF</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">Análise detalhada do engajamento por conteúdo e dispositivo.</p>
            <a href="#" class="text-blue-500 text-sm font-medium flex items-center">
                <i class="fas fa-download mr-1"></i> Download
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Chart.js para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dados para o gráfico de visualizações
        const labels = <?php echo $labels_json; ?>;
        const data = <?php echo $dados_json; ?>;
        
        // Criar o gráfico de visualizações
        const ctx = document.getElementById('visualizacoesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Visualizações',
                    data: data,
                    fill: false,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script> 