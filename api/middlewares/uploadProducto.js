const multer = require("multer");
const path = require("path");
const fs = require("fs");

// 📂 carpeta: uploads/productos
const uploadPath = path.join(__dirname, "../uploads/productos");

// crear carpeta si no existe
if (!fs.existsSync(uploadPath)) {
  fs.mkdirSync(uploadPath, { recursive: true });
}

// ⚙️ configuración storage
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, uploadPath);
  },
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname);
    const nombre = `producto_${Date.now()}${ext}`;
    cb(null, nombre);
  }
});

// ✅ solo imágenes
const fileFilter = (req, file, cb) => {
  const permitidos = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
  if (!permitidos.includes(file.mimetype)) {
    return cb(new Error("Solo imágenes JPG, PNG o WEBP"), false);
  }
  cb(null, true);
};

// 🚀 exportar multer
const uploadProducto = multer({
  storage,
  fileFilter,
  limits: {
    fileSize: 5 * 1024 * 1024 // 5MB
  }
});

module.exports = uploadProducto;