const { getSupabase } = require('./_lib/db');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method tidak diizinkan.' });
  const { name, phone, listing } = req.body || {};
  if (!name || !phone || !listing) return res.status(422).json({ error: 'Nama, nomor WhatsApp, dan kos wajib diisi.' });
  try {
    const { error } = await getSupabase().from('inquiries').insert({ name: String(name).trim(), phone: String(phone).trim(), listing_name: String(listing).trim() });
    if (error) throw error;
    return res.status(201).json({ ok: true });
  } catch (error) {
    console.error('POST /api/inquiries failed', error);
    return res.status(500).json({ error: 'Inquiry belum bisa disimpan.' });
  }
};
