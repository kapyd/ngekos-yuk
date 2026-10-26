const { readSession } = require('../_lib/auth');

module.exports = function handler(req, res) {
  const user = readSession(req);
  return res.status(200).json({ user });
};
