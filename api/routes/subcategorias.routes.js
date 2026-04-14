const express = require("express");
const router = express.Router();

const subcategoriasCtrl = require("../controllers/subcategorias.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   🧩 SUBCATEGORÍAS (SOLO ADMIN)
===================================================== */

// ➤ Listar subcategorías
router.get(
  "/",
  auth(["ADMIN"]),
  subcategoriasCtrl.listarSubcategorias
);

// ➤ Crear subcategoría
router.post(
  "/",
  auth(["ADMIN"]),
  subcategoriasCtrl.crearSubcategoria
);

// ➤ Actualizar subcategoría
router.put(
  "/:id",
  auth(["ADMIN"]),
  subcategoriasCtrl.actualizarSubcategoria
);

// ➤ Desactivar subcategoría
router.delete(
  "/:id",
  auth(["ADMIN"]),
  subcategoriasCtrl.desactivarSubcategoria
);

module.exports = router;