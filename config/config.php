<?php
// Configurações gerais da aplicação

const APP_NAME = 'GOA Hospitality Group';

// Fuso horário: datas de check-in e check-out dependem disso
date_default_timezone_set('America/Sao_Paulo');

// Em desenvolvimento mostramos os erros na tela. Em produção, trocar para '0'.
ini_set('display_errors', '1');
error_reporting(E_ALL);

// BASE_URL: parte do endereço até a pasta public (ex.: /goa-reservas/public).
// É calculada sozinha, para o projeto funcionar em qualquer pasta do servidor.
$caminhoScript = $_SERVER['SCRIPT_NAME'] ?? '';
$posicao = strpos($caminhoScript, '/public/');
define('BASE_URL', $posicao !== false ? substr($caminhoScript, 0, $posicao + 7) : '');
unset($caminhoScript, $posicao);
