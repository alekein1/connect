const express = require("express");
const router = express.Router();

const ctrl = require("../controllers/locales.controller");
const { auth } = require("../middlewares/auth.middleware");

/* =====================================================
   🏪 CRUD LOCALES (SOLO SUPER ADMIN)
===================================================== */

router.get("/", auth(["SUPER_ADMIN"]), ctrl.listarLocales);

router.get("/:id", auth(["SUPER_ADMIN"]), ctrl.obtenerLocal);

router.post("/", auth(["SUPER_ADMIN"]), ctrl.crearLocal);

router.put("/:id", auth(["SUPER_ADMIN"]), ctrl.actualizarLocal);

router.delete("/:id", auth(["SUPER_ADMIN"]), ctrl.eliminarLocal);

module.exports = router;