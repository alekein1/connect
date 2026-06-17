const express = require("express");
const router = express.Router();

const notasCreditoCtrl = require("../controllers/notasCredito.controller");
const { auth } = require("../middlewares/auth.middleware");

router.get(
  "/superadmin/facturas",
  auth(["SUPER_ADMIN"]),
  notasCreditoCtrl.listarFacturasSuperadmin
);

router.post(
  "/superadmin/ventas/:id_venta/emitir",
  auth(["SUPER_ADMIN"]),
  notasCreditoCtrl.emitirNotaCreditoSuperadmin
);

router.post(
  "/superadmin/:id_nota_credito/consultar",
  auth(["SUPER_ADMIN"]),
  notasCreditoCtrl.consultarNotaCreditoSuperadmin
);

module.exports = router;
