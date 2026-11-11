// Empfängt das Kontaktformular und speichert die Nachricht für den Admin-Bereich.
const crypto = require('crypto');
const { configured, cmd, readBody, send } = require('./_kv');

module.exports = async (req, res) => {
  if (req.method !== 'POST') return send(res, 405, { ok: false, error: 'method' });
  if (!configured()) return send(res, 503, { ok: false, error: 'storage' });
  const b = readBody(req);

  // Spam-Schutz: Honeypot leer, Formular mind. 2,5 s offen
  if (b.website) return send(res, 200, { ok: true });
  const ts = Number(b.ts) || 0;
  if (ts <= 0 || Date.now() - ts < 2500) return send(res, 400, { ok: false, error: 'too_fast' });

  const s = (v, n) => String(v || '').trim().slice(0, n);
  const name = s(b.name, 100), email = s(b.email, 160), message = s(b.message, 5000);
  const topic = b.topic === 'affiliate' ? 'affiliate' : 'contact';
  const lang = b.lang === 'fr' ? 'fr' : 'de';
  if (!name || !message || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return send(res, 400, { ok: false, error: 'fields' });
  if (!b.consent) return send(res, 400, { ok: false, error: 'consent' });

  // Rate-Limit: max. 5 Nachrichten pro IP und Tag (IP nur als Hash)
  const ip = String(req.headers['x-forwarded-for'] || '').split(',')[0].trim() || 'unknown';
  const day = new Date().toISOString().slice(0, 10);
  const key = 'rl:' + day + ':' + crypto.createHash('sha256').update(ip + '|' + day).digest('hex').slice(0, 32);
  try {
    const n = await cmd('INCR', key);
    if (n === 1) await cmd('EXPIRE', key, 86400);
    if (n > 5) return send(res, 429, { ok: false, error: 'limit' });

    const msg = { id: crypto.randomBytes(8).toString('hex'), date: new Date().toISOString(), name, email, topic, lang, message, read: false };
    await cmd('HSET', 'messages', msg.id, JSON.stringify(msg));
    return send(res, 200, { ok: true });
  } catch (e) {
    return send(res, 500, { ok: false, error: 'server' });
  }
};
