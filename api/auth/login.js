const { getSupabase } = require('../_lib/db');
const { setSession } = require('../_lib/auth');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ error: 'Method tidak diizinkan.' });
  const { email, password } = req.body || {};
  if (!email || !password) return res.status(422).json({ error: 'Email dan password wajib diisi.' });

  try {
    const { data: user, error } = await getSupabase().from('users').select('id,name,email,password_hash,role').eq('email', String(email).trim().toLowerCase()).maybeSingle();
    if (error) throw error;
    if (!user || !(await bcrypt.compare(String(password), user.password_hash))) {
      return res.status(401).json({ error: 'Email atau password salah.' });
    }
    const safeUser = { id: user.id, name: user.name, email: user.email, role: user.role };
    setSession(res, safeUser);
    return res.status(200).json({ user: safeUser });
  } catch (error) {
    console.error('POST /api/auth/login failed', error);
    return res.status(500).json({ error: 'Login belum bisa diproses.' });
  }
};
