<?php
require_once 'config.php';
iniciarSessao();

// Verificar se usuário está logado
if (!estaLogado()) {
    header('Location: login.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';
$dispositivo = null;

// Obter ID do dispositivo da URL
$id = $_GET['id'] ?? '';

if (empty($id)) {
    header('Location: dispositivos.php');
    exit;
}

// Buscar dados do dispositivo
$result = supabaseRequest("devices?id=eq.$id", 'GET');

if ($result['statusCode'] === 200 && !empty($result['body'])) {
    $dispositivo = $result['body'][0];
} else {
    header('Location: dispositivos.php?erro=Dispositivo não encontrado');
    exit;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
    $localizacao = filter_input(INPUT_POST, 'localizacao', FILTER_SANITIZE_SPECIAL_CHARS);
    $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $orientation = filter_input(INPUT_POST, 'orientation', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Validar campos obrigatórios
    if (empty($nome) || empty($token)) {
        $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
        $tipo_mensagem = 'error';
    } else {
        // Preparar dados para atualização
        $dados = [
            'name' => $nome,
            'description' => $descricao,
            'location' => $localizacao,
            'token' => $token,
            'status' => $status,
            'orientation' => $orientation,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Atualizar dispositivo
        $result = supabaseRequest("devices?id=eq.$id", 'PATCH', $dados);
        
        if ($result['statusCode'] === 204) {
            $mensagem = 'Dispositivo atualizado com sucesso!';
            $tipo_mensagem = 'success';
            
            // Atualizar dados locais do dispositivo
            $dispositivo = array_merge($dispositivo, $dados);
        } else {
            $mensagem = 'Erro ao atualizar dispositivo. Tente novamente.';
            $tipo_mensagem = 'error';
        }
    }
}

// Buscar playlists para atribuição
$playlists = [];
$resultPlaylists = supabaseRequest("playlists", 'GET');
if ($resultPlaylists['statusCode'] === 200) {
    $playlists = $resultPlaylists['body'];
}

// Processar atribuição de playlist
if (isset($_POST['atribuir_playlist'])) {
    $playlist_id = filter_input(INPUT_POST, 'playlist_id', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (!empty($playlist_id)) {
        $dados_atribuicao = [
            'device_id' => $id,
            'playlist_id' => $playlist_id,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = supabaseRequest("device_playlists", 'POST', $dados_atribuicao);
        
        if ($result['statusCode'] === 201) {
            $mensagem = 'Playlist atribuída com sucesso!';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Erro ao atribuir playlist. Tente novamente.';
            $tipo_mensagem = 'error';
        }
    }
}

// Buscar playlists atribuídas
$playlists_atribuidas = [];
$resultAtribuidas = supabaseRequest("device_playlists?device_id=eq.$id&select=*,playlists(*)", 'GET');
if ($resultAtribuidas['statusCode'] === 200) {
    $playlists_atribuidas = $resultAtribuidas['body'];
}

// Remover playlist atribuída
if (isset($_GET['remover_playlist'])) {
    $atribuicao_id = filter_input(INPUT_GET, 'remover_playlist', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (!empty($atribuicao_id)) {
        $result = supabaseRequest("device_playlists?id=eq.$atribuicao_id", 'DELETE');
        
        if ($result['statusCode'] === 204) {
            header("Location: dispositivo_editar.php?id=$id&mensagem=Playlist removida com sucesso&tipo=success");
            exit;
        } else {
            $mensagem = 'Erro ao remover playlist. Tente novamente.';
            $tipo_mensagem = 'error';
        }
    }
}

// Verificar se há mensagem via GET
if (isset($_GET['mensagem']) && isset($_GET['tipo'])) {
    $mensagem = $_GET['mensagem'];
    $tipo_mensagem = $_GET['tipo'];
}

$pagina_atual = 'dispositivos';
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Editar Dispositivo</h1>
        <a href="dispositivos.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Voltar
        </a>
    </div>

    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Informações do Dispositivo</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome do Dispositivo*</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($dispositivo['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="localizacao" class="form-label">Localização</label>
                                <input type="text" class="form-control" id="localizacao" name="localizacao" value="<?php echo htmlspecialchars($dispositivo['location'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($dispositivo['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="token" class="form-label">Token de Acesso*</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="token" name="token" value="<?php echo htmlspecialchars($dispositivo['token'] ?? ''); ?>" required>
                                <a href="gerar_token.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">Gerar Novo</a>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Ativo</option>
                                    <option value="inactive" <?php echo ($dispositivo['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
                                    <option value="maintenance" <?php echo ($dispositivo['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Em Manutenção</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="orientation" class="form-label">Orientação da Tela</label>
                                <select class="form-select" id="orientation" name="orientation">
                                    <option value="landscape" <?php echo ($dispositivo['orientation'] ?? '') === 'landscape' ? 'selected' : ''; ?>>Paisagem (Horizontal)</option>
                                    <option value="portrait" <?php echo ($dispositivo['orientation'] ?? '') === 'portrait' ? 'selected' : ''; ?>>Retrato (Vertical)</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">QR Code para Acesso</h6>
                </div>
                <div class="card-body text-center">
                    <div id="qrcode" class="mb-3"></div>
                    <p class="text-sm mb-2">URL do Dispositivo:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="device_url" value="<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copiarURL()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <a href="<?php echo SITE_URL . 'player.php?token=' . ($dispositivo['token'] ?? ''); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i> Abrir Player
                    </a>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Status do Dispositivo</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Status Atual:</span>
                            <span class="badge <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'bg-success' : (($dispositivo['status'] ?? '') === 'maintenance' ? 'bg-warning' : 'bg-danger'); ?>">
                                <?php echo ($dispositivo['status'] ?? '') === 'active' ? 'Ativo' : (($dispositivo['status'] ?? '') === 'maintenance' ? 'Em Manutenção' : 'Inativo'); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Última Conexão:</span>
                            <span class="text-muted">
                                <?php echo isset($dispositivo['last_connection']) ? date('d/m/Y H:i', strtotime($dispositivo['last_connection'])) : 'Nunca'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Endereço IP:</span>
                            <span class="text-muted"><?php echo $dispositivo['last_ip'] ?? 'Desconhecido'; ?></span>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefresh">
                            <i class="fas fa-sync-alt me-1"></i> Atualizar Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Playlists Atribuídas</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAtribuirPlaylist">
                        <i class="fas fa-plus me-1"></i> Atribuir Playlist
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($playlists_atribuidas)): ?>
                        <div class="alert alert-info">
                            Nenhuma playlist atribuída a este dispositivo.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th width="150">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($playlists_atribuidas as $atribuicao): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($atribuicao['playlists']['name'] ?? 'Playlist Sem Nome'); ?></td>
                                            <td><?php echo $atribuicao['playlists']['description'] ? htmlspecialchars($atribuicao['playlists']['description']) : 'Sem descrição'; ?></td>
                                            <td>
                                                <a href="playlist_editar.php?id=<?php echo $atribuicao['playlist_id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <a href="?id=<?php echo $id; ?>&remover_playlist=<?php echo $atribuicao['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja remover esta playlist do dispositivo?')">
                                                    <i class="fas fa-trash"></i>
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
        </div>
    </div>
</div>

<!-- Modal Atribuir Playlist -->
<div class="modal fade" id="modalAtribuirPlaylist" tabindex="-1" aria-labelledby="modalAtribuirPlaylistLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAtribuirPlaylistLabel">Atribuir Playlist ao Dispositivo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="playlist_id" class="form-label">Selecione a Playlist</label>
                        <select class="form-select" id="playlist_id" name="playlist_id" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($playlists as $playlist): ?>
                                <option value="<?php echo $playlist['id']; ?>"><?php echo htmlspecialchars($playlist['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="atribuir_playlist" class="btn btn-primary">Atribuir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/qrcode.js/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gerar QR Code
    var deviceUrl = document.getElementById('device_url').value;
    new QRCode(document.getElementById("qrcode"), {
        text: deviceUrl,
        width: 150,
        height: 150,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Botão de atualização de status
    document.getElementById('btnRefresh').addEventListener('click', function() {
        alert('Solicitação de atualização de status enviada ao dispositivo.');
    });
});

// Função para gerar token aleatório
function gerarToken() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let token = '';
    
    for (let i = 0; i < 32; i++) {
        token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    document.getElementById('token').value = token;
}

// Função para copiar URL do dispositivo
function copiarURL() {
    const urlInput = document.getElementById('device_url');
    urlInput.select();
    document.execCommand('copy');
    alert('URL copiada para a área de transferência!');
}
</script> 