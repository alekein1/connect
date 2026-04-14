const jwt = require("jsonwebtoken");
const jwtConfig = require("../config/jwt");

/* =====================================================
   🔐 VERIFY TOKEN + CHECK ROLE (PRO)
===================================================== */
exports.auth = (rolesPermitidos = []) => {
  return (req, res, next) => {
    try {
      const authHeader = req.headers.authorization;

      if (!authHeader) {
        return res.status(401).json({
          ok: false,
          mensaje: "Token requerido"
        });
      }

      // 🔍 validar formato Bearer
      if (!authHeader.startsWith("Bearer ")) {
        return res.status(401).json({
          ok: false,
          mensaje: "Formato de token inválido"
        });
      }

      const token = authHeader.split(" ")[1];

      // 🔐 verificar token
      const decoded = jwt.verify(token, jwtConfig.secret);

      req.user = decoded;

      // 🔥 si no hay roles → solo autenticación
      if (!rolesPermitidos || rolesPermitidos.length === 0) {
        return next();
      }

      const userRoles = Array.isArray(decoded.roles)
        ? decoded.roles
        : [];

      const permitido = rolesPermitidos.some(role =>
        userRoles.includes(role)
      );

      if (!permitido) {
        return res.status(403).json({
          ok: false,
          mensaje: "No tienes permisos para esta acción"
        });
      }

      next();

    } catch (error) {

      if (error.name === "TokenExpiredError") {
        return res.status(401).json({
          ok: false,
          mensaje: "Token expirado"
        });
      }

      return res.status(401).json({
        ok: false,
        mensaje: "Token inválido"
      });
    }
  };
};