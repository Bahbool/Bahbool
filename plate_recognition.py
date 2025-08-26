import sys
import easyocr
import cv2
import numpy as np
from ultralytics import YOLO
import os
import re

# Suppress unnecessary logs
os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

# Input image path
image_path = sys.argv[1]

# Check if image exists
if not os.path.exists(image_path):
    print("ERROR: Image file not found")
    sys.exit(1)

try:
    # Load YOLOv8 model (fine-tuned for license plates)
    model = YOLO('yolov8n.pt')  # Replace with path to fine-tuned model
    img = cv2.imread(image_path)
    if img is None:
        print("ERROR: Failed to load image")
        sys.exit(1)

    # Resize image for consistency
    img = cv2.resize(img, (800, 600))

    # Detect license plates
    results = model.predict(img, conf=0.5)  # Confidence threshold
    plate = "UNKNOWN"
    max_conf = 0.6

    for result in results:
        for box in result.boxes:
            # Extract bounding box coordinates
            x1, y1, x2, y2 = map(int, box.xyxy[0])
            # Crop license plate region
            plate_img = img[y1:y2, x1:x2]

            # Initialize EasyOCR reader
            reader = easyocr.Reader(['en'], verbose=False)
            ocr_results = reader.readtext(plate_img)

            # Select best text (highest confidence, valid plate format)
            for _, text, conf in ocr_results:
                if conf > max_conf and len(text) <= 12 and re.match(r'^[A-Z0-9\s\-]{1,12}$', text):
                    plate = text.upper().replace(" ", "").replace("-", "")
                    max_conf = conf

    print(plate)

except Exception as e:
    print(f"ERROR: Processing failed - {str(e)}")
    sys.exit(1)