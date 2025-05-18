<?php
/**
 * Clase base para todos los modelos
 */
abstract class Model {
    protected $pdo;
    protected $table;
    protected $primaryKey;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        global $pdo as $globalPdo;
        $this->pdo = $pdo ?: $globalPdo;
        
        if (!$this->pdo) {
            throw new Exception("No hay conexión a la base de datos disponible");
        }
    }
    
    /**
     * Obtiene todos los registros de la tabla
     * @param int|null $userId ID del usuario para filtrar
     * @return array Lista de registros
     */
    public function getAll($userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            // Filtrar por usuario si se proporciona ID y la tabla tiene ese campo
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $sql .= " WHERE id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene un registro por su ID
     * @param int $id ID del registro
     * @param int|null $userId ID del usuario para verificación
     * @return array|false Datos del registro o false
     */
    public function getById($id, $userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona y la tabla tiene ese campo
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea un nuevo registro
     * @param array $data Datos del registro
     * @return int|string ID del registro insertado o mensaje de error
     */
    public function create(array $data) {
        try {
            // Preparar la consulta INSERT
            $columns = [];
            $placeholders = [];
            $values = [];
            
            foreach ($data as $column => $value) {
                $columns[] = $column;
                $placeholders[] = ":$column";
                $values[":$column"] = $value;
            }
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($values as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            
            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al insertar registro: " . $e->getMessage();
        }
    }
    
    /**
     * Actualiza un registro existente
     * @param int $id ID del registro
     * @param array $data Datos a actualizar
     * @param int|null $userId ID del usuario para verificación
     * @return bool|string True si se actualizó, mensaje de error si falla
     */
    public function update($id, array $data, $userId = null) {
        try {
            // Preparar la consulta UPDATE
            $setParts = [];
            $values = [];
            
            foreach ($data as $column => $value) {
                $setParts[] = "$column = :$column";
                $values[":$column"] = $value;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " 
                    WHERE {$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona y la tabla tiene ese campo
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($values as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al actualizar registro: " . $e->getMessage();
        }
    }
    
    /**
     * Elimina un registro
     * @param int $id ID del registro
     * @param int|null $userId ID del usuario para verificación
     * @return bool|string True si se eliminó, mensaje de error si falla
     */
    public function delete($id, $userId = null) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            // Añadir filtro de usuario si se proporciona y la tabla tiene ese campo
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->hasColumn('id_usuario')) {
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return "Error al eliminar registro: " . $e->getMessage();
        }
    }
    
    /**
     * Verifica si una tabla tiene una columna específica
     * @param string $column Nombre de la columna
     * @return bool True si la columna existe
     */
    protected function hasColumn($column) {
        try {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE :column";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':column', $column);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Registra errores en el log
     * @param string $message Mensaje de error
     */
    protected function logError($message) {
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/model_errors.log';
        $date = date('Y-m-d H:i:s');
        error_log("[$date] {$this->table}: $message\n", 3, $logFile);
    }
}