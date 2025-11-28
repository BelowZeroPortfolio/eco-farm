#!/usr/bin/env python3
"""
YOLO Pest Detection Service - Flask Version
Persistent service that keeps the model loaded in memory for faster detection.
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from ultralytics import YOLO
import io
from PIL import Image
import os
import sys
import uuid
from datetime import datetime

app = Flask(__name__)

# Enable CORS for all routes
CORS(app, resources={
    r"/*": {
        "origins": [
            "https://sagayecofarm.infinityfreeapp.com",
            "http://localhost:*",
            "http://127.0.0.1:*"
        ],
        "methods": ["GET", "POST", "OPTIONS"],
        "allow_headers": ["Content-Type", "ngrok-skip-browser-warning"]
    }
})

# Global model variable
model = None

def load_model():
    """Load YOLO model at startup"""
    global model
    model_path = "best.pt"
    
    if not os.path.exists(model_path):
        print(f"ERROR: Model file not found: {model_path}", file=sys.stderr)
        sys.exit(1)
    
    print("=" * 60)
    print("YOLO Pest Detection Service - Flask Version")
    print("=" * 60)
    print(f"Loading YOLO model from: {model_path}")
    
    try:
        model = YOLO(model_path)
        print("✓ Model loaded successfully!")
        print(f"✓ Model classes: {len(model.names)}")
        print(f"✓ Service ready on http://127.0.0.1:5000")
        print("=" * 60)
    except Exception as e:
        print(f"ERROR: Failed to load model: {e}", file=sys.stderr)
        sys.exit(1)

@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "model": "loaded" if model is not None else "not loaded",
        "version": "2.0-flask"
    })

@app.route('/detect', methods=['POST'])
def detect():
    """Main detection endpoint"""
    try:
        # Check if model is loaded
        if model is None:
            return jsonify({
                "status": "error",
                "message": "Model not loaded"
            }), 500
        
        # Check if image is provided
        if 'image' not in request.files:
            return jsonify({
                "status": "error",
                "message": "No image file provided"
            }), 400
        
        image_file = request.files['image']
        
        # Validate file
        if image_file.filename == '':
            return jsonify({
                "status": "error",
                "message": "Empty filename"
            }), 400
        
        # Read image
        image_bytes = image_file.read()
        
        # Validate image size (max 10MB)
        if len(image_bytes) > 10 * 1024 * 1024:
            return jsonify({
                "status": "error",
                "message": "Image too large (max 10MB)"
            }), 400
        
        # Open image with PIL
        try:
            image = Image.open(io.BytesIO(image_bytes))
        except Exception as e:
            return jsonify({
                "status": "error",
                "message": f"Invalid image format: {str(e)}"
            }), 400
        
        # Run inference with save option
        results = model(image, verbose=False)
        
        # Extract detections
        pests = []
        annotated_image_path = None
        
        for result in results:
            boxes = result.boxes
            
            if boxes is not None and len(boxes) > 0:
                # Save annotated image with bounding boxes
                detections_dir = 'detections'
                if not os.path.exists(detections_dir):
                    os.makedirs(detections_dir, exist_ok=True)
                
                # Generate unique filename
                timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
                unique_id = str(uuid.uuid4())[:8]
                filename = f"detection_{timestamp}_{unique_id}.jpg"
                filepath = os.path.join(detections_dir, filename)
                
                # Get annotated image from result and save
                annotated_img = result.plot()  # Returns numpy array with boxes drawn
                from PIL import Image as PILImage
                import cv2
                
                # Convert BGR to RGB (OpenCV uses BGR)
                annotated_img_rgb = cv2.cvtColor(annotated_img, cv2.COLOR_BGR2RGB)
                pil_img = PILImage.fromarray(annotated_img_rgb)
                pil_img.save(filepath, 'JPEG', quality=90)
                
                annotated_image_path = filename  # Return relative path
                
                for box in boxes:
                    # Extract class name and confidence
                    class_id = int(box.cls[0])
                    confidence = float(box.conf[0])
                    
                    # Get bounding box coordinates
                    xyxy = box.xyxy[0].tolist()  # [x1, y1, x2, y2]
                    
                    # Get class name from model
                    class_name = model.names[class_id]
                    
                    # Add to pests list
                    pests.append({
                        "type": class_name,
                        "confidence": round(confidence * 100, 1),
                        "bbox": {
                            "x1": round(xyxy[0], 2),
                            "y1": round(xyxy[1], 2),
                            "x2": round(xyxy[2], 2),
                            "y2": round(xyxy[3], 2)
                        }
                    })
        
        # Return results with annotated image path
        return jsonify({
            "pests": pests,
            "count": len(pests),
            "annotated_image": annotated_image_path
        })
    
    except Exception as e:
        return jsonify({
            "status": "error",
            "message": f"Detection error: {str(e)}"
        }), 500

@app.route('/info', methods=['GET'])
def info():
    """Get model information"""
    if model is None:
        return jsonify({"error": "Model not loaded"}), 500
    
    return jsonify({
        "model_classes": list(model.names.values()),
        "num_classes": len(model.names),
        "model_type": "YOLOv8"
    })

if __name__ == '__main__':
    # Load model before starting server
    load_model()
    
    # Display ngrok tunnel information
    print("\n" + "=" * 60)
    print("🌐 ngrok TUNNEL SETUP")
    print("=" * 60)
    print("Local URL:  http://127.0.0.1:5000")
    print("=" * 60)
    print("\n💡 NEXT STEPS:")
    print("   1. Open another terminal")
    print("   2. Run: ngrok http 5000")
    print("   3. Copy the HTTPS URL from ngrok")
    print("   4. Update config/env.php with the URL")
    print("   5. Upload to InfinityFree")
    print("=" * 60)
    print("\n✅ Flask service is ready and waiting for requests...")
    print("=" * 60 + "\n")
    
    # Start Flask server
    # Note: debug=False for production, use True only for development
    app.run(
        host='127.0.0.1',
        port=5000,
        debug=False,
        threaded=True  # Allow multiple concurrent requests
    )
