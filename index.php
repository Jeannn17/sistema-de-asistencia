<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/models/registro.php';
require_once __DIR__ . '/app/controllers/registroController.php';

$db= database::conect();
$modelClass = 'registro';
$model = new $modelClass($db);
$controller= new registroController($model);

$action = $_GET['action'] ?? 'index';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$file   = $_FILES['file'] ?? null;

switch($action){
    case 'index'; $controller->index(); break;
    case 'create': $controller->create(); break;
    case 'store': $controller->store(); break;
    case 'editView': $controller->editView($id); break;
    case 'update': $controller->update(); break;
}
