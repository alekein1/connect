const express = require("express");
const router = express.Router();

const inventarioCtrl = require("../controllers/inventario.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   📊 INVENTARIO (ADMIN)
===================================================== */

// 🔹 Listar inventario (con filtros)
router.get(
  "/",
  auth(["ADMIN"]),
  inventarioCtrl.listarInventario
);

router.get(
  "/locales-destino",
  auth(["ADMIN"]),
  inventarioCtrl.listarLocalesDestino
);

router.get(
  "/:id_producto/imeis-disponibles",
  auth(["ADMIN"]),
  inventarioCtrl.listarImeisDisponibles
);

// 🔹 Ajustar stock (entrada / salida)
router.put(
  "/:id_producto/ajustar",
  auth(["ADMIN"]),
  inventarioCtrl.ajustarStock
);

router.post("/:id_producto/imei", auth(["ADMIN"]), inventarioCtrl.ingresarPorIMEI);
router.post("/:id_producto/traspasar", auth(["ADMIN"]), inventarioCtrl.traspasarProducto);

module.exports = router;
