<?php
// Shared helper to compute project rankings from external panel marks.

if (!function_exists('get_project_rankings')) {
    /**
     * @return array{available: bool, rows: array<int, array<string, mixed>>}
     */
    function get_project_rankings(mysqli $conn, ?int $limit = null, ?int $panelSessionId = null): array {
        $sql = "SELECT p.id, p.project_group_no, p.title, p.category, p.session,
                       leader.full_name AS leader_name,
                       (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) AS member_count,
                       COUNT(DISTINCT pe.id) AS evaluation_count,
                       AVG(psm.total_score) AS avg_total_score,
                       AVG(psm.demo3_score) AS avg_demo3_score
                FROM projects p
                JOIN panel_evaluations pe ON pe.project_id = p.id
                JOIN panel_student_marks psm ON psm.panel_evaluation_id = pe.id
                LEFT JOIN users leader ON leader.id = p.student_id
                WHERE p.department = 'JTMK' AND p.course_code = 'DFT50114'";
        if ($panelSessionId !== null) {
            $sql .= ' AND pe.panel_session_id = ' . (int) $panelSessionId;
        }
        $sql .= "
                GROUP BY p.id, p.project_group_no, p.title, p.category, p.session, leader.full_name
                ORDER BY avg_total_score DESC, avg_demo3_score DESC";

        $result = $conn->query($sql);
        if ($result === false) {
            return ['available' => false, 'rows' => []];
        }

        $rows = [];
        $rank = 0;
        while ($row = $result->fetch_assoc()) {
            $rank++;
            $row['rank'] = $rank;
            $row['avg_total_score'] = round((float) $row['avg_total_score'], 2);
            $row['avg_demo3_score'] = round((float) $row['avg_demo3_score'], 2);
            $rows[] = $row;
            if ($limit !== null && count($rows) >= $limit) {
                break;
            }
        }

        return ['available' => true, 'rows' => $rows];
    }
}
