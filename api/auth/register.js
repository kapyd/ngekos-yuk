const { getSupabase } = require('../_lib/db');
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
    const supabase = getSupabase();
    const { data: existing, error: existingError } = await supabase.from('users').select('id').eq('email', normalizedEmail).maybeSingle();
    if (existingError) throw existingError;
    if (existing) return res.status(409).json({ error: 'Email tersebut sudah terdaftar.' });
    const passwordHash = await bcrypt.hash(String(password), 12);
    const { data: safeUser, error } = await supabase.from('users').insert({ name: String(name).trim(), email: normalizedEmail, password_hash: passwordHash }).select('id,name,email,role').single();
    if (error) throw error;
    setSession(res, safeUser);
    return res.status(201).json({ user: safeUser });
  } catch (error) {
    console.error('POST /api/auth/register failed', error);
    return res.status(500).json({ error: 'Register belum bisa diproses.' });
  }
};
