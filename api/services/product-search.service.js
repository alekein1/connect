function buildCompactProductSearchExpression(alias = "p") {
  return `UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(CONCAT(COALESCE(${alias}.nombre_producto, ''), COALESCE(${alias}.marca, ''), COALESCE(${alias}.color, ''), COALESCE(${alias}.capacidad, ''), COALESCE(${alias}.estado, ''), COALESCE(${alias}.codigo_barras, ''), COALESCE(${alias}.sku, '')), ' ', ''), '/', ''), '-', ''), '_', ''), '.', ''), ',', ''), '|', ''))`;
}

function buildProductSearchClause(alias = "p", rawQuery = "") {
  const trimmedQuery = String(rawQuery || "").trim();
  const terms = trimmedQuery
    .toUpperCase()
    .split(/[^A-Z0-9]+/)
    .filter(Boolean);

  if (!terms.length) {
    return null;
  }

  const expression = buildCompactProductSearchExpression(alias);
  const likeTerm = `%${trimmedQuery}%`;

  return {
    sql: `(
      COALESCE(${alias}.nombre_producto, '') LIKE ?
      OR COALESCE(${alias}.marca, '') LIKE ?
      OR COALESCE(${alias}.color, '') LIKE ?
      OR COALESCE(${alias}.capacidad, '') LIKE ?
      OR COALESCE(${alias}.estado, '') LIKE ?
      OR COALESCE(${alias}.codigo_barras, '') LIKE ?
      OR COALESCE(${alias}.sku, '') LIKE ?
      OR (${terms.map(() => `${expression} LIKE ?`).join(" AND ")})
    )`,
    params: [
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      likeTerm,
      ...terms.map((term) => `%${term}%`)
    ]
  };
}

module.exports = {
  buildProductSearchClause
};
