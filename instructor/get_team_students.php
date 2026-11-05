<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'instructor'
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Instructor access is required.']);
    exit;
}

$teamId = filter_input(INPUT_GET, 'team_id', FILTER_VALIDATE_INT);
if (!$teamId || $teamId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid team is required.']);
    exit;
}

try {
    $query = $conn->prepare(
        "SELECT u.id, u.full_name, u.email
         FROM team_members tm
         INNER JOIN users u ON u.id = tm.user_id
         WHERE tm.team_id = :team_id
           AND u.role = 'student'
         ORDER BY u.full_name ASC"
    );
    $query->execute([':team_id' => $teamId]);
    $students = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'students' => array_map(static function ($student) {
            return [
                'id' => (int) $student['id'],
                'full_name' => $student['full_name'],
                'email' => $student['email'],
            ];
        }, $students),
    ], JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $e) {
    error_log('GROUPWARE team students endpoint error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Students could not be loaded.']);
}
