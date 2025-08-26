import sys
import easyocr
import os

# Suppress progress bar
os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"
os.environ["TF_CPP_MIN_LOG_LEVEL"] = "3"

image_path = sys.argv[1]
reader = easyocr.Reader(['en'], verbose=False)
results = reader.readtext(image_path)

plate = "UNKNOWN"
for bbox, text, conf in results:
    if conf > 0.6 and len(text) <= 10:
        plate = text.upper()
        break

print(plate)
