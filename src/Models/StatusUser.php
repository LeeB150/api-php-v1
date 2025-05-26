<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class StatusUser
{
    private $pdo;
    private $table = 'sys_status';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // Obtener todos los estados
    public function getAll(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error (getAll): " . $e->getMessage());
            return [];
        }
    }

    // Encontrar un estado por id
    public function find(int $id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error (find): " . $e->getMessage());
            return false;
        }
    }

    // Crear un nuevo estado
    public function create(array $data): ?int
    {
        if (empty($data)) {
            return null;
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error (create): " . $e->getMessage());
            return null;
        }
    }

    // Actualizar un estado existente
    public function update(int $id, array $data)
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
        }
        $fields = implode(', ', $fields);
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $data['id'] = $id;
            $stmt->execute($data);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database error (update): " . $e->getMessage());
            return false;
        }
    }

    // Eliminar un estado
    public function delete(int $id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database error (delete): " . $e->getMessage());
            return false;
        }
    }
}