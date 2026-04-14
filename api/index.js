require("dotenv").config();

const express = require("express");
const cors = require("cors");


const app = express();
const path = require("path");

app.use("/api/uploads", express.static(path.join(__dirname, "uploads")));
const PORT = process.env.PORT || 4004;
app.use(cors());
app.use(express.json());

const authRoutes = require("./routes/auth.routes");
const localesRoutes = require("./routes/locales.routes");
const adminRoutes = require("./routes/admin.routes");
const usariosCajaRoutes = require("./routes/usuariosCaja.routes");
const categoriasRoutes = require("./routes/categorias.routes");
const subcategoriasRoutes = require("./routes/subcategorias.routes");
const productosRoutes = require("./routes/producto.routes");
const intentarioRoutes = require("./routes/inventario.routes");
const cajaRoutes = require("./routes/caja.routes");
const ventasRoutes = require("./routes/ventas.routes");
const contabilidadRoutes = require("./routes/contabilidad.routes");
const gastosRoutes = require("./routes/gastos.routes");
const sriRoutes = require("./routes/sri.routes");
const reportesRoutes = require("./routes/reportes.routes");





app.get("/", (req, res) => {
  res.json({
    ok: true,
    sistema: "CONNECT-CORE",
    mensaje: "API funcionando correctamente 🚀"
  });
});

app.use("/api/auth", authRoutes);
app.use("/api/locales", localesRoutes);
app.use("/api/admins", adminRoutes);
app.use("/api/usuarios", usariosCajaRoutes);
app.use("/api/categorias", categoriasRoutes);
app.use("/api/subcategorias", subcategoriasRoutes);
app.use("/api/productos", productosRoutes);
app.use("/api/inventario", intentarioRoutes);
app.use("/api/caja", cajaRoutes);
app.use("/api/ventas", ventasRoutes)
app.use("/api/contabilidad", contabilidadRoutes);
app.use("/api/gastos", gastosRoutes);
app.use("/api/sri", sriRoutes);
app.use("/api/reportes", reportesRoutes);

const server = app.listen(PORT, () => {
    console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
});

server.on("error", (error) => {
  if (error.code === "EADDRINUSE") {
    console.error(`❌ El puerto ${PORT} ya está en uso. Cierra el proceso anterior antes de volver a iniciar.`);
    return;
  }

  console.error("❌ No se pudo iniciar el servidor:", error);
});

function shutdown(signal) {
  console.log(`\n🛑 Recibido ${signal}. Cerrando servidor...`);

  server.close(() => {
    console.log("✅ Servidor detenido correctamente");
    process.exit(0);
  });
}

process.on("SIGINT", () => shutdown("SIGINT"));
process.on("SIGTERM", () => shutdown("SIGTERM"));
