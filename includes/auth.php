<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requiereLogin()
{
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: /login.php');
        exit;
    }
}

function requiereRol($roles)
{
    requiereLogin();

    if (!in_array($_SESSION['rol'], $roles, true)) {
        header('Location: /index.php');
        exit;
    }
}