<?php
// config/Database.php

class Database {

// Atributos privados para configuracion de CLASE PDO
    private $host = 'localhost';
    private $dbname   = 'sistema_asistencia';  
    private $user = 'postgres';
    private $pass = '2525';
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
        $this->connect();
    }

    //Metodo privado que instancia la Clase PDO
    private function connect() {
        $dsn = "pgsql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        //opciones de configuracion de PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException("Error de conexión: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    //Metodo GET que devuelve la conexion
    public function getConnection() {
        return $this->pdo;
    }
}