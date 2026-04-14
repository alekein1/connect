const express = require("express");
const router = express.Router();

const reportesCtrl = require("../controllers/reportes.controller");
const { auth } = require("../middlewares/auth.middleware");

router.get(
  "/superadmin/resumen",
  auth(["SUPER_ADMIN"]),
  reportesCtrl.reporteGeneralSuperadmin
);

router.get(
  "/superadmin/rides",
  auth(["SUPER_ADMIN"]),
  reportesCtrl.reporteRidesSuperadmin
);

router.get(
  "/superadmin/stock/pdf",
  auth(["SUPER_ADMIN"]),
  reportesCtrl.reporteStockPdfSuperadmin
);

router.get(
  "/admin/stock/pdf",
  auth(["ADMIN"]),
  reportesCtrl.reporteStockPdfAdmin
);

module.exports = router;
