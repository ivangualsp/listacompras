<?php
require_once 'config.php';

// Obter token da URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Token de acesso não fornecido');
}

// Verificar se o token corresponde a um dispositivo válido
$result = supabaseRequest("devices?token=eq.$token&select=*", 'GET');

if ($result['statusCode'] !== 200 || empty($result['body'])) {
    die('Dispositivo não encontrado ou token inválido');
}

$dispositivo = $result['body'][0];

// Registrar a conexão
$connection_data = [
    'device_id' => $dispositivo['id'],
    'connected_at' => date('Y-m-d H:i:s'),
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'status' => 'success'
];

supabaseRequest('device_connections', 'POST', $connection_data);

// Atualizar dados do dispositivo
$update_data = [
    'last_connection' => date('Y-m-d H:i:s'),
    'last_ip' => $_SERVER['REMOTE_ADDR'],
    'last_user_agent' => $_SERVER['HTTP_USER_AGENT']
];

supabaseRequest("devices?id=eq.{$dispositivo['id']}", 'PATCH', $update_data);

// Buscar playlists associadas ao dispositivo
$playlists = [];
$resultPlaylists = supabaseRequest("device_playlists?device_id=eq.{$dispositivo['id']}&select=*,playlists(*)", 'GET');

if ($resultPlaylists['statusCode'] === 200 && !empty($resultPlaylists['body'])) {
    foreach ($resultPlaylists['body'] as $item) {
        if (isset($item['playlists'])) {
            $playlists[] = $item['playlists'];
        }
    }
}

// Buscar conteúdos associados ao dispositivo (quando não há playlist)
$conteudos = [];
if (empty($playlists)) {
    $resultConteudos = supabaseRequest("content_device?device_id=eq.{$dispositivo['id']}&select=*,content(*)", 'GET');
    if ($resultConteudos['statusCode'] === 200 && !empty($resultConteudos['body'])) {
        foreach ($resultConteudos['body'] as $item) {
            if (isset($item['content'])) {
                $conteudos[] = $item['content'];
            }
        }
    }
}

// Determinar a orientação da tela
$orientacao = $dispositivo['orientation'] ?? 'landscape';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaIndoor Player</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #000;
            color: #fff;
            overflow: hidden;
            position: relative;
            width: 100vw;
            height: 100vh;
        }
        
        .player-container {
            width: 100%;
            height: 100%;
            position: relative;
            background-color: #000;
        }
        
        .media-item {
            width: 100%;
            height: 100%;
            object-fit: contain;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        
        .media-item.active {
            opacity: 1;
            z-index: 10;
        }
        
        .media-item.image {
            object-fit: contain;
        }
        
        .media-item.video {
            object-fit: contain;
        }
        
        .status-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            z-index: 100;
            display: none;
        }
        
        .overlay-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            z-index: 1000;
        }
        
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 999;
        }
        
        .loading i {
            font-size: 5rem;
            color: white;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .portrait {
            transform: rotate(90deg);
            transform-origin: center;
        }
    </style>
</head>
<body>
    <div class="player-container" id="player-container">
        <div class="loading" id="loading">
            <i class="fas fa-spinner"></i>
        </div>
        
        <div class="overlay-message" id="no-content-message" style="display: none;">
            <h2>Sem conteúdo</h2>
            <p>Não há conteúdo disponível para exibição neste momento.</p>
            <p>ID do Dispositivo: <?php echo htmlspecialchars($dispositivo['name'] ?? 'N/A'); ?></p>
        </div>
        
        <div class="status-bar" id="status-bar">
            Dispositivo: <?php echo htmlspecialchars($dispositivo['name'] ?? 'N/A'); ?> | 
            Localização: <?php echo htmlspecialchars($dispositivo['location'] ?? 'N/A'); ?> |
            Conectado às: <?php echo date('H:i:s'); ?>
        </div>
    </div>

    <script>
        // Configuração
        const deviceId = "<?php echo $dispositivo['id'] ?? ''; ?>";
        const deviceOrientation = "<?php echo $orientacao; ?>";
        let currentIndex = 0;
        let mediaItems = [];
        let isPlaying = false;
        
        // Playlists e conteúdos
        const playlists = <?php echo json_encode($playlists); ?>;
        const conteudos = <?php echo json_encode($conteudos); ?>;
        
        // Elementos DOM
        const playerContainer = document.getElementById('player-container');
        const loading = document.getElementById('loading');
        const noContentMessage = document.getElementById('no-content-message');
        const statusBar = document.getElementById('status-bar');
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Ajustar orientação
            if (deviceOrientation === 'portrait') {
                playerContainer.classList.add('portrait');
            }
            
            // Tentar carregar conteúdo
            loadContent();
            
            // Configurar atualização automática a cada 5 minutos
            setInterval(checkForUpdates, 5 * 60 * 1000);
            
            // Mostrar barra de status ao passar o mouse
            playerContainer.addEventListener('mousemove', function() {
                statusBar.style.display = 'block';
                setTimeout(() => {
                    statusBar.style.display = 'none';
                }, 3000);
            });
        });
        
        // Carregar conteúdo
        function loadContent() {
            if (playlists.length > 0) {
                // Usar a primeira playlist (pode ser implementada lógica para alternar entre playlists)
                const playlistId = playlists[0].id;
                fetchPlaylistContent(playlistId);
            } else if (conteudos.length > 0) {
                // Usar conteúdos diretos
                prepareMediaItems(conteudos);
            } else {
                // Não há conteúdo disponível
                loading.style.display = 'none';
                noContentMessage.style.display = 'block';
            }
        }
        
        // Buscar conteúdo da playlist
        function fetchPlaylistContent(playlistId) {
            fetch(`<?php echo SITE_URL; ?>api/playlist_content.php?playlist_id=${playlistId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao buscar conteúdo da playlist');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.length > 0) {
                        prepareMediaItems(data);
                    } else {
                        throw new Error('Playlist vazia');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    if (conteudos.length > 0) {
                        // Fallback para conteúdos diretos se falhar a busca da playlist
                        prepareMediaItems(conteudos);
                    } else {
                        loading.style.display = 'none';
                        noContentMessage.style.display = 'block';
                    }
                });
        }
        
        // Preparar itens de mídia
        function prepareMediaItems(items) {
            mediaItems = items;
            if (mediaItems.length > 0) {
                loading.style.display = 'none';
                startMediaPlayback();
            } else {
                loading.style.display = 'none';
                noContentMessage.style.display = 'block';
            }
        }
        
        // Iniciar reprodução
        function startMediaPlayback() {
            if (!isPlaying && mediaItems.length > 0) {
                isPlaying = true;
                showMedia(currentIndex);
            }
        }
        
        // Mostrar mídia
        function showMedia(index) {
            // Limpar conteúdo existente
            const existingMedia = document.querySelectorAll('.media-item');
            existingMedia.forEach(item => {
                item.remove();
            });
            
            const mediaItem = mediaItems[index];
            const filePath = mediaItem.file_path;
            const displayTime = mediaItem.display_time || 30; // 30 segundos por padrão
            
            // Criar elemento de mídia
            let mediaElement;
            const fileType = getFileType(filePath);
            
            if (fileType === 'image') {
                mediaElement = document.createElement('img');
                mediaElement.src = filePath;
                mediaElement.className = 'media-item image active';
                mediaElement.onload = function() {
                    // Avançar para próxima mídia após o tempo especificado
                    setTimeout(nextMedia, displayTime * 1000);
                };
                mediaElement.onerror = function() {
                    console.error('Erro ao carregar imagem:', filePath);
                    setTimeout(nextMedia, 1000); // Avançar rápido se houver erro
                };
            } else if (fileType === 'video') {
                mediaElement = document.createElement('video');
                mediaElement.src = filePath;
                mediaElement.className = 'media-item video active';
                mediaElement.autoplay = true;
                mediaElement.controls = false;
                mediaElement.muted = false;
                mediaElement.onended = nextMedia;
                mediaElement.onerror = function() {
                    console.error('Erro ao carregar vídeo:', filePath);
                    setTimeout(nextMedia, 1000);
                };
            } else {
                // Tipo não suportado, avançar
                setTimeout(nextMedia, 1000);
                return;
            }
            
            playerContainer.appendChild(mediaElement);
        }
        
        // Próxima mídia
        function nextMedia() {
            currentIndex++;
            if (currentIndex >= mediaItems.length) {
                currentIndex = 0;
            }
            showMedia(currentIndex);
        }
        
        // Verificar atualizações
        function checkForUpdates() {
            // Recarregar a página para buscar conteúdo atualizado
            window.location.reload();
        }
        
        // Obter tipo de arquivo
        function getFileType(filePath) {
            const extension = filePath.split('.').pop().toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                return 'image';
            } else if (['mp4', 'webm', 'ogg'].includes(extension)) {
                return 'video';
            }
            return 'unknown';
        }
    </script>
</body>
</html> 