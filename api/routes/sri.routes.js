const express = require("express");
const router = express.Router();

const sriCtrl = require("../controllers/sri.controller");
const { auth } = require("../middlewares/auth.middleware");
const uploadSriCertificado = require("../middlewares/uploadSriCertificado");

function uploadCertificadoJson(req, res, next) {
  uploadSriCertificado.single("certificado")(req, res, (error) => {
    if (!error) return next();

    return res.status(400).json({
      ok: false,
      mensaje: error.message || "Error al procesar el archivo del certificado"
    });
  });
}

router.get(
  "/",
  auth(["ADMIN"]),
  sriCtrl.infoSri
);

router.get(
  "/configuracion",
  auth(["ADMIN"]),
  sriCtrl.obtenerConfiguracionSri
);

router.get(
  "/pruebas",
  sriCtrl.infoSri
);

router.get(
  "/pruebas/locales",
  sriCtrl.listarLocalesPruebas
);

router.get(
  "/pruebas/configuracion",
  sriCtrl.obtenerConfiguracionSriPruebas
);

router.post(
  "/certificado/probar",
  auth(["ADMIN"]),
  uploadCertificadoJson,
  sriCtrl.probarCertificado
);

router.put(
  "/configuracion",
  auth(["ADMIN"]),
  uploadCertificadoJson,
  sriCtrl.guardarConfiguracionSri
);

router.post(
  "/facturas/:id_venta/xml",
  auth(["CAJA", "ADMIN"]),
  sriCtrl.generarXmlFactura
);

router.post(
  "/facturas/:id_venta/firmar",
  auth(["CAJA", "ADMIN"]),
  sriCtrl.firmarXmlFactura
);

router.post(
  "/facturas/:id_venta/enviar",
  auth(["CAJA", "ADMIN"]),
  sriCtrl.enviarFacturaSri
);

router.post(
  "/facturas/:id_venta/autorizar",
  auth(["CAJA", "ADMIN"]),
  sriCtrl.autorizarFacturaSri
);

router.post(
  "/facturas/:id_venta/ride",
  auth(["CAJA", "ADMIN"]),
  sriCtrl.generarRideFacturaSri
);

router.post(
  "/facturas/:id_venta/email",
  auth(["CAJA", "ADMIN"]),
  express.json(),
  sriCtrl.enviarFacturaSriCorreo
);

router.post(
  "/pruebas/certificado/probar",
  uploadCertificadoJson,
  sriCtrl.probarCertificado
);

router.post(
  "/pruebas/configuracion",
  uploadCertificadoJson,
  sriCtrl.guardarConfiguracionSriPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/xml",
  sriCtrl.generarXmlFacturaPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/firmar",
  sriCtrl.firmarXmlFacturaPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/enviar",
  sriCtrl.enviarFacturaSriPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/autorizar",
  sriCtrl.autorizarFacturaSriPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/ride",
  sriCtrl.generarRideFacturaSriPruebas
);

router.post(
  "/pruebas/facturas/:id_venta/email",
  express.json(),
  sriCtrl.enviarFacturaSriCorreoPruebas
);

module.exports = router;
