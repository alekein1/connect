const express = require("express");
const router = express.Router();

const cajaCtrl = require("../controllers/caja.controller");
const { auth } = require("../middlewares/auth.middleware");

// 🔹 Caja POS
router.get("/verificar", auth(["ADMIN","CAJA"]), cajaCtrl.verificarCaja);
router.post("/abrir", auth(["ADMIN","CAJA"]), cajaCtrl.abrirCaja);
router.post("/cerrar", auth(["ADMIN","CAJA"]), cajaCtrl.cerrarCaja);

// 🔹 Admin
router.get("/", auth(["ADMIN"]), cajaCtrl.listarCajas);
router.get("/resumen", auth(["ADMIN"]), cajaCtrl.resumenCajasLocal);
router.get("/:id", auth(["ADMIN"]), cajaCtrl.detalleCaja);

module.exports = router;
