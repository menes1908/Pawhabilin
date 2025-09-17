<?php
// Lightweight products handler using a JSON file for storage.
// Files are stored under admin/data and images under pictures/products.

$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/products.json';
$imagesDir = __DIR__ . '/../pictures/products';

if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
if (!is_dir($imagesDir)) mkdir($imagesDir, 0755, true);
if (!file_exists($dataFile)) file_put_contents($dataFile, json_encode([]));

function read_products($f){ $json = file_get_contents($f); $a = json_decode($json, true); return is_array($a)?$a:[]; }
function write_products($f, $arr){ file_put_contents($f, json_encode(array_values($arr), JSON_PRETTY_PRINT)); }

$action = $_REQUEST['action'] ?? '';
if ($action === 'list') {
    header('Content-Type: application/json');
    echo json_encode(read_products($dataFile));
    exit;
}

if ($action === 'get' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $list = read_products($dataFile);
    foreach ($list as $p) if ((string)$p['id'] === (string)$id) { header('Content-Type: application/json'); echo json_encode($p); exit; }
    http_response_code(404); echo json_encode(['error'=>'Not found']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_POST['action'] ?? '';
    $list = read_products($dataFile);

    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $price = trim($_POST['price'] ?? '');
        if ($name === '' || $price === '') { echo json_encode(['success'=>false,'error'=>'Missing name or price']); exit; }

        $imagePath = '';
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['image']['tmp_name'];
            $orig = basename($_FILES['image']['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','',pathinfo($orig, PATHINFO_FILENAME));
            $nameFile = $safe . '-' . time() . '.' . $ext;
            $dest = $imagesDir . '/' . $nameFile;
            if (move_uploaded_file($tmp, $dest)) {
                // store relative path from project root
                $imagePath = 'pictures/products/' . $nameFile;
            }
        }

        if ($action === 'add') {
            $newId = time() . rand(100,999);
            $item = ['id'=>$newId,'name'=>$name,'price'=>$price,'image'=>$imagePath];
            $list[] = $item;
            write_products($dataFile, $list);
            echo json_encode(['success'=>true,'item'=>$item]); exit;
        }

        if ($action === 'edit') {
            foreach ($list as &$p) {
                if ((string)$p['id'] === (string)$id) {
                    $p['name'] = $name; $p['price'] = $price;
                    if ($imagePath) $p['image'] = $imagePath;
                    write_products($dataFile, $list);
                    echo json_encode(['success'=>true,'item'=>$p]); exit;
                }
            }
            echo json_encode(['success'=>false,'error'=>'Not found']); exit;
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $found = false;
        foreach ($list as $i=>$p) if ((string)$p['id'] === (string)$id) { $found=true; array_splice($list,$i,1); break; }
        if ($found) { write_products($dataFile, $list); echo json_encode(['success'=>true]); exit; }
        echo json_encode(['success'=>false,'error'=>'Not found']); exit;
    }
}

// default
header('Content-Type: application/json'); echo json_encode(['error'=>'Invalid request']);
