const express = require("express");
const router = express.Router();

const ventasCtrl = require("../controllers/ventas.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   🧾 VENTAS (SOLO CAJA)
===================================================== */

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

router.post(
  "/:id/comprobante/email",
  auth(["CAJA", "ADMIN"]),
  ventasCtrl.enviarComprobantePdfPorCorreo
);

module.exports = router;
