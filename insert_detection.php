<?php
header('Content-Type: application/json');

/* -- DB CONFIG -- */
$DB_HOST="localhost";
$DB_PORT=3306;                // change to 3306 if your MySQL uses default
$DB_NAME="car_members_db";
$DB_USER="root";
$DB_PASS="";
/* -------------- */

$mysqli = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME,$DB_PORT);
if ($mysqli->connect_errno) { http_response_code(500); echo json_encode(["ok"=>false,"error"=>"DB connect failed"]); exit; }

$in = json_decode(file_get_contents('php://input'), true);
$plate_raw = trim($in['plate_text'] ?? '');
$ocr_conf  = floatval($in['ocr_conf'] ?? 0);
$img_b64   = $in['image_b64'] ?? '';

if ($plate_raw === '' || $img_b64 === '') { http_response_code(400); echo json_encode(["ok"=>false,"error"=>"missing fields"]); exit; }

/* normalize: uppercase, remove spaces/dashes, fix common OCR confusions */
function norm($s){
  $s = strtoupper($s);
  $s = preg_replace('/[\s\-]/','',$s);
  $map = ['O'=>'0','I'=>'1','Z'=>'2','S'=>'5','B'=>'8','G'=>'6'];
  return strtr($s, $map);
}
$plate_norm = norm($plate_raw);

/* save image */
$dir = __DIR__."/uploads/plates";
if (!is_dir($dir)) @mkdir($dir,0777,true);
$img_bin = base64_decode(preg_replace('#^data:image/\w+;base64,#','',$img_b64));
$fname = 'plate_'.time().'_'.bin2hex(random_bytes(3)).'.jpg';
$abs = "$dir/$fname"; $rel = "uploads/plates/$fname";
if (!file_put_contents($abs, $img_bin)) { http_response_code(500); echo json_encode(["ok"=>false,"error"=>"save image failed"]); exit; }

/* check MEMBERS only */
$esc = $mysqli->real_escape_string($plate_norm);
$member_id = null;
$q = $mysqli->query("SELECT id FROM members WHERE REPLACE(REPLACE(UPPER(plate_number),' ','') ,'-','') = '$esc' LIMIT 1");
if ($q && $q->num_rows>0) { $member_id = intval($q->fetch_assoc()['id']); }

$is_member = $member_id !== null ? 1 : 0;
$decision  = $is_member ? 'granted' : 'denied';   // <- main flag used by UI

/* insert into detection_log */
$stmt = $mysqli->prepare("INSERT INTO detection_log (plate_text, ocr_conf, frame_path, is_match, match_type, member_id, decision)
                          VALUES (?,?,?,?,?,?,?)");
$match_type = $is_member ? 'registered' : NULL;   // keep for compatibility
$stmt->bind_param("sdsisss", $plate_norm, $ocr_conf, $rel, $is_member, $match_type, $member_id, $decision);
$stmt->execute(); $id = $stmt->insert_id; $stmt->close();

echo json_encode([
  "ok"=>true, "id"=>$id, "image_path"=>$rel,
  "is_member"=>(bool)$is_member, "decision"=>$decision, "member_id"=>$member_id
]);
