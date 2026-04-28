const db = require("../db/db");

let ensureDetalleVentaImeiColumnPromise = null;
let ensureVentaAnulacionSchemaPromise = null;

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

function parseEnumColumnValues(columnType = "") {
  const matches = String(columnType).match(/'([^']*)'/g) || [];
  return matches.map((item) => item.slice(1, -1));
}

function buildDefaultClause(columnDefault) {
  if (columnDefault === null || columnDefault === undefined) {
    return "";
  }

  const rawDefault = String(columnDefault);

  if (/^current_timestamp(?:\(\))?$/i.test(rawDefault)) {
    return ` DEFAULT ${rawDefault}`;
  }

  return ` DEFAULT '${rawDefault.replace(/'/g, "''")}'`;
}

async function ensureVentaAnulacionSchema() {
  if (!ensureVentaAnulacionSchemaPromise) {
    ensureVentaAnulacionSchemaPromise = (async () => {
      const [columns] = await db.query(
        `
        SELECT
          COLUMN_NAME,
          COLUMN_TYPE,
          IS_NULLABLE,
          COLUMN_DEFAULT
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'ventas'
          AND COLUMN_NAME IN (
            'estado',
            'motivo_anulacion',
            'fecha_anulacion',
            'id_usuario_anulacion'
          )
        `
      );

      const columnMap = columns.reduce((acc, column) => {
        acc[column.COLUMN_NAME] = column;
        return acc;
      }, {});

      const estadoColumn = columnMap.estado;

      if (!estadoColumn) {
        throw new Error("La tabla ventas no tiene la columna estado");
      }

      const columnType = String(estadoColumn.COLUMN_TYPE || "").toLowerCase();

      if (columnType.startsWith("enum(")) {
        const enumValues = parseEnumColumnValues(estadoColumn.COLUMN_TYPE);

        if (!enumValues.includes("ANULADA")) {
          const updatedValues = [...enumValues, "ANULADA"];
          const nullableSql = estadoColumn.IS_NULLABLE === "YES" ? "NULL" : "NOT NULL";
          const defaultSql = buildDefaultClause(estadoColumn.COLUMN_DEFAULT);
          const enumSql = updatedValues
            .map((value) => `'${String(value).replace(/'/g, "''")}'`)
            .join(", ");

          await db.query(
            `
            ALTER TABLE ventas
            MODIFY COLUMN estado ENUM(${enumSql}) ${nullableSql}${defaultSql}
            `
          );
        }
      }

      if (!columnMap.motivo_anulacion) {
        await db.query(
          `
          ALTER TABLE ventas
          ADD COLUMN motivo_anulacion VARCHAR(255) NULL DEFAULT NULL AFTER estado
          `
        );
      }

      if (!columnMap.fecha_anulacion) {
        await db.query(
          `
          ALTER TABLE ventas
          ADD COLUMN fecha_anulacion DATETIME NULL DEFAULT NULL AFTER motivo_anulacion
          `
        );
      }

      if (!columnMap.id_usuario_anulacion) {
        await db.query(
          `
          ALTER TABLE ventas
          ADD COLUMN id_usuario_anulacion INT(11) NULL DEFAULT NULL AFTER fecha_anulacion
          `
        );
      }
    })().catch((error) => {
      ensureVentaAnulacionSchemaPromise = null;
      throw error;
    });
  }

  return ensureVentaAnulacionSchemaPromise;
}

module.exports = {
  ensureDetalleVentaImeiColumn,
  ensureVentaAnulacionSchema
};
