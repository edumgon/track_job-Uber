<?php

class DatabaseConnection
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $servername = "mysql";
        $username = getenv("MYSQL_USER");
        $password = getenv("MYSQL_PASSWORD");
        $database = getenv("MYSQL_DATABASE");

        try {
            $this->conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("<h1>Erro ao conectar ao banco de dados:</h1><p>" . $e->getMessage() . "</p>");
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
?>
