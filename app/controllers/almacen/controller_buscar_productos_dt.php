<?php
// --- Resumen del Archivo ---
// Nombre: app/controllers/almacen/controller_buscar_productos_dt.php
// Función: Proporciona los datos de productos del almacén de un usuario específico
//          en el formato requerido por DataTables (Server-side processing).
//          Es invocado vía AJAX desde la tabla de búsqueda de productos en compras/create.php.
// Método HTTP esperado: POST
// Parámetros POST esperados (además de los de DataTables):
//   - id_usuario: El ID del usuario cuyos productos se listarán.
// Respuesta: JSON formateado para DataTables
//   {
//     "draw": <int>,
//     "recordsTotal": <int>,
//     "recordsFiltered": <int>,
//     "data": [
//       { "id_producto": ..., "codigo": ..., ... "iva_porcentaje_producto" (alias de iva_predeterminado): ..., "nombre_categoria": ... },
//       ...
//     ]
//   }

require_once __DIR__ . '/../../config.php'; // Contiene $pdo, $URL, $fechaHora

// Verificar que id_usuario se haya enviado
if (!isset($_POST['id_usuario'])) {
    echo json_encode([
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "ID de usuario no proporcionado."
    ]);
    exit;
}
$id_usuario = filter_var($_POST['id_usuario'], FILTER_VALIDATE_INT);
if ($id_usuario === false) {
    echo json_encode([
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => "ID de usuario inválido."
    ]);
    exit;
}


// Parámetros de DataTables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10; 
$searchValue = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// Columnas para ordenamiento y búsqueda (deben coincidir con los 'data' en JS)
$columns = [
    0 => 'p.id_producto',
    1 => 'p.codigo',
    2 => 'p.nombre',
    3 => 'p.stock',
    4 => 'p.precio_compra',
    5 => 'p.iva_predeterminado', // Ahora se ordena por la columna real
    6 => 'c.nombre_categoria'
];

$orderByColumnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2; // Default order por nombre
$orderByColumn = $columns[$orderByColumnIndex] ?? $columns[2];

$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
if (!in_array(strtolower($orderDir), ['asc', 'desc'])) {
    $orderDir = 'asc';
}

$bindings = [':id_usuario' => $id_usuario];

// --- Total de registros sin filtrar ---
$stmtTotal = $pdo->prepare("SELECT COUNT(p.id_producto) 
                            FROM tb_almacen p
                            WHERE p.id_usuario = :id_usuario");
$stmtTotal->execute([':id_usuario' => $id_usuario]);
$recordsTotal = $stmtTotal->fetchColumn();

// --- Construcción de la consulta principal ---
$sql = "SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.stock, p.stock_minimo, p.stock_maximo,
               p.precio_compra, p.precio_venta, p.fecha_ingreso, p.imagen,
               p.id_usuario, p.id_categoria, p.fyh_creacion, p.fyh_actualizacion,
               c.nombre_categoria,
               p.iva_predeterminado AS iva_porcentaje_producto -- Seleccionar la columna real y darle el alias esperado por JS
        FROM tb_almacen p
        INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_usuario = :id_usuario";

// --- Filtrado (búsqueda) ---
$searchSql = "";
if (!empty($searchValue)) {
    $searchSql = " AND (p.codigo LIKE :searchValue OR 
                       p.nombre LIKE :searchValue OR 
                       p.descripcion LIKE :searchValue OR 
                       c.nombre_categoria LIKE :searchValue OR
                       p.iva_predeterminado LIKE :searchValue )"; // También buscar por IVA
    $bindings[':searchValue'] = '%' . $searchValue . '%';
}
$sql .= $searchSql;

// --- Total de registros CON filtro de búsqueda (para recordsFiltered) ---
$stmtFiltered = $pdo->prepare("SELECT COUNT(p.id_producto) 
                                FROM tb_almacen p 
                                INNER JOIN tb_categorias c ON p.id_categoria = c.id_categoria
                                WHERE p.id_usuario = :id_usuario_count " . $searchSql);
$bindingsCountFiltered = [':id_usuario_count' => $id_usuario];
if (!empty($searchValue)) {
     $bindingsCountFiltered[':searchValue'] = '%' . $searchValue . '%';
}
$stmtFiltered->execute($bindingsCountFiltered);
$recordsFiltered = $stmtFiltered->fetchColumn();


// --- Ordenamiento y Paginación ---
$sql .= " ORDER BY " . $orderByColumn . " " . strtoupper($orderDir);
if ($length != -1) { 
    $sql .= " LIMIT :start, :length";
}

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
if (!empty($searchValue)) {
    $stmt->bindParam(':searchValue', $bindings[':searchValue'], PDO::PARAM_STR);
}
if ($length != -1) {
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':length', $length, PDO::PARAM_INT);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar respuesta para DataTables
$response = [
    "draw" => $draw,
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data,
];

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>