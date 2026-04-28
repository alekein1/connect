const express = require("express");
const router = express.Router();

const ventasCtrl = require("../controllers/ventas.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   🧾 VENTAS (SOLO CAJA)
===================================================== */

router.get(
  "/",
  auth(["ADMIN"]),
  ventasCtrl.listarVentasAdmin
);

// ➕ Crear venta
router.post(
  "/",
  auth(["CAJA"]), // 🔥 SOLO CAJA
  ventasCtrl.crearVenta
);

router.get(
  "/buscar",
  auth(["CAJA"]),
  ventasCtrl.buscarProductoPOS
);

router.get(
  "/:id",
  auth(["ADMIN"]),
  ventasCtrl.obtenerDetalleVentaAdmin
);

router.post(
  "/:id/anular",
  auth(["ADMIN"]),
  ventasCtrl.anularVentaAdmin
);

router.post(
  "/:id/comprobante/email",
  auth(["CAJA", "ADMIN"]),
  ventasCtrl.enviarComprobantePdfPorCorreo
);

module.exports = router;
