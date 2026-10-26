const { getSupabase } = require('./_lib/db');

module.exports = async function handler(req, res) {
  if (req.method !== 'GET') return res.status(405).json({ error: 'Method tidak diizinkan.' });

  try {
    const { data, error } = await getSupabase().from('listings').select('id,name,location,city,price,period,type,rating,reviews,image,tag,features,description').eq('is_active', true).order('id');
    if (error) throw error;
    const listings = (data || []).map((listing) => ({ ...listing, features: Array.isArray(listing.features) ? listing.features : [] }));
    return res.status(200).json(listings);
  } catch (error) {
    console.error('GET /api/listings failed', error);
    return res.status(500).json({ error: 'Data kos belum bisa diambil. Periksa koneksi Supabase.' });
  }
};
