<?php
/**
 * Clase base para todos los modelos
 */
abstract class Model {
    protected $pdo;
    protected $table;
    protected $primaryKey = 'id';
    protected $logFile;
    
    /**
     * Constructor
     * @param PDO|null $pdo Conexión PDO opcional
     */
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: Database::getInstance();
        $this->logFile = __DIR__ . '/../logs/' . strtolower(get_class($this)) . '.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Obtiene todos los registros (filtrados por usuario si es necesario)
     * @param int|null $userId ID del usuario para filtrar (null para obtener todos)
     * @return array Lista de registros
     */
    public function getAll($userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            // Si se proporciona un ID de usuario y la tabla tiene el campo id_usuario, filtrar
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $sql .= " WHERE id_usuario = :userId";
            }
            
            $sql .= " ORDER BY fyh_creacion DESC";
            $stmt = $this->pdo->prepare($sql);
            
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
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
     * Obtiene un registro por ID
     * @param int $id ID del registro
     * @param int|null $userId ID del usuario para verificar propiedad (null para omitir)
     * @return array|false Registro o false si no existe
     */
    public function getById($id, $userId = null) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            // Si se proporciona un ID de usuario y la tabla tiene el campo id_usuario, 
            // verificar que el registro pertenezca al usuario
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
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
     * @param array $data Datos a insertar
     * @return bool|string True si fue exitoso, mensaje de error en caso contrario
     */
    public function create(array $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Construir la consulta
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $lastId = $this->pdo->lastInsertId();
                $this->pdo->commit();
                return $lastId;
            } else {
                $this->pdo->rollBack();
                return "Error al insertar en {$this->table}";
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            return "Error de base de datos: " . $e->getMessage();
        }
    }
    
    /**
     * Actualiza un registro existente
     * @param int $id ID del registro
     * @param array $data Datos a actualizar
     * @param int|null $userId ID del usuario para verificar propiedad (null para omitir)
     * @return bool|string True si fue exitoso, mensaje de error en caso contrario
     */
    public function update($id, array $data, $userId = null) {
        try {
            // Verificar que el registro exista y pertenezca al usuario si se proporciona ID
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $record = $this->getById($id, $userId);
                if (!$record) {
                    return "El registro no existe o no pertenece al usuario";
                }
            }
            
            $this->pdo->beginTransaction();
            
            // Construir la consulta
            $setClause = [];
            foreach (array_keys($data) as $column) {
                $setClause[] = "$column = :$column";
            }
            $setClause = implode(', ', $setClause);
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
            
            // Añadir condición de usuario si es necesario
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return "Error al actualizar en {$this->table}";
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            return "Error de base de datos: " . $e->getMessage();
        }
    }
    
    /**
     * Elimina un registro
     * @param int $id ID del registro
     * @param int|null $userId ID del usuario para verificar propiedad (null para omitir)
     * @return bool|string True si fue exitoso, mensaje de error en caso contrario
     */
    public function delete($id, $userId = null) {
        try {
            // Verificar que el registro exista y pertenezca al usuario si se proporciona ID
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $record = $this->getById($id, $userId);
                if (!$record) {
                    return "El registro no existe o no pertenece al usuario";
                }
            }
            
            $this->pdo->beginTransaction();
            
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            // Añadir condición de usuario si es necesario
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $sql .= " AND id_usuario = :userId";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            
            if ($userId !== null && $this->tableHasColumn('id_usuario')) {
                $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
            }
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return "Error al eliminar de {$this->table}";
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $this->logError($e->getMessage());
            return "Error de base de datos: " . $e->getMessage();
        }
    }
    
    /**
     * Verifica si una tabla tiene una columna específica
     * @param string $columnName Nombre de la columna
     * @return bool True si la tabla tiene la columna
     */
    protected function tableHasColumn($columnName) {
        try {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE :columnName";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':columnName', $columnName);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $this->logError("Error al verificar si la tabla tiene la columna: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra un error en el archivo de log
     * @param string $message Mensaje de error
     */
    protected function logError($message) {
        $date = date('Y-m-d H:i:s');
        $log = "[$date] ERROR: $message" . PHP_EOL;
        error_log($log, 3, $this->logFile);
    }
}