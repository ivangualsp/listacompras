<?php
require_once 'config.php';
verificarLogin();

$mensagem = '';
$erro = '';
$conteudo = null;

// Obter ID do conteúdo da URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: conteudo.php');
    exit;
}

// Obter tipos de conteúdo
$tipos_conteudo = supabaseRequest('content_types?select=*&order=name.asc', 'GET');

// Obter dispositivos para associação
$dispositivos = supabaseRequest('devices?select=*&order=name.asc', 'GET');

// Buscar dados do conteúdo
$result = supabaseRequest("content?id=eq.$id", 'GET');

if ($result['statusCode'] === 200 && !empty($result['body'])) {
    $conteudo = $result['body'][0];
} else {
    header('Location: conteudo.php?erro=Conteúdo não encontrado');
    exit;
}

// Buscar dispositivos associados a este conteúdo
$dispositivos_associados = [];
$result_associados = supabaseRequest("content_device?content_id=eq.$id&select=device_id", 'GET');
if ($result_associados['statusCode'] === 200 && !empty($result_associados['body'])) {
    $dispositivos_associados = array_column($result_associados['body'], 'device_id');
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $tipo_conteudo_id = filter_input(INPUT_POST, 'tipo_conteudo_id', FILTER_SANITIZE_STRING);
    $duracao = filter_input(INPUT_POST, 'duracao', FILTER_SANITIZE_STRING);
    $resolucao = filter_input(INPUT_POST, 'resolucao', FILTER_SANITIZE_STRING);
    $validade = filter_input(INPUT_POST, 'validade', FILTER_SANITIZE_STRING);
    $dispositivos_selecionados = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : [];
    
    // Validação básica
    if (empty($titulo) || empty($tipo_conteudo_id)) {
        $erro = 'Por favor, preencha os campos obrigatórios (título e tipo de conteúdo).';
    } else {
        // Verificar se tem upload de novo arquivo
        $file_path = $conteudo['file_path']; // Mantém o arquivo atual por padrão
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
            // Diretório para uploads
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_name = uniqid() . '_' . $_FILES['arquivo']['name'];
            $file_path = $upload_dir . $file_name;
            
            // Mover o arquivo
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $file_path)) {
                // Arquivo enviado com sucesso
            } else {
                $erro = 'Erro ao fazer upload do arquivo.';
            }
        }
        
        if (empty($erro)) {
            // Atualizar o conteúdo
            $conteudo_data = [
                'title' => $titulo,
                'description' => $descricao,
                'file_path' => $file_path,
                'content_type_id' => $tipo_conteudo_id,
                'duration' => $duracao,
                'resolution' => $resolucao,
                'valid_until' => $validade,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = supabaseRequest("content?id=eq.$id", 'PATCH', $conteudo_data);
            
            if ($result['statusCode'] === 204) {
                // Remover todas as associações atuais com dispositivos
                supabaseRequest("content_device?content_id=eq.$id", 'DELETE');
                
                // Criar novas associações com os dispositivos selecionados
                foreach ($dispositivos_selecionados as $dispositivo_id) {
                    $associacao_data = [
                        'content_id' => $id,
                        'device_id' => $dispositivo_id,
                        'display_order' => 0, // Default
                        'display_time' => 30 // Default: 30 segundos
                    ];
                    
                    supabaseRequest('content_device', 'POST', $associacao_data);
                }
                
                $mensagem = 'Conteúdo atualizado com sucesso!';
                
                // Atualizar os dados na variável local
                $conteudo = array_merge($conteudo, $conteudo_data);
                
                // Atualizar a lista de dispositivos associados
                $dispositivos_associados = $dispositivos_selecionados;
                
                // Redirecionar para a página de conteúdo após 2 segundos
                header('Refresh: 2; URL=conteudo.php');
            } else {
                $erro = 'Erro ao atualizar conteúdo. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Editar Conteúdo</h1>
        <a href="conteudo.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    <p class="text-gray-600 mt-1">Edite as informações e configurações do conteúdo</p>
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
    <form action="conteudo_editar.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($conteudo['title'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="tipo_conteudo_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conteúdo <span class="text-red-500">*</span></label>
                <select id="tipo_conteudo_id" name="tipo_conteudo_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Selecione o tipo</option>
                    <?php if (isset($tipos_conteudo['body']) && is_array($tipos_conteudo['body'])): ?>
                        <?php foreach ($tipos_conteudo['body'] as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php echo ($conteudo['content_type_id'] == $tipo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($conteudo['description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Arquivo</label>
                <input type="file" id="arquivo" name="arquivo" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, MP4, PDF</p>
                <?php if (!empty($conteudo['file_path'])): ?>
                    <div class="mt-2">
                        <span class="text-xs font-medium text-gray-700">Arquivo atual:</span>
                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars(basename($conteudo['file_path'])); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <label for="validade" class="block text-sm font-medium text-gray-700 mb-1">Válido até</label>
                <input type="date" id="validade" name="validade" value="<?php echo $conteudo['valid_until'] ? date('Y-m-d', strtotime($conteudo['valid_until'])) : ''; ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="duracao" class="block text-sm font-medium text-gray-700 mb-1">Duração</label>
                <input type="text" id="duracao" name="duracao" value="<?php echo htmlspecialchars($conteudo['duration'] ?? ''); ?>" placeholder="Ex: 1:30 min" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="resolucao" class="block text-sm font-medium text-gray-700 mb-1">Resolução</label>
                <input type="text" id="resolucao" name="resolucao" value="<?php echo htmlspecialchars($conteudo['resolution'] ?? ''); ?>" placeholder="Ex: 1920x1080" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Dispositivos</h3>
            <p class="text-sm text-gray-600 mb-4">Selecione os dispositivos onde este conteúdo será exibido:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php if (isset($dispositivos['body']) && is_array($dispositivos['body'])): ?>
                    <?php foreach ($dispositivos['body'] as $dispositivo): ?>
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg">
                            <input type="checkbox" id="dispositivo_<?php echo $dispositivo['id']; ?>" name="dispositivos[]" value="<?php echo $dispositivo['id']; ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                <?php echo in_array($dispositivo['id'], $dispositivos_associados) ? 'checked' : ''; ?>>
                            <label for="dispositivo_<?php echo $dispositivo['id']; ?>" class="ml-2 block">
                                <span class="block text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dispositivo['name']); ?></span>
                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($dispositivo['specific_location'] ?? $dispositivo['location'] ?? ''); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 col-span-3">Nenhum dispositivo disponível.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3">
            <a href="conteudo.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Salvar Alterações</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 