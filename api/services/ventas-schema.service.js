const db = require("../db/db");

let ensureDetalleVentaImeiColumnPromise = null;

async function ensureDetalleVentaImeiColumn() {
  if (!ensureDetalleVentaImeiColumnPromise) {
    ensureDetalleVentaImeiColumnPromise = (async () => {
      const [[column]] = await db.query(
        `
        SELECT COLUMN_NAME
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'detalle_venta'
          AND COLUMN_NAME = 'imei'
        LIMIT 1
        `
      );

      if (!column) {
        await db.query(
          `
          ALTER TABLE detalle_venta
          ADD COLUMN imei VARCHAR(20) NULL DEFAULT NULL AFTER cantidad
          `
        );
      }
    })().catch((error) => {
      ensureDetalleVentaImeiColumnPromise = null;
      throw error;
    });
  }

  return ensureDetalleVentaImeiColumnPromise;
}

module.exports = {
  ensureDetalleVentaImeiColumn
};
