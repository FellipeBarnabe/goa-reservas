<?php
// Funções de apoio usadas em todo o projeto

// Escapa texto para exibir com segurança no HTML (proteção contra XSS)
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

// Monta o endereço de uma página ou arquivo da pasta public.
// url('css/style.css') -> /goa-reservas/public/css/style.css
function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

// Redireciona para outra página do site e encerra o script
function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

// 1250.5 -> "R$ 1.250,50"
function moeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// "2026-11-10" -> "10/11/2026"
function data_br(?string $data): string
{
    if (!$data) {
        return '';
    }
    $timestamp = strtotime($data);
    return $timestamp ? date('d/m/Y', $timestamp) : '';
}
