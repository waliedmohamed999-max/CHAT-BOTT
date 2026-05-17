import express from 'express';
import 'dotenv/config';
import fs from 'fs';
import path from 'path';
import pino from 'pino';
import QRCode from 'qrcode';
import makeWASocket, {
  DisconnectReason,
  fetchLatestBaileysVersion,
  useMultiFileAuthState,
  downloadMediaMessage
} from '@whiskeysockets/baileys';

const app = express();
app.use(express.json({limit: '25mb'}));

const port = Number(process.env.PORT || 3020);
const bridgeToken = process.env.BRIDGE_TOKEN || 'change-me-bridge-token';
const phpWebhookUrl = process.env.PHP_WEBHOOK_URL || '';
const authDir = process.env.AUTH_DIR || './auth';
const sessions = new Map();

function assertToken(req, res, next) {
  if (req.headers['x-bridge-token'] !== bridgeToken) {
    res.status(403).json({error: 'forbidden'});
    return;
  }
  next();
}

app.use(assertToken);

function sessionPath(sessionId) {
  return path.join(authDir, sessionId.replace(/[^a-z0-9_-]/gi, '_'));
}

async function emitToPhp(payload) {
  if (!phpWebhookUrl) return;
  try {
    await fetch(phpWebhookUrl, {
      method: 'POST',
      headers: {'Content-Type': 'application/json', 'X-Bridge-Token': bridgeToken},
      body: JSON.stringify(payload)
    });
  } catch (error) {
    console.error('PHP webhook failed', error.message);
  }
}

function serializeMessage(message, direction = 'inbound') {
  const content = message.message || {};
  const text =
    content.conversation ||
    content.extendedTextMessage?.text ||
    content.imageMessage?.caption ||
    content.videoMessage?.caption ||
    '';
  return {
    chatId: message.key.remoteJid,
    messageId: message.key.id,
    from: message.key.participant || message.key.remoteJid,
    to: message.key.remoteJid,
    body: text,
    type: content.imageMessage ? 'image' : content.videoMessage ? 'video' : content.audioMessage ? 'audio' : content.documentMessage ? 'document' : 'text',
    direction,
    status: direction === 'outbound' ? 'sent' : 'received',
    timestamp: Number(message.messageTimestamp || Math.floor(Date.now() / 1000)),
    rawPayload: message
  };
}

async function createSession(sessionId, meta = {}) {
  if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, {recursive: true});
  const {state, saveCreds} = await useMultiFileAuthState(sessionPath(sessionId));
  const {version} = await fetchLatestBaileysVersion();
  const session = sessions.get(sessionId) || {id: sessionId, status: 'authenticating', qr: null, sock: null, chats: new Map(), messages: new Map(), meta};

  const sock = makeWASocket({
    version,
    auth: state,
    logger: pino({level: 'silent'}),
    printQRInTerminal: false,
    browser: ['Marketing Center', 'Chrome', '1.0.0']
  });

  session.sock = sock;
  session.status = 'authenticating';
  sessions.set(sessionId, session);

  sock.ev.on('creds.update', saveCreds);
  sock.ev.on('connection.update', async (update) => {
    if (update.qr) {
      session.qr = await QRCode.toDataURL(update.qr);
      session.status = 'waiting_for_scan';
      await emitToPhp({type: 'status', storeId: meta.storeId, status: session.status, qr: session.qr});
    }
    if (update.connection === 'open') {
      const user = sock.user || {};
      session.status = 'connected';
      session.qr = null;
      session.phoneNumber = String(user.id || '').split(':')[0].replace('@s.whatsapp.net', '');
      session.displayName = user.name || user.verifiedName || 'WhatsApp';
      await emitToPhp({type: 'status', storeId: meta.storeId, status: 'connected', phoneNumber: session.phoneNumber, displayName: session.displayName});
    }
    if (update.connection === 'close') {
      const shouldReconnect = update.lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
      session.status = shouldReconnect ? 'disconnected' : 'expired';
      await emitToPhp({type: 'status', storeId: meta.storeId, status: session.status});
      if (shouldReconnect) setTimeout(() => createSession(sessionId, meta), 3000);
    }
  });

  sock.ev.on('messages.upsert', async ({messages}) => {
    for (const msg of messages) {
      if (!msg.message || msg.key.fromMe) continue;
      const serialized = serializeMessage(msg, 'inbound');
      session.messages.set(serialized.messageId, serialized);
      session.chats.set(serialized.chatId, {
        chatId: serialized.chatId,
        name: serialized.chatId,
        isGroup: serialized.chatId.endsWith('@g.us'),
        unreadCount: 1,
        lastMessage: serialized.body,
        lastMessageAt: serialized.timestamp
      });
      await emitToPhp({type: 'message', storeId: meta.storeId, message: serialized, chat: session.chats.get(serialized.chatId)});
    }
  });

  return session;
}

app.post('/sessions/:id/create', async (req, res) => {
  const session = await createSession(req.params.id, req.body || {});
  res.json({status: session.status, qr: session.qr});
});

app.get('/sessions/:id/status', (req, res) => {
  const session = sessions.get(req.params.id);
  res.json({
    status: session?.status || 'disconnected',
    qr: session?.qr || null,
    phoneNumber: session?.phoneNumber || null,
    displayName: session?.displayName || null,
    avatarUrl: session?.avatarUrl || null
  });
});

app.post('/sessions/:id/reconnect', async (req, res) => {
  const current = sessions.get(req.params.id);
  const session = await createSession(req.params.id, current?.meta || {});
  res.json({status: session.status, qr: session.qr});
});

app.post('/sessions/:id/disconnect', async (req, res) => {
  const session = sessions.get(req.params.id);
  if (session?.sock) await session.sock.logout().catch(() => {});
  sessions.delete(req.params.id);
  fs.rmSync(sessionPath(req.params.id), {recursive: true, force: true});
  res.json({status: 'disconnected'});
});

app.get('/sessions/:id/chats', (req, res) => {
  const session = sessions.get(req.params.id);
  res.json({data: [...(session?.chats?.values() || [])]});
});

app.get('/sessions/:id/chats/:chatId/messages', (req, res) => {
  const session = sessions.get(req.params.id);
  const chatId = decodeURIComponent(req.params.chatId);
  const data = [...(session?.messages?.values() || [])].filter((message) => message.chatId === chatId);
  res.json({data});
});

app.post('/sessions/:id/send-message', async (req, res) => {
  const session = sessions.get(req.params.id);
  if (!session?.sock || session.status !== 'connected') return res.status(422).json({error: 'not_connected'});
  const jid = req.body.to.includes('@') ? req.body.to : `${req.body.to}@s.whatsapp.net`;
  const sent = await session.sock.sendMessage(jid, {text: req.body.body || ''});
  const serialized = serializeMessage(sent, 'outbound');
  session.messages.set(serialized.messageId, serialized);
  res.json({status: 'sent', chatId: jid, messageId: serialized.messageId});
});

app.post('/sessions/:id/send-media', async (req, res) => {
  const session = sessions.get(req.params.id);
  if (!session?.sock || session.status !== 'connected') return res.status(422).json({error: 'not_connected'});
  const jid = req.body.to.includes('@') ? req.body.to : `${req.body.to}@s.whatsapp.net`;
  const media = {url: req.body.media_url};
  const payload = req.body.type === 'image' ? {image: media, caption: req.body.caption || ''} : {document: media, fileName: req.body.file_name || 'file'};
  const sent = await session.sock.sendMessage(jid, payload);
  const serialized = serializeMessage(sent, 'outbound');
  session.messages.set(serialized.messageId, serialized);
  res.json({status: 'sent', chatId: jid, messageId: serialized.messageId});
});

app.get('/sessions/:id/contacts', async (req, res) => {
  const session = sessions.get(req.params.id);
  res.json({data: session?.sock?.user ? [session.sock.user] : []});
});

app.listen(port, () => {
  console.log(`Marketing Center WhatsApp QR bridge listening on ${port}`);
});
