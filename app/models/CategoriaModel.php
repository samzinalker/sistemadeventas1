<?php
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

/**
 * Modelo para categorías de productos
 */
class CategoriaModel extends Model {
    protected $table = 'tb_categorias';
    protected $primaryKey = 'id_categoria';
    
    /**
     * Constructor
     */
    public function __construct($pdo = null) {
        parent::__construct($pdo);
    }
}