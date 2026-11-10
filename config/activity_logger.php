<?php

/**
 * GROUPWARE - Activity Logger Service
 * Logs team collaboration events (proposals, votes, tasks)
 */

function logActivity(
    PDO $conn,
    int $userId,
    int $teamId,
    string $activityType,
    string $description,
    ?string $relatedType = null,
    ?int $relatedId = null
): bool {
    $sql = "INSERT INTO activity_logs
            (user_id, team_id, activity_type, description,
             related_type, related_id)
            VALUES
            (:user_id, :team_id, :activity_type, :description,
             :related_type, :related_id)";

    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':team_id' => $teamId,
            ':activity_type' => $activityType,
            ':description' => $description,
            ':related_type' => $relatedType,
            ':related_id' => $relatedId
        ]);
    } catch (PDOException $e) {
        // If table doesn't exist (MySQL error 1146 / SQLSTATE 42S02), auto-create and retry
        if ($e->getCode() === '42S02' || strpos($e->getMessage(), "doesn't exist") !== false) {
            try {
                $createSql = "CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    team_id INT NOT NULL,
                    activity_type VARCHAR(50) NOT NULL,
                    description TEXT NOT NULL,
                    related_type VARCHAR(50) NULL,
                    related_id INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_team (team_id),
                    INDEX idx_user (user_id),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

                $conn->exec($createSql);

                $stmt = $conn->prepare($sql);
                return $stmt->execute([
                    ':user_id' => $userId,
                    ':team_id' => $teamId,
                    ':activity_type' => $activityType,
                    ':description' => $description,
                    ':related_type' => $relatedType,
                    ':related_id' => $relatedId
                ]);
            } catch (PDOException $createEx) {
                error_log("Failed to auto-create activity_logs: " . $createEx->getMessage());
                return false;
            }
        }

        error_log("GROUPWARE logActivity error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch the latest activity entries for a specific team
 */
function getRecentTeamActivities(PDO $conn, int $teamId, int $limit = 5): array {
    try {
        $stmt = $conn->prepare("
            SELECT a.id, a.activity_type, a.description, a.created_at,
                   u.full_name, u.role
            FROM activity_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.team_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $teamId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fail silently if table not yet created
        return [];
    }
}


