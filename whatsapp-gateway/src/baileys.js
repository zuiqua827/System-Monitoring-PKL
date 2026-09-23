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
      throw new Error('WHATSAPP_NOT_CONNECTED');
    }

    if (!phone || typeof phone !== 'string') {
      throw new Error('INVALID_PHONE');
    }

    if (!message || typeof message !== 'string') {
      throw new Error('INVALID_MESSAGE');
    }

    const cleanPhone = phone.replace(/[^0-9]/g, '');
    const jid = `${cleanPhone}@s.whatsapp.net`;

    try {
      const [result] = await this.sock.onWhatsApp(jid);
      if (!result || !result.exists) {
         throw new Error('NOT_ON_WHATSAPP');
      }

      const sendResult = await this.sock.sendMessage(result.jid, { text: message });
      return {
        success: true,
        messageId: sendResult?.key?.id || null,
        timestamp: sendResult?.messageTimestamp || Date.now(),
      };
    } catch (err) {
      console.error(`[Baileys] Gagal mengirim pesan ke ${phone}:`, err.message);
      throw err;
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
