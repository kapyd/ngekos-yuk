const { getPool, sql } = require('../_lib/db');
const { setSession } = require('../_lib/auth');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method tidak diizinkan.' });
  const { name, email, password, passwordConfirmation } = req.body || {};
  const normalizedEmail = String(email || '').trim().toLowerCase();
  if (!name || !normalizedEmail || !password) return res.status(422).json({ error: 'Nama, email, dan password wajib diisi.' });
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedEmail)) return res.status(422).json({ error: 'Email tidak valid.' });
  if (String(password).length < 8) return res.status(422).json({ error: 'Password minimal 8 karakter.' });
  if (password !== passwordConfirmation) return res.status(422).json({ error: 'Konfirmasi password tidak sama.' });

  try {
    const pool = await getPool();
    const existing = await pool.request().input('email', sql.NVarChar(180), normalizedEmail).query('SELECT TOP 1 id FROM dbo.users WHERE email = @email');
    if (existing.recordset.length) return res.status(409).json({ error: 'Email tersebut sudah terdaftar.' });
    const passwordHash = await bcrypt.hash(String(password), 12);
    const user = await pool.request()
      .input('name', sql.NVarChar(120), String(name).trim())
      .input('email', sql.NVarChar(180), normalizedEmail)
      .input('passwordHash', sql.NVarChar(255), passwordHash)
      .query('INSERT INTO dbo.users (name, email, password_hash) OUTPUT INSERTED.id, INSERTED.name, INSERTED.email, INSERTED.role VALUES (@name, @email, @passwordHash)');
    const safeUser = user.recordset[0];
    setSession(res, safeUser);
    return res.status(201).json({ user: safeUser });
  } catch (error) {
    console.error('POST /api/auth/register failed', error);
    return res.status(500).json({ error: 'Register belum bisa diproses.' });
  }
};
