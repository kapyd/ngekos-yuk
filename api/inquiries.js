const { getPool, sql } = require('./_lib/db');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method tidak diizinkan.' });
  const { name, phone, listing } = req.body || {};
  if (!name || !phone || !listing) return res.status(422).json({ error: 'Nama, nomor WhatsApp, dan kos wajib diisi.' });
  try {
    const pool = await getPool();
    await pool.request()
      .input('name', sql.NVarChar(120), String(name).trim())
      .input('phone', sql.NVarChar(40), String(phone).trim())
      .input('listing', sql.NVarChar(150), String(listing).trim())
      .query('INSERT INTO dbo.inquiries (name, phone, listing_name) VALUES (@name, @phone, @listing)');
    return res.status(201).json({ ok: true });
  } catch (error) {
    console.error('POST /api/inquiries failed', error);
    return res.status(500).json({ error: 'Inquiry belum bisa disimpan.' });
  }
};
