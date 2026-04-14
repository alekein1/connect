const express = require("express");
const router = express.Router();

const gastosCtrl = require("../controllers/gastos.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   💸 GASTOS
   Todo filtrado por el local del token
===================================================== */
router.get(
  "/resumen",
  auth(["ADMIN"]),
  gastosCtrl.resumenGastos
);

router.get(
  "/",
  auth(["ADMIN"]),
  gastosCtrl.listarGastos
);

router.get(
  "/:id",
  auth(["ADMIN"]),
  gastosCtrl.obtenerGasto
);

router.post(
  "/",
  auth(["ADMIN", "CAJA"]),
  gastosCtrl.crearGasto
);

router.put(
  "/:id",
  auth(["ADMIN"]),
  gastosCtrl.actualizarGasto
);

router.delete(
  "/:id",
  auth(["ADMIN"]),
  gastosCtrl.eliminarGasto
);

module.exports = router;
