import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
} from '@whiskeysockets/baileys';
import pino from 'pino';
import qrcode from 'qrcode';
import fs from 'fs';
import path from 'path';

/**
 * In-memory message store for handling retry receipts from WhatsApp peer devices.
 * Limits memory consumption with bounded FIFO size and TTL expiration.
 */
export class MessageStore {
  constructor(maxSize = 1000, ttlMs = 24 * 60 * 60 * 1000) {
    this.messages = new Map();
    this.maxSize = maxSize;
    this.ttlMs = ttlMs;

    this.cleanupInterval = setInterval(() => this.cleanup(), 15 * 60 * 1000);
    if (this.cleanupInterval.unref) {
      this.cleanupInterval.unref();
    }
  }

  saveMessage(id, message) {
    if (!id || !message) return;
    if (this.messages.size >= this.maxSize) {
      const oldestKey = this.messages.keys().next().value;
      if (oldestKey) this.messages.delete(oldestKey);
    }
    this.messages.set(id, {
      message,
      timestamp: Date.now(),
    });
  }

  getMessage(id) {
    const entry = this.messages.get(id);
    if (!entry) return null;
    if (Date.now() - entry.timestamp > this.ttlMs) {
      this.messages.delete(id);
      return null;
    }
    return entry.message;
  }

  cleanup() {
    const now = Date.now();
    for (const [id, entry] of this.messages.entries()) {
      if (now - entry.timestamp > this.ttlMs) {
        this.messages.delete(id);
      }
    }
  }

  clear() {
    this.messages.clear();
  }
}

class BaileysGateway {
  constructor() {
    this.sock = null;
    this.status = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
    this.qrCodeData = null; // Base64 data URL of QR code
    this.rawQr = null;
    this.connectedUser = null; // { id, name, phone }
    this.sessionDir = process.env.SESSION_DIR || './session';
    this.reconnectTimeout = null;
    this.isReconnecting = false;
    this.logger = pino({ level: 'warn' });
    this.messageStore = new MessageStore();
  }

  async init() {
    if (this.sock && (this.status === 'connected' || this.status === 'connecting')) {
      return;
    }

    this.status = 'connecting';
    this.qrCodeData = null;
    this.rawQr = null;

    try {
      if (!fs.existsSync(this.sessionDir)) {
        fs.mkdirSync(this.sessionDir, { recursive: true });
      }

      const { state, saveCreds } = await useMultiFileAuthState(this.sessionDir);
      const { version } = await fetchLatestBaileysVersion();

      this.sock = makeWASocket({
        version,
        logger: this.logger,
        printQRInTerminal: false,
        auth: {
          creds: state.creds,
          keys: makeCacheableSignalKeyStore(state.keys, this.logger),
        },
        generateHighQualityLinkPreview: true,
        browser: ['SIMONGAN PKL', 'Chrome', '1.0.0'],
        getMessage: async (key) => {
          if (key && key.id) {
            const stored = this.messageStore.getMessage(key.id);
            if (stored) {
              return stored;
            }
          }
          return undefined;
        },
      });

      this.sock.ev.on('creds.update', saveCreds);

      this.sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
          this.rawQr = qr;
          try {
            this.qrCodeData = await qrcode.toDataURL(qr, { width: 320, margin: 2 });
            this.status = 'qr_ready';
            console.log('[Baileys] QR code baru telah di-generate. Siap dipindai.');
          } catch (err) {
            console.error('[Baileys] Gagal membuat QR data URL:', err);
          }
        }

        if (connection === 'connecting') {
          this.status = this.qrCodeData ? 'qr_ready' : 'connecting';
        } else if (connection === 'open') {
          this.status = 'connected';
          this.qrCodeData = null;
          this.rawQr = null;
          this.isReconnecting = false;

          const user = this.sock.user;
          const phone = user?.id ? user.id.split(':')[0].replace(/[^0-9]/g, '') : null;
          this.connectedUser = {
            id: user?.id,
            name: user?.name || user?.notify || 'WhatsApp SIMONGAN',
            phone: phone,
          };

          console.log(`[Baileys] WhatsApp terhubung! Akun: ${this.connectedUser.name} (${this.connectedUser.phone})`);
        } else if (connection === 'close') {
          this.status = 'disconnected';
          this.connectedUser = null;
          this.qrCodeData = null;
          this.rawQr = null;

          const statusCode = lastDisconnect?.error?.output?.statusCode;
          const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

          console.log(`[Baileys] Koneksi terputus. Status Code: ${statusCode}. Auto-reconnect: ${shouldReconnect}`);

          if (shouldReconnect) {
            this.scheduleReconnect(3000);
          } else {
            console.log('[Baileys] Device logged out. Menghapus sesi lama...');
            this.cleanSession();
            this.status = 'logged_out';
            this.scheduleReconnect(2000);
          }
        }
      });
    } catch (err) {
      console.error('[Baileys] Error saat inisialisasi socket:', err);
      this.status = 'disconnected';
      this.scheduleReconnect(5000);
    }
  }

  scheduleReconnect(delayMs = 3000) {
    if (this.isReconnecting) return;
    this.isReconnecting = true;

    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
    }

    this.reconnectTimeout = setTimeout(() => {
      this.isReconnecting = false;
      this.init();
    }, delayMs);
  }

  cleanSession() {
    try {
      if (fs.existsSync(this.sessionDir)) {
        fs.rmSync(this.sessionDir, { recursive: true, force: true });
        console.log('[Baileys] Direktori sesi berhasil dibersihkan.');
      }
    } catch (err) {
      console.error('[Baileys] Gagal menghapus direktori sesi:', err);
    }
  }

  getStatus() {
    return {
      status: this.status,
      phone: this.connectedUser?.phone || null,
      name: this.connectedUser?.name || null,
      qr: this.qrCodeData,
      raw_qr: this.rawQr,
    };
  }

  async sendMessage(phone, message) {
    if (this.status !== 'connected' || !this.sock) {
      const err = new Error('WHATSAPP_NOT_CONNECTED');
      err.errorCode = 'WHATSAPP_NOT_CONNECTED';
      err.isPermanent = false;
      throw err;
    }

    if (!phone || typeof phone !== 'string') {
      const err = new Error('INVALID_PHONE');
      err.errorCode = 'INVALID_PHONE';
      err.isPermanent = true;
      throw err;
    }

    if (!message || typeof message !== 'string' || message.trim() === '') {
      const err = new Error('INVALID_MESSAGE');
      err.errorCode = 'INVALID_MESSAGE';
      err.isPermanent = true;
      throw err;
    }

    const cleanPhone = phone.replace(/[^0-9]/g, '');
    if (!cleanPhone.startsWith('628') || cleanPhone.length < 10 || cleanPhone.length > 16) {
      const err = new Error('INVALID_PHONE');
      err.errorCode = 'INVALID_PHONE';
      err.isPermanent = true;
      throw err;
    }

    const jid = `${cleanPhone}@s.whatsapp.net`;

    // Verify WhatsApp registration via onWhatsApp
    try {
      const results = await this.sock.onWhatsApp(jid);
      const onWa = Array.isArray(results) ? results[0] : results;
      if (!onWa || !onWa.exists) {
        const err = new Error('NOT_ON_WHATSAPP');
        err.errorCode = 'NOT_ON_WHATSAPP';
        err.isPermanent = true;
        throw err;
      }
    } catch (err) {
      if (err.errorCode === 'NOT_ON_WHATSAPP') {
        throw err;
      }
      console.warn(`[Baileys] onWhatsApp check encountered issue for ${cleanPhone}:`, err.message);
      if (this.status !== 'connected' || !this.sock) {
        const connErr = new Error('WHATSAPP_NOT_CONNECTED');
        connErr.errorCode = 'WHATSAPP_NOT_CONNECTED';
        connErr.isPermanent = false;
        throw connErr;
      }
      const isTimeout = err.message?.toLowerCase().includes('timeout') || err.message?.toLowerCase().includes('timed out');
      const transientErr = new Error(`Gagal memeriksa status WhatsApp: ${err.message}`);
      transientErr.errorCode = isTimeout ? 'TIMEOUT' : 'SEND_FAILED';
      transientErr.isPermanent = false;
      throw transientErr;
    }

    try {
      const sendResult = await this.sock.sendMessage(jid, { text: message });
      if (sendResult?.key?.id && sendResult?.message) {
        this.messageStore.saveMessage(sendResult.key.id, sendResult.message);
      }

      return {
        success: true,
        messageId: sendResult?.key?.id || null,
        timestamp: sendResult?.messageTimestamp || Date.now(),
        error: null,
        errorCode: null,
        isPermanent: false,
      };
    } catch (err) {
      console.error(`[Baileys] Gagal mengirim pesan ke ${cleanPhone}:`, err.message);
      const isConnError = this.status !== 'connected' || !this.sock || err.message?.includes('connection') || err.message?.includes('Socket');
      const isTimeout = err.message?.toLowerCase().includes('timed out') || err.message?.toLowerCase().includes('timeout');

      const sendErr = new Error(err.message || 'Gagal mengirim pesan via Baileys.');
      sendErr.errorCode = isConnError ? 'WHATSAPP_NOT_CONNECTED' : (isTimeout ? 'TIMEOUT' : 'SEND_FAILED');
      sendErr.isPermanent = false;
      throw sendErr;
    }
  }

  async disconnect() {
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
    }
    this.isReconnecting = false;

    if (this.sock) {
      try {
        await this.sock.logout();
      } catch (err) {
        console.warn('[Baileys] Error saat logout:', err.message);
      }
      try {
        this.sock.end(undefined);
      } catch (_) {}
      this.sock = null;
    }

    this.cleanSession();
    this.messageStore.clear();
    this.status = 'logged_out';
    this.connectedUser = null;
    this.qrCodeData = null;
    this.rawQr = null;

    // Reinitialize to produce new QR
    this.scheduleReconnect(1000);

    return { success: true, message: 'Sesi WhatsApp berhasil diputuskan.' };
  }

  async gracefulShutdown() {
    console.log('[Baileys] Menutup koneksi dengan aman...');
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
    }
    if (this.sock) {
      try {
        this.sock.end(undefined);
      } catch (_) {}
    }
  }
}

export const gateway = new BaileysGateway();
