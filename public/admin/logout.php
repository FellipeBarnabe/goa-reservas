<?php
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../includes/funcoes.php';
require __DIR__ . '/../../includes/auth.php';

// Sair é uma ação que muda o estado: só aceita POST com token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('admin/');
}
exigirCsrf();
encerrarSessao();
redirecionar('admin/login.php');