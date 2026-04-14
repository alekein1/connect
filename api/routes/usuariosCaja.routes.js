const router = require("express").Router();
const ctrl = require("../controllers/usuariosCaja.controller");
const { auth } = require("../middlewares/auth.middleware");

/* SOLO ADMIN */
router.get("/", auth(["ADMIN"]), ctrl.listarUsuariosCaja);
router.post("/", auth(["ADMIN"]), ctrl.crearUsuarioCaja);
router.put("/:id", auth(["ADMIN"]), ctrl.actualizarUsuarioCaja);
router.put("/:id/password", auth(["ADMIN"]), ctrl.cambiarPasswordCaja);
router.delete("/:id", auth(["ADMIN"]), ctrl.desactivarUsuarioCaja);

module.exports = router;