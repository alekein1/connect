const express = require("express");
const router = express.Router();
const authCtrl = require("../controllers/auth.controller");

// 👑 solo usar en postman
router.post("/superadmin", authCtrl.crearSuperAdmin);

// 🔐 login
router.post("/login", authCtrl.login);

module.exports = router;