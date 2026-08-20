
<?php
require_once __DIR__ . '/DatabaseHelper.php';
require_once __DIR__ . '/DClase.php';

class DAsistencia {
    private $dbHelper;

    public function __construct() {
        $this->dbHelper = DatabaseHelper::getInstance();
    }
    public function getStatusBoardByClass(int $id_clase): array {
        $db = $this->dbHelper->connect();
        $sql = "SELECT a.estudiante_codigo_estudiante AS codigo_estudiante, u.nombres, u.apellidos, a.tipo
            FROM asistencia a
            JOIN estudiante e ON a.estudiante_codigo_estudiante = e.codigo_estudiante
            JOIN usuario u ON e.usuario_id = u.id
            WHERE a.clase_id = :id_clase
            ORDER BY u.apellidos, u.nombres";
        $stmt = $db->prepare($sql);
        $stmt->execute(['id_clase' => $id_clase]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAttendance(array $asistencia): bool {
        $db = $this->dbHelper;
        // Validar que no exista ya asistencia para ese estudiante en esa clase
        if ($this->existe($asistencia['codigo_estudiante'], $asistencia['id_clase'])) {
            echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">DEBUG DAsistencia: Ya existe asistencia para este estudiante en esta clase.</pre>';
            return false;
        }
        $data = [
            'clase_id' => $asistencia['id_clase'],
            'estudiante_codigo_estudiante' => $asistencia['codigo_estudiante'],
            'tipo' => $asistencia['tipo']
        ];
        try {
            $inserted = $db->insert('asistencia', $data);
            if ($inserted <= 0) {
                echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">DEBUG DAsistencia: Insert falló, valor devuelto: ' . var_export($inserted, true) . '</pre>';
            }
            return $inserted > 0;
        } catch (\PDOException $e) {
            echo '<pre style="background:#fef2f2;color:#991b1b;padding:1em;border-radius:8px;">PDOException: ' . htmlspecialchars($e->getMessage()) . '</pre>';
            return false;
        }
    }

    public function validateAttendance(array $asistencia): bool {
        // Ejemplo simple: validar que los campos no estén vacíos
        return isset($asistencia['id_clase'], $asistencia['codigo_estudiante'], $asistencia['tipo'])
            && $asistencia['id_clase'] > 0
            && $asistencia['codigo_estudiante'] !== ''
            && $asistencia['tipo'] !== '';
    }
    public function existe(string $codigo_estudiante, int $id_clase, int $excludedId = 0): bool {
        $db = $this->dbHelper->connect();
        $sql = "SELECT COUNT(*) FROM asistencia WHERE estudiante_codigo_estudiante = :codigo_estudiante AND clase_id = :id_clase";
        $params = [
            'codigo_estudiante' => $codigo_estudiante,
            'id_clase' => $id_clase
        ];
        if ($excludedId > 0) {
            $sql .= " AND id_asistencia != :excludedId";
            $params['excludedId'] = $excludedId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Devuelve los registros de asistencia de la clase para el reporte.
     * La generación del PDF debe hacerse en la capa de negocio o presentación.
     */
    public function generarReporte(int $id_clase): array {
        return $this->getStatusBoardByClass($id_clase);
    }

    /**
     * Actualiza el tipo de asistencia de un estudiante en una clase.
     */
    public function updateAttendance(int $id_clase, string $codigo, string $nuevoTipo): bool {
        $db = $this->dbHelper->connect();
        $sql = "UPDATE asistencia SET tipo = :tipo WHERE clase_id = :cid AND estudiante_codigo_estudiante = :codigo";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'tipo' => $nuevoTipo,
            'cid' => $id_clase,
            'codigo' => $codigo
        ]);
    }

    public function finalizar(int $id_clase): bool { 
        $db = DatabaseHelper::getInstance();
        // Suponiendo que la tabla clase tiene un campo finalizada boolean
        $updated = $db->update('clase', ['finalizada' => true], $id_clase);
        return $updated > 0;
    }
}
