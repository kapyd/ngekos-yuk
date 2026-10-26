const { clearSession } = require('../_lib/auth');

module.exports = function handler(req, res) {
  clearSession(res);
  return res.status(200).json({ ok: true });
};
