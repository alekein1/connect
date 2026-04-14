const express = require("express");
const router = express.Router();

const categoriasCtrl = require("../controllers/categorias.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   📂 CATEGORÍAS (SOLO ADMIN - POR LOCAL)
===================================================== */

// ➤ Listar categorías del local
router.get(
  "/",
  auth(["ADMIN"]),
  categoriasCtrl.listarCategorias
);

// ➤ Obtener categoría por ID (mismo local)
router.get(
  "/:id",
  auth(["ADMIN"]),
  categoriasCtrl.obtenerCategoria
);

// ➤ Crear categoría (usa id_local del token)
router.post(
  "/",
  auth(["ADMIN"]),
  categoriasCtrl.crearCategoria
);

// ➤ Actualizar categoría
router.put(
  "/:id",
  auth(["ADMIN"]),
  categoriasCtrl.actualizarCategoria
);

// ➤ Desactivar categoría (soft delete)
router.delete(
  "/:id",
  auth(["ADMIN"]),
  categoriasCtrl.desactivarCategoria
);

module.exports = router;