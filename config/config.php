<?php
// Configurações gerais da aplicação

const APP_NAME = 'GOA Hospitality Group';

// Ambiente: 'desenvolvimento' mostra os erros na tela; 'producao' esconde e grava no log do servidor.
// Ao publicar o site, troque para 'producao'.
const AMBIENTE = 'desenvolvimento';

// Fuso horário: datas de check-in e check-out dependem disso
date_default_timezone_set('America/Sao_Paulo');

error_reporting(E_ALL);
ini_set('display_errors', AMBIENTE === 'producao' ? '0' : '1');
ini_set('log_errors', '1');

// Cabeçalhos de proteção (só em requisições web, antes de qualquer saída)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');                    // o navegador não "adivinha" tipos de arquivo
    header('X-Frame-Options: SAMEORIGIN');                        // ninguém embute o site (e o painel) em outro site
    header('Referrer-Policy: strict-origin-when-cross-origin');   // não vaza endereços internos para outros sites
}

// BASE_URL: parte do endereço até a pasta public (ex.: /goa-reservas/public).
// É calculada sozinha, para o projeto funcionar em qualquer pasta do servidor.
$caminhoScript = $_SERVER['SCRIPT_NAME'] ?? '';
$posicao = strpos($caminhoScript, '/public/');
define('BASE_URL', $posicao !== false ? substr($caminhoScript, 0, $posicao + 7) : '');
unset($caminhoScript, $posicao);