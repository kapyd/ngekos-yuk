const sql = require('mssql');

let poolPromise;

function getConfig() {
  const required = ['SQLSERVER_HOST', 'SQLSERVER_USER', 'SQLSERVER_PASSWORD', 'SQLSERVER_DATABASE'];
  const missing = required.filter((key) => !process.env[key]);
  if (missing.length) {
    throw new Error(`Environment variable belum lengkap: ${missing.join(', ')}`);
  }

  return {
    server: process.env.SQLSERVER_HOST,
    user: process.env.SQLSERVER_USER,
    password: process.env.SQLSERVER_PASSWORD,
    database: process.env.SQLSERVER_DATABASE,
    port: Number(process.env.SQLSERVER_PORT || 1433),
    options: {
      encrypt: process.env.SQLSERVER_ENCRYPT !== 'false',
      trustServerCertificate: process.env.SQLSERVER_TRUST_CERT === 'true',
    },
    pool: { max: 5, min: 0, idleTimeoutMillis: 30000 },
  };
}

function getPool() {
  if (!poolPromise) {
    poolPromise = sql.connect(getConfig()).catch((error) => {
      poolPromise = undefined;
      throw error;
    });
  }
  return poolPromise;
}

function parseFeatures(value) {
  try {
    return JSON.parse(value || '[]');
  } catch {
    return [];
  }
}

module.exports = { sql, getPool, parseFeatures };
