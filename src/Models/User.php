<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

class User
{
    private $pdo;
    private $table = 'sys_users';

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function getFilteredPaginated($filters, $limit, $offset)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE 1=1"; // Condición base

            $params = [];

            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $query .= " AND {$key} LIKE :{$key}";
                    $params[$key] = "%{$value}%"; // Agrega parámetros para evitar SQL Injection
                }
            }

            $query .= " LIMIT :limit OFFSET :offset";

            $stmt = $this->pdo->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindParam(":{$key}", $value, PDO::PARAM_STR);
            }

            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Registrar el error y devolver un valor adecuado
            error_log("Database error (getFilteredPaginated): " . $e->getMessage());
            return false; // o false, según prefieras manejar el error
        }
    }

    public function find($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id AND id_status = 1");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database error: " . $e->getMessage()); // Guarda el error en el log
            return false;
        }
    }

    public function create(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            die("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, array $data)
    {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClauses);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $data['id'] = $id;
            $stmt->execute($data);
            return $stmt->rowCount(); // Devuelve el número de filas afectadas
        } catch (PDOException $e) {
            die("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount(); // Devuelve el número de filas afectadas
        } catch (PDOException $e) {
            die("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function findByEmailOrUsername($email, $username)
    {
        // Verificar por email
        try {
            $stmtEmail = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE email = :email");
            $stmtEmail->bindParam(':email', $email);
            $stmtEmail->execute();
            if ($stmtEmail->fetchColumn()) {
                return 'email';
            }
        } catch (PDOException $e) {
            die("Error al buscar usuario por email: " . $e->getMessage());
            return false;
        }

        // Verificar por username
        try {
            $stmtUsername = $this->pdo->prepare("SELECT id FROM {$this->table} WHERE username = :username");
            $stmtUsername->bindParam(':username', $username);
            $stmtUsername->execute();
            if ($stmtUsername->fetchColumn()) {
                return 'username'; // El username ya existe
            }
        } catch (PDOException $e) {
            die("Error al buscar usuario por username: " . $e->getMessage());
            return false;
        }

        return null; // No existe usuario con ese email o username
    }
}