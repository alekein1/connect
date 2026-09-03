function buildCompactSearchExpression(valueSql) {
  return `UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(${valueSql}, ' ', ''), '/', ''), '-', ''), '_', ''), '.', ''), ',', ''), '|', ''))`;
}

function buildCompactProductSearchExpression(alias = "p") {
  return buildCompactSearchExpression(
    `CONCAT(COALESCE(${alias}.nombre_producto, ''), COALESCE(${alias}.marca, ''), COALESCE(${alias}.color, ''), COALESCE(${alias}.capacidad, ''), COALESCE(${alias}.estado, ''), COALESCE(${alias}.codigo_barras, ''), COALESCE(${alias}.sku, ''))`
  );
}

function buildLooseTextProductSearchExpression(alias = "p") {
  return `REPLACE(${buildCompactSearchExpression(
    `CONCAT(COALESCE(${alias}.nombre_producto, ''), COALESCE(${alias}.marca, ''), COALESCE(${alias}.color, ''), COALESCE(${alias}.capacidad, ''), COALESCE(${alias}.estado, ''))`
  )}, '0', 'O')`;
}

function toCompactSearchToken(value = "") {
  return String(value || "")
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, "");
}

function toLooseTextToken(value = "") {
  return toCompactSearchToken(value).replace(/0/g, "O");
}

function normalizeProductSearchQuery(rawQuery = "") {
  const sanitized = String(rawQuery || "")
    .normalize("NFKC")
    .replace(/[\u0000-\u001F\u007F]+/g, "")
    .trim();

  if (!sanitized) {
    return "";
  }

  return sanitized.replace(/^\][A-Za-z0-9]{2}/, "").trim();
}

function buildProductSearchClause(alias = "p", rawQuery = "") {
  const trimmedQuery = normalizeProductSearchQuery(rawQuery);
  const terms = trimmedQuery
    .toUpperCase()
    .split(/[^A-Z0-9]+/)
    .filter(Boolean);

  if (!terms.length) {
    return null;
  }

  const expression = buildCompactProductSearchExpression(alias);
  const looseTextExpression = buildLooseTextProductSearchExpression(alias);
  const compactQuery = toCompactSearchToken(trimmedQuery);
  const looseTextQuery = toLooseTextToken(trimmedQuery);
  const likeTerm = `%${trimmedQuery}%`;
  const compactTerm = compactQuery ? `%${compactQuery}%` : null;
  const looseTextTerm = looseTextQuery ? `%${looseTextQuery}%` : null;
  const sqlParts = [
    `COALESCE(${alias}.nombre_producto, '') LIKE ?`,
    `COALESCE(${alias}.marca, '') LIKE ?`,
    `COALESCE(${alias}.color, '') LIKE ?`,
    `COALESCE(${alias}.capacidad, '') LIKE ?`,
    `COALESCE(${alias}.estado, '') LIKE ?`,
    `COALESCE(${alias}.codigo_barras, '') LIKE ?`,
    `COALESCE(${alias}.sku, '') LIKE ?`,
    `(${terms.map(() => `${expression} LIKE ?`).join(" AND ")})`
  ];

  const params = [
    likeTerm,
    likeTerm,
    likeTerm,
    likeTerm,
    likeTerm,
    likeTerm,
    likeTerm,
    ...terms.map((term) => `%${term}%`)
  ];

  if (compactTerm) {
    sqlParts.push(`${expression} LIKE ?`);
    params.push(compactTerm);
  }

  if (looseTextTerm) {
    sqlParts.push(`${looseTextExpression} LIKE ?`);
    params.push(looseTextTerm);
  }

  return {
    sql: `(${sqlParts.join("\n      OR ")})`,
    params
  };
}

module.exports = {
  buildProductSearchClause,
  normalizeProductSearchQuery
};
