const express = require("express");
const router = express.Router();

const contabilidadCtrl = require("../controllers/contabilidad.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   📊 CONTABILIDAD
===================================================== */
router.get(
  "/resumen",
  auth(["ADMIN"]),
  contabilidadCtrl.resumenContable
);

router.get(
  "/dashboard",
  auth(["ADMIN"]),
  contabilidadCtrl.dashboardContable
);

module.exports = router;
