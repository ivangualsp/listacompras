<?php
require_once 'config.php';
verificarLogin();

$mensagem = '';
$erro = '';

// Obter tipos de conteúdo
$tipos_conteudo = supabaseRequest('content_types?select=*&order=name.asc', 'GET');

// Obter dispositivos para associação
$dispositivos = supabaseRequest('devices?select=*&order=name.asc', 'GET');

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
        // Upload de arquivo (em um sistema real, isso enviaria para o Supabase Storage)
        $file_path = '';
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
            // Inserir o conteúdo
            $conteudo_data = [
                'title' => $titulo,
                'description' => $descricao,
                'file_path' => $file_path,
                'content_type_id' => $tipo_conteudo_id,
                'duration' => $duracao,
                'resolution' => $resolucao,
                'valid_until' => $validade,
                'created_by' => $_SESSION['usuario_id']
            ];
            
            $result = supabaseRequest('content', 'POST', $conteudo_data);
            
            if ($result['statusCode'] === 201 && !empty($result['body'])) {
                $conteudo_id = $result['body'][0]['id'];
                
                // Associar aos dispositivos selecionados
                foreach ($dispositivos_selecionados as $dispositivo_id) {
                    $associacao_data = [
                        'content_id' => $conteudo_id,
                        'device_id' => $dispositivo_id,
                        'display_order' => 0, // Default
                        'display_time' => 30 // Default: 30 segundos
                    ];
                    
                    supabaseRequest('content_device', 'POST', $associacao_data);
                }
                
                $mensagem = 'Conteúdo adicionado com sucesso!';
                
                // Redirecionar para a página de conteúdo após 2 segundos
                header('Refresh: 2; URL=conteudo.php');
            } else {
                $erro = 'Erro ao adicionar conteúdo. Por favor, tente novamente.';
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Adicionar Novo Conteúdo</h1>
        <a href="conteudo.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    <p class="text-gray-600 mt-1">Crie e configure um novo conteúdo para exibição</p>
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
    <form action="conteudo_adicionar.php" method="post" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                <input type="text" id="titulo" name="titulo" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="tipo_conteudo_id" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Conteúdo <span class="text-red-500">*</span></label>
                <select id="tipo_conteudo_id" name="tipo_conteudo_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Selecione o tipo</option>
                    <?php if (isset($tipos_conteudo['body']) && is_array($tipos_conteudo['body'])): ?>
                        <?php foreach ($tipos_conteudo['body'] as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['name']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea id="descricao" name="descricao" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>
            <div>
                <label for="arquivo" class="block text-sm font-medium text-gray-700 mb-1">Arquivo</label>
                <input type="file" id="arquivo" name="arquivo" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, MP4, PDF</p>
            </div>
            <div>
                <label for="validade" class="block text-sm font-medium text-gray-700 mb-1">Válido até</label>
                <input type="date" id="validade" name="validade" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="duracao" class="block text-sm font-medium text-gray-700 mb-1">Duração</label>
                <input type="text" id="duracao" name="duracao" placeholder="Ex: 1:30 min" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="resolucao" class="block text-sm font-medium text-gray-700 mb-1">Resolução</label>
                <input type="text" id="resolucao" name="resolucao" placeholder="Ex: 1920x1080" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Dispositivos</h3>
            <p class="text-sm text-gray-600 mb-4">Selecione os dispositivos onde este conteúdo será exibido:</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php if (isset($dispositivos['body']) && is_array($dispositivos['body'])): ?>
                    <?php foreach ($dispositivos['body'] as $dispositivo): ?>
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg">
                            <input type="checkbox" id="dispositivo_<?php echo $dispositivo['id']; ?>" name="dispositivos[]" value="<?php echo $dispositivo['id']; ?>" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="dispositivo_<?php echo $dispositivo['id']; ?>" class="ml-2 block">
                                <span class="block text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dispositivo['name']); ?></span>
                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($dispositivo['specific_location']); ?></span>
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
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Salvar Conteúdo</button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 