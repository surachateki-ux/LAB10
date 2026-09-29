import io
import requests
from flask import Flask, request, jsonify
from PIL import Image
from ultralytics import YOLO

app = Flask(__name__)
# โหลด Model YOLO ของคุณ (ปรับตามชื่อไฟล์ที่มี เช่น 'best.pt' หรือ 'yolov8n.pt')
model = YOLO('best.pt')

@app.route('/predict', methods=['POST'])
def predict():
    if 'file' not in request.files:
        return jsonify({'error': 'No file uploaded'}), 400

    file = request.files['file']
    if file.filename == '':
        return jsonify({'error': 'Empty file'}), 400

    try:
        # IP เครื่องเซิร์ฟเวอร์ของคุณ
        API_URL = "http://10.96.47.121/smart_farm/api/store_inventory.php"
        
        img_bytes = file.read()
        img = Image.open(io.BytesIO(img_bytes))

        # ประมวลผลภาพด้วย YOLO (เอา device=0 ออกกรณีรันด้วย CPU)
        results = model.predict(source=img, conf=0.25)
        
        detections = []
        counts = {'apple': 0, 'mango': 0, 'orange': 0}

        for r in results:
            for box in r.boxes:
                cls_id = int(box.cls[0])
                detections.append({
                    'class': model.names[cls_id],
                    'confidence': round(float(box.conf[0]), 4),
                    'bounding_box': [round(x, 2) for x in box.xyxy[0].tolist()]
                })
                
                # นับจำนวนผลไม้แต่ละชนิด
                class_name = model.names[cls_id]
                if class_name in counts:
                    counts[class_name] += 1

        # เตรียมข้อมูล JSON สำหรับส่งไปยัง PHP API
        payload = {
            'apple': counts['apple'],
            'mango': counts['mango'],
            'orange': counts['orange']
        }
        
        # ส่ง POST request ไปที่ PHP API
        res = requests.post(API_URL, json=payload, timeout=2)
        print("API Status Code:", res.status_code)
        print("API Raw Response:", res.text)

        return jsonify({'status': 'success', 'total': len(detections), 'detections': detections}), 200

    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)