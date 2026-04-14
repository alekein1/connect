const express = require("express");
const router = express.Router();

const productosCtrl = require("../controllers/productos.controller");
const { auth } = require("../middlewares/auth.middleware");
const uploadProducto = require("../middlewares/uploadProducto");

/* =====================================================
   🧾 PRODUCTOS (ADMIN - POR LOCAL)
===================================================== */

// 🔎 Buscar productos (🔥 NUEVO)
router.get(
  "/buscar",
  auth(["ADMIN"]),
  productosCtrl.buscarProductos
);

// 📋 Listar
router.get(
  "/",
  auth(["ADMIN"]),
  productosCtrl.listarProductos
);

// ➕ Crear (CON IMAGEN)
router.post(
  "/",
  auth(["ADMIN"]),
  uploadProducto.single("imagen"),
  productosCtrl.crearProducto
);

// ✏️ Actualizar (IMAGEN OPCIONAL)
router.put(
  "/:id",
  auth(["ADMIN"]),
  uploadProducto.single("imagen"),
  productosCtrl.actualizarProducto
);

// ✏️ Actualizar por POST (compatibilidad con FormData)
router.post(
  "/:id",
  auth(["ADMIN"]),
  uploadProducto.single("imagen"),
  productosCtrl.actualizarProducto
);

// ❌ Eliminar producto completo
router.delete(
  "/:id",
  auth(["ADMIN"]),
  productosCtrl.eliminarProducto
);

// 🧾 Etiquetas
router.post(
  "/:id/etiquetas",
  auth(["ADMIN"]),
  productosCtrl.generarEtiquetas
);

module.exports = router;
