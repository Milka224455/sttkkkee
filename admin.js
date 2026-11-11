// Admin-API: Nachrichten lesen, als gelesen markieren, löschen. Geschützt durch ADMIN_PASSWORD (Vercel-Umgebungsvariable).
const crypto = require('crypto');
const { configured, cmd, readBody, send } = require('./_kv');

function passOk(p) {
  const real = process.env.ADMIN_PASSWORD || '';
  if (real.length < 10) return false;
  const a = crypto.createHash('sha256').update(String(p || '')).digest();
  const b = crypto.createHash('sha256').update(real).digest();
  return crypto.timingSafeEqual(a, b);
}

module.exports = async (req, res) => {
  if (req.method !== 'POST') return send(res, 405, { ok: false, error: 'method' });
  if (!configured()) return send(res, 503, { ok: false, error: 'storage' });
  if (!(process.env.ADMIN_PASSWORD || '').length) return send(res, 503, { ok: false, error: 'nopass' });
  const b = readBody(req);
  if (!passOk(b.password)) {
    await new Promise(r => setTimeout(r, 1500));
    return send(res, 401, { ok: false, error: 'auth' });
  }
  try {
    const id = String(b.id || '');
    if (b.action === 'read' || b.action === 'unread') {
      const raw = await cmd('HGET', 'messages', id);
      if (raw) { const m = JSON.parse(raw); m.read = b.action === 'read'; await cmd('HSET', 'messages', id, JSON.stringify(m)); }
    } else if (b.action === 'delete') {
      await cmd('HDEL', 'messages', id);
    } else if (b.action === 'readall') {
      const all = await cmd('HGETALL', 'messages') || [];
      for (let i = 0; i < all.length; i += 2) {
        const m = JSON.parse(all[i + 1]);
        if (!m.read) { m.read = true; await cmd('HSET', 'messages', m.id, JSON.stringify(m)); }
      }
    }
    const all = await cmd('HGETALL', 'messages') || [];
    const list = [];
    for (let i = 0; i < all.length; i += 2) { try { list.push(JSON.parse(all[i + 1])); } catch (e) {} }
    list.sort((x, y) => (x.date < y.date ? 1 : -1));
    return send(res, 200, { ok: true, messages: list });
  } catch (e) {
    return send(res, 500, { ok: false, error: 'server' });
  }
};
