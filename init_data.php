<?php
// 設定回傳內容為 JSON 格式
header('Content-Type: application/json; charset=utf-8');

$ZONES = ['A', 'B', 'C', 'D'];
$AISLES = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
$SIDES = ['1', '2']; // 1左 2右
$COLS = ['1','2','3','4','5','6','7','8','9','A','B','C','D','E','F']; // 1~15
$LEVELS = ['1','2','3','4','5','6'];

$warehouse = [];
$emptySlots = [];

// 1. 初始化 5760 個空儲位
foreach ($ZONES as $x) {
    foreach ($AISLES as $y) {
        foreach ($SIDES as $z) {
            foreach ($COLS as $t) {
                foreach ($LEVELS as $v) {
                    $key = "{$x}{$y}-{$z}-{$t}{$v}";
                    $warehouse[$key] = null;
                    $emptySlots[] = $key; // 記錄空位以便後續隨機分配
                }
            }
        }
    }
}

// 2. 亂數產生 XX 種產品與安全庫存
$products = [];
$productInfo = []; // 儲存安全庫存資訊
$letters = 'ABCD'; 
while (count($products) < 80) {
    $l1 = $letters[rand(0, 3)];
    $l2 = $letters[rand(0, 3)];
    $num = rand(10, 99);
    $pCode = $l1 . $l2 . $num;
    
    // 避免重複的產品編號
    if (!in_array($pCode, $products)) {
        $products[] = $pCode;
        // 隨機產生安全庫存值 (2~80)
        $productInfo[$pCode] = [
            "safetyStock" => rand(20, 120)
        ];
    }
}

// 3. 將產品隨機放置到貨架 (每種分配 10~80 個棧板)
shuffle($emptySlots);
$slotIndex = 0;

foreach ($products as $prod) {
    $qty = rand(10, 80); 
    for ($i = 0; $i < $qty; $i++) {
        if ($slotIndex >= count($emptySlots)) {
            break 2; 
        }
        $slotKey = $emptySlots[$slotIndex];
        $warehouse[$slotKey] = $prod;
        $slotIndex++;
    }
}

// 4. 存入 data.json 檔案 (包含倉儲與產品安全庫存)
$dataFile = 'data.json';
$dataToSave = [
    "warehouse" => $warehouse,
    "products" => $productInfo
];
$jsonString = json_encode($dataToSave, JSON_PRETTY_PRINT);
$dataResult = file_put_contents($dataFile, $jsonString);


// 5. 隨機產生 10 筆訂單資料
$orders = [];
for ($i = 1; $i <= 30; $i++) {
    $orderItems = [];
    $itemCnt = rand(2, 5); 
    $totalQty = 0;
    
    $shuffledProducts = $products;
    shuffle($shuffledProducts);
    
    for ($j = 0; $j < $itemCnt; $j++) {
        $reqQty = rand(1, 10); 
        $orderItems[] = [
            "p" => $shuffledProducts[$j],
            "qty" => $reqQty
        ];
        $totalQty += $reqQty;
    }
    
    $orders[] = [
        "id" => "ORD-" . date("Y") . "-" . (1000 + $i),
        "items" => $orderItems,
        "totalQty" => $totalQty,
        "status" => "待處理"
    ];
}

// 6. 存入 orders.json 檔案 
$ordersFile = 'orders.json';
$ordersJsonString = json_encode($orders, JSON_PRETTY_PRINT);
$ordersResult = file_put_contents($ordersFile, $ordersJsonString);


// 7. 輸出執行結果
if ($dataResult !== false && $ordersResult !== false) {
    echo json_encode([
        "status" => "success",
        "message" => "成功產生資料與安全庫存設定！",
        "total_slots" => count($warehouse),
        "used_slots" => $slotIndex,
        "usage_rate" => round(($slotIndex / count($warehouse)) * 100, 2) . "%",
        "generated_orders" => count($orders)
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "寫入檔案失敗，請檢查資料夾寫入權限 (http群組)"
    ]);
}
?>