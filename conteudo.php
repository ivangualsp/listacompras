<?php
require_once 'config.php';
verificarLogin();

// Configuração de paginação
$itens_por_pagina = 9;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

// Filtro por tipo (se existir)
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$filtro_query = '';

if ($filtro_tipo && $filtro_tipo !== 'todos') {
    // Buscar o ID do tipo pelo nome
    $tipo_result = supabaseRequest("content_types?select=id&name=eq.$filtro_tipo", 'GET');
    if ($tipo_result['statusCode'] === 200 && !empty($tipo_result['body'])) {
        $tipo_id = $tipo_result['body'][0]['id'];
        $filtro_query = "&content_type_id=eq.$tipo_id";
    }
}

// Buscar conteúdos com paginação
$conteudos = supabaseRequest("content?select=*,content_types(name)&order=created_at.desc$filtro_query&limit=$itens_por_pagina&offset=$offset", 'GET');

// Contar total de registros para paginação
$total_result = supabaseRequest("content?select=count", 'GET');
$total_registros = $total_result['statusCode'] === 200 && !empty($total_result['body']) ? $total_result['body'][0]['count'] : 0;
$total_paginas = ceil($total_registros / $itens_por_pagina);

// Buscar tipos de conteúdo para o filtro
$tipos_conteudo = supabaseRequest('content_types?select=*&order=name.asc', 'GET');
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Conteúdo</h1>
        <p class="text-gray-600">Visualize e gerencie todo o conteúdo da sua rede</p>
    </div>
    <a href="conteudo_adicionar.php" class="mt-4 md:mt-0 px-6 py-2 bg-blue-500 text-white font-medium rounded-lg hover:bg-blue-600 transition duration-300 flex items-center">
        <i class="fas fa-plus mr-2"></i> Novo Conteúdo
    </a>
</div>

<!-- Filtros e Pesquisa -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="flex items-center space-x-4 mb-4 md:mb-0">
            <span class="text-gray-700 font-medium">Filtrar por:</span>
            <div class="flex space-x-2">
                <a href="conteudo.php?tipo=todos" class="px-4 py-2 rounded-full text-sm <?php echo (!$filtro_tipo || $filtro_tipo === 'todos') ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                    Todos
                </a>
                <?php if (isset($tipos_conteudo['body']) && is_array($tipos_conteudo['body'])): ?>
                    <?php foreach ($tipos_conteudo['body'] as $tipo): ?>
                        <a href="conteudo.php?tipo=<?php echo urlencode($tipo['name']); ?>" class="px-4 py-2 rounded-full text-sm <?php echo ($filtro_tipo === $tipo['name']) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                            <?php echo htmlspecialchars($tipo['name']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="w-full md:w-auto">
            <form action="conteudo.php" method="get" class="flex">
                <input type="text" name="busca" placeholder="Buscar conteúdo..." class="border border-gray-300 rounded-l-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 flex-grow">
                <button type="submit" class="bg-blue-500 text-white rounded-r-lg px-4 py-2 hover:bg-blue-600">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <?php if (isset($conteudos['body']) && is_array($conteudos['body']) && !empty($conteudos['body'])): ?>
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
                            <span>Até <?php echo $conteudo['valid_until'] ? date('d/m/Y', strtotime($conteudo['valid_until'])) : 'N/A'; ?></span>
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
            <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-500">Nenhum conteúdo encontrado. 
                <?php if ($filtro_tipo && $filtro_tipo !== 'todos'): ?>
                    <a href="conteudo.php" class="text-blue-500">Limpar filtros</a> ou 
                <?php endif; ?>
                <a href="conteudo_adicionar.php" class="text-blue-500">adicionar um novo conteúdo</a>.
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
    <div class="flex justify-center my-8">
        <div class="flex space-x-1">
            <?php if ($pagina_atual > 1): ?>
                <a href="conteudo.php?pagina=<?php echo $pagina_atual - 1; ?><?php echo $filtro_tipo ? '&tipo=' . urlencode($filtro_tipo) : ''; ?>" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            // Determinar range de páginas a mostrar
            $range = 2; // Quantidade de páginas antes e depois da atual
            $inicio_range = max(1, $pagina_atual - $range);
            $fim_range = min($total_paginas, $pagina_atual + $range);
            
            // Mostrar link para primeira página se início do range não for 1
            if ($inicio_range > 1) {
                echo '<a href="conteudo.php?pagina=1' . ($filtro_tipo ? '&tipo=' . urlencode($filtro_tipo) : '') . '" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">1</a>';
                if ($inicio_range > 2) {
                    echo '<span class="px-4 py-2">...</span>';
                }
            }
            
            // Mostrar páginas no range
            for ($i = $inicio_range; $i <= $fim_range; $i++) {
                $ativo = $i == $pagina_atual ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-blue-100';
                echo '<a href="conteudo.php?pagina=' . $i . ($filtro_tipo ? '&tipo=' . urlencode($filtro_tipo) : '') . '" class="px-4 py-2 ' . $ativo . ' rounded-md">' . $i . '</a>';
            }
            
            // Mostrar link para última página se fim do range não for total_paginas
            if ($fim_range < $total_paginas) {
                if ($fim_range < $total_paginas - 1) {
                    echo '<span class="px-4 py-2">...</span>';
                }
                echo '<a href="conteudo.php?pagina=' . $total_paginas . ($filtro_tipo ? '&tipo=' . urlencode($filtro_tipo) : '') . '" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">' . $total_paginas . '</a>';
            }
            ?>
            
            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="conteudo.php?pagina=<?php echo $pagina_atual + 1; ?><?php echo $filtro_tipo ? '&tipo=' . urlencode($filtro_tipo) : ''; ?>" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-blue-100">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?> 