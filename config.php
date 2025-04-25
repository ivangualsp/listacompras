<?php
// Configurações do banco de dados Supabase
define('SUPABASE_URL', 'https://bhobujvrqzfpwsekdbhz.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImJob2J1anZycXpmcHdzZWtkYmh6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDU1MjM2MzAsImV4cCI6MjA2MTA5OTYzMH0.JlYQLW1pDQy-4rTfgH5wNr_krxlL_6MQXqcS2sUqEpI');
define('SUPABASE_API_URL', SUPABASE_URL . '/rest/v1/');

// Configurações gerais
define('SITE_NAME', 'MediaIndoor Corporativo');
define('SITE_URL', 'https://midiaindoor2.ivangualberto.com.br/');
define('UPLOAD_DIR', 'uploads/');
define('SESSION_TIME', 86400); // 24 horas

// Função para conexão ao Supabase via PDO (para consultas diretas)
function getPDO() {
    $dsn = "pgsql:host=" . parse_url(SUPABASE_URL, PHP_URL_HOST) . ";port=5432;dbname=postgres;user=postgres;password=YOUR_DB_PASSWORD";
    return new PDO($dsn);
}

// Função para fazer requisições à API REST do Supabase
function supabaseRequest($endpoint, $method = 'GET', $data = null) {
    $url = SUPABASE_API_URL . $endpoint;
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    // Adicionar header específico para operações PATCH
    if ($method === 'PATCH') {
        $headers[] = 'Prefer: return=minimal';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    // Habilitar informações de erro
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Adicionar informações de erro se houver falha
    $error = null;
    if ($response === false || ($statusCode >= 400 && $method !== 'PATCH')) {
        $error = curl_error($ch);
        error_log("Erro na requisição Supabase: " . $error . " (URL: $url, Método: $method)");
    }
    
    curl_close($ch);

    return [
        'statusCode' => $statusCode,
        'body' => json_decode($response, true),
        'error' => $error
    ];
}

// Função para iniciar a sessão
function iniciarSessao() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Função para verificar se usuário está logado
function estaLogado() {
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}

// Função para redirecionar se não estiver logado
function verificarLogin() {
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

// Função para hash de senha
function hashSenha($senha) {
    return password_hash($senha, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verificarSenha($senha, $hash) {
    return password_verify($senha, $hash);
}
?> 