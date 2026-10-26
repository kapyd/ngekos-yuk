const { getPool, parseFeatures } = require('./_lib/db');

module.exports = async function handler(req, res) {
  if (req.method !== 'GET') return res.status(405).json({ error: 'Method tidak diizinkan.' });

  try {
    const pool = await getPool();
    const result = await pool.request().query(`
      SELECT id, name, location, city, price, period, type, rating, reviews,
             image_url AS image, tag, features_json, description
      FROM dbo.listings
      WHERE is_active = 1
      ORDER BY id
    `);
    const listings = result.recordset.map((listing) => ({
      ...listing,
      id: Number(listing.id),
      price: Number(listing.price),
      rating: Number(listing.rating),
      reviews: Number(listing.reviews),
      features: parseFeatures(listing.features_json),
    }));
    listings.forEach((listing) => delete listing.features_json);
    return res.status(200).json(listings);
  } catch (error) {
    console.error('GET /api/listings failed', error);
    return res.status(500).json({ error: 'Data kos belum bisa diambil. Periksa koneksi Azure SQL/SQL Server.' });
  }
};
