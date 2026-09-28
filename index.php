<?php
require_once __DIR__. '/config/database.php';
require_once __DIR__ . '/models/asistenciaModel.php';
require_once __DIR__ . '/controllers/asistenciaController.php';

$db= database::conect();
$modelClass = 'asistenciaModel';
$model = new $modelClass($db);
$controller= new asistenciaController($model);

$action = $_GET['action'] ?? 'index';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$file   = $_FILES['file'] ?? null;

switch($action){
    case 'index'; $controller->index(); break;
    case 'validarSubidaExcel': $controller->validarSubidaExcel($file); break;
    case 'procesarExcel': $controller->procesarExcel(); break;
}
