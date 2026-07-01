<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$dataFile = 'data.json';
$ordersFile = 'orders.json';

// 讀取目前的倉儲與產品資料 (兼容新舊格式)
function getWarehouseData() {
    global $dataFile;
    if (!file_exists($dataFile)) return ["warehouse" => [], "products" => []];
    $data = json_decode(file_get_contents($dataFile), true);
    
    // 如果是舊格式 (直接是 warehouse array)，自動轉換
    if (is_array($data) && !isset($data['warehouse'])) {
        return ["warehouse" => $data, "products" => []];
    }
    return is_array($data) ? $data : ["warehouse" => [], "products" => []];
}

function saveWarehouseData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

function getOrdersData() {
    global $ordersFile;
    if (!file_exists($ordersFile)) return [];
    $data = json_decode(file_get_contents($ordersFile), true);
    return is_array($data) ? $data : [];
}

function saveOrdersData($data) {
    global $ordersFile;
    file_put_contents($ordersFile, json_encode($data, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'get') {
        echo json_encode(["status" => "success", "data" => getWarehouseData()]);
    } elseif ($action === 'get_orders') {
        echo json_encode(["status" => "success", "data" => getOrdersData()]);
    } else {
        echo json_encode(["status" => "error", "message" => "無效的 action"]);
    }
    
} elseif ($method === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $action = isset($input['action']) ? $input['action'] : '';
    
    if ($action === 'update_slot') {
        if (!isset($input['slot'])) {
            echo json_encode(["status" => "error", "message" => "缺少 slot 參數"]);
            exit;
        }
        
        $slotKey = $input['slot'];
        $product = isset($input['product']) ? $input['product'] : null; 
        
        $fullData = getWarehouseData();
        // 僅更新 warehouse 節點
        $fullData['warehouse'][$slotKey] = $product;
        saveWarehouseData($fullData);
        
        echo json_encode([
            "status" => "success", 
            "message" => "儲位更新成功", 
            "slot" => $slotKey, 
            "product" => $product
        ]);
        
    } elseif ($action === 'update_order') {
        if (!isset($input['order_id']) || !isset($input['status'])) {
            echo json_encode(["status" => "error", "message" => "缺少訂單更新參數"]);
            exit;
        }
        
        $orderId = $input['order_id'];
        $newStatus = $input['status'];
        
        $ordersData = getOrdersData();
        $updated = false;
        
        foreach ($ordersData as &$order) {
            if ($order['id'] === $orderId) {
                $order['status'] = $newStatus;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            saveOrdersData($ordersData);
            echo json_encode(["status" => "success", "message" => "訂單狀態更新成功"]);
        } else {
            echo json_encode(["status" => "error", "message" => "找不到指定訂單"]);
        }

    } else {
         echo json_encode(["status" => "error", "message" => "無效的 action"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "不支援的請求方法"]);
}
?>