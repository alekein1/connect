const express = require("express");
const router = express.Router();

const adminCtrl = require("../controllers/admin.controller");
const { auth } = require("../middlewares/auth.middleware");

router.get("/dashboard", auth(["ADMIN"]), adminCtrl.dashboardAdmin);

/* SOLO SUPER ADMIN */
router.get("/", auth(["SUPER_ADMIN"]), adminCtrl.listarAdmins);
router.post("/", auth(["SUPER_ADMIN"]), adminCtrl.crearAdmin);
router.put("/:id", auth(["SUPER_ADMIN"]), adminCtrl.actualizarAdmin);
router.put("/:id/password", auth(["SUPER_ADMIN"]), adminCtrl.cambiarPassword);
router.delete("/:id", auth(["SUPER_ADMIN"]), adminCtrl.eliminarAdmin);

module.exports = router;
