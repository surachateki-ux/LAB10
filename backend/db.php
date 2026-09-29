<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "smart_farm_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed"]));
}
?>
```[cite: 1]

---

### 1.3 API บันทึก Telemetry Data (`api/store_telemetry.php`)[cite: 1]

สร้างไฟล์ชื่อ `store_telemetry.php` ไว้ในโฟลเดอร์ **`C:\xampp\htdocs\smart_farm\api\`**[cite: 1]  
*(ไฟล์นี้เอาไว้รับค่าอุณหภูมิและความชื้นจาก ESP32 ผ่าน HTTP POST)*[cite: 1]

```php
<?php
header("Content-Type: application/json");
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $temp = isset($data['temperature']) ? floatval($data['temperature']) : 0.0;
    $hum = isset($data['humidity']) ? floatval($data['humidity']) : 0.0;

    $stmt = $conn->prepare("INSERT INTO telemetry_data (temperature, humidity) VALUES (?, ?)");
    $stmt->bind_param("dd", $temp, $hum);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Telemetry data saved"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
?>
```[cite: 1]

---

### 1.4 API บันทึก Inventory Data (`api/store_inventory.php`)[cite: 1]

สร้างไฟล์ชื่อ `store_inventory.php` ไว้ในโฟลเดอร์ **`C:\xampp\htdocs\smart_farm\api\`**[cite: 1]  
*(ไฟล์นี้เอาไว้รับจำนวนผลไม้จากโปรแกรมตรวจจับ AI / YOLOv8 ผ่าน HTTP POST)*[cite: 1]

```php
<?php
header("Content-Type: application/json");
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $apple = isset($data['apple']) ? intval($data['apple']) : 0;
    $mango = isset($data['mango']) ? intval($data['mango']) : 0;
    $orange = isset($data['orange']) ? intval($data['orange']) : 0;

    $stmt = $conn->prepare("INSERT INTO warehouse_inventory (apple_count, mango_count, orange_count) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $apple, $mango, $orange);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Inventory data saved"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
}
?>
```[cite: 1]

---

### 1.5 API ดึงข้อมูลล่าสุดสำหรับ Dashboard (`api/get_latest.php`)[cite: 1]

สร้างไฟล์ชื่อ `get_latest.php` ไว้ในโฟลเดอร์ **`C:\xampp\htdocs\smart_farm\api\`**[cite: 1]  
*(ไฟล์นี้เอาไว้ให้แอปพลิเคชัน Flutter หรือเว็บเรียกดูข้อมูลล่าสุด)*[cite: 1]

```php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once '../db.php';

// ดึงข้อมูล Telemetry ล่าสุด
$telemetry_res = $conn->query("SELECT * FROM telemetry_data ORDER BY id DESC LIMIT 1");
$telemetry_data = ($telemetry_res && $telemetry_res->num_rows > 0) ? $telemetry_res->fetch_assoc() : null;

// ดึงข้อมูล Inventory ล่าสุด
$inventory_res = $conn->query("SELECT * FROM warehouse_inventory ORDER BY id DESC LIMIT 1");
$inventory_data = ($inventory_res && $inventory_res->num_rows > 0) ? $inventory_res->fetch_assoc() : null;

echo json_encode([
    "status" => "success",
    "telemetry" => $telemetry_data,
    "inventory" => $inventory_data
]);
?>
```[cite: 1]

---

### วิธีการทดสอบว่า API ทำงานถูกต้องหรือไม่

หลังจากสร้างไฟล์เสร็จทั้งหมดแล้ว สามารถทดสอบเปิดเบราว์เซอร์แล้วไปที่ URL นี้:
👉 `http://localhost/smart_farm/api/get_latest.php`

ถ้าทุกอย่างถูกต้อง เบราว์เซอร์จะแสดงผลลัพธ์เป็นโครงสร้าง JSON ดังนี้ (เนื่องจากเพิ่งสร้างฐานข้อมูลใหม่ ข้อมูลข้างในจะเป็น `null` ซึ่งถือว่าถูกต้อง):
```json
{"status":"success","telemetry":null,"inventory":null}