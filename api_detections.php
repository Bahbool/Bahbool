<?php
header('Content-Type: application/json');

$DB_HOST="localhost"; $DB_PORT=3307; $DB_NAME="car_members_db"; $DB_USER="root"; $DB_PASS="";
$mysqli = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME,$DB_PORT);
if ($mysqli->connect_errno) { http_response_code(500); echo json_encode(["ok"=>false,"error"=>"DB"]); exit; }

$limit = isset($_GET['limit']) ? max(1, min(200, intval($_GET['limit']))) : 50;

$res = $mysqli->query("SELECT id, plate_text, ocr_conf, frame_path, detected_at, is_match, match_type, decision
                       FROM detection_log ORDER BY id DESC LIMIT $limit");
$data = [];
while ($row = $res->fetch_assoc()) {
  $row['is_match'] = (bool)$row['is_match'];
  $data[] = $row;
}
echo json_encode(["ok"=>true, "rows"=>$data]);
