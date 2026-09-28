<?php

class database{
    public static function conect(): PDO{
         $host = "localhost";
         $db_name = "sistema_asistencia";
         $username = "postgres";
         $pass = "2525";

         $pdo = new PDO("pgsql:host=$host;dbname=$db_name", $username, $pass);
         $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
         return $pdo;
    }
}