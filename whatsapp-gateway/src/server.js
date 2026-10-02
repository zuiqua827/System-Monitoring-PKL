import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { gateway } from './baileys.js';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;
const AUTH_TOKEN = process.env.GATEWAY_AUTH_TOKEN || process.env.API_KEY || null;

app.use(cors());
app.use(express.json());

// API Key / Bearer Token authentication middleware
const authenticateApiKey = (req, res, next) => {
  if (!AUTH_TOKEN) {
    return next();
  }

  const authHeader = req.headers['authorization'];
  let bearerToken = null;
  if (authHeader && authHeader.startsWith('Bearer ')) {
    bearerToken = authHeader.substring(7).trim();
  }

  const clientKey = req.headers['x-api-key'] || bearerToken || req.query.api_key;
  if (clientKey !== AUTH_TOKEN) {
    return res.status(401).json({
      success: false,
      error: 'Unauthorized: Kunci API/Token tidak valid.',
    });
  }

  next();
};

// Health Check (public)
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    service: 'SIMONGAN WhatsApp Gateway',
    uptime: process.uptime(),
    timestamp: new Date().toISOString(),
  });
});

// Gateway Status (Connection, QR Code, Phone)
app.get('/api/status', authenticateApiKey, (req, res) => {
  try {
    const statusData = gateway.getStatus();
    res.json({
      success: true,
      ...statusData,
    });
  } catch (err) {
    res.status(500).json({
      success: false,
      error: err.message,
    });
  }
});

// Normalize phone number to canonical Indonesian mobile format: 628xxxxxxxx
export const normalizePhone = (phone) => {
  if (!phone || typeof phone !== 'string') return null;
  const digitsOnly = phone.replace(/[^0-9]/g, '');
  if (!digitsOnly) return null;

  let clean = digitsOnly;
  if (clean.startsWith('08')) {
    clean = '628' + clean.substring(2);
  } else if (clean.startsWith('8')) {
    clean = '62' + clean;
  }

  // Indonesian mobile numbers must start with country code + mobile prefix: 628
  if (!clean.startsWith('628')) {
    return null;
  }

  // Mobile numbers in Indonesia are 10-16 digits canonical
  if (clean.length < 10 || clean.length > 16) {
    return null;
  }

  return clean;
};

// Send Message Endpoint
app.post('/api/send', authenticateApiKey, async (req, res) => {
  const { phone, message } = req.body;

  if (!phone || typeof phone !== 'string' || !message || typeof message !== 'string') {
    return res.status(400).json({
      success: false,
      error: 'Parameter phone dan message wajib diisi dengan format string yang valid.',
      errorCode: 'INVALID_PARAMETERS',
      isPermanent: true,
    });
  }

  if (message.trim() === '') {
    return res.status(400).json({
      success: false,
      error: 'Pesan WhatsApp tidak boleh kosong.',
      errorCode: 'INVALID_MESSAGE',
      isPermanent: true,
    });
  }

  const cleanPhone = normalizePhone(phone);
  if (!cleanPhone) {
    return res.status(400).json({
      success: false,
      error: 'Format nomor telepon tidak valid. Harus nomor seluler Indonesia yang valid (diawali 628, 10-16 digit).',
      errorCode: 'INVALID_PHONE',
      isPermanent: true,
    });
  }

  try {
    const result = await gateway.sendMessage(cleanPhone, message);
    res.json({
      success: true,
      messageId: result.messageId || null,
      timestamp: result.timestamp || Date.now(),
      error: null,
      errorCode: null,
      isPermanent: false,
    });
  } catch (err) {
    let statusCode = 500;
    const msg = err.message || '';
    let errorCode = err.errorCode || 'UNKNOWN_ERROR';
    let isPermanent = err.isPermanent ?? false;

    if (errorCode === 'WHATSAPP_NOT_CONNECTED' || msg.includes('WHATSAPP_NOT_CONNECTED')) {
      statusCode = 503;
      errorCode = 'WHATSAPP_NOT_CONNECTED';
      isPermanent = false;
    } else if (errorCode === 'TIMEOUT' || msg.toLowerCase().includes('timeout') || msg.toLowerCase().includes('timed out')) {
      statusCode = 504;
      errorCode = 'TIMEOUT';
      isPermanent = false;
    } else if (errorCode === 'NOT_ON_WHATSAPP' || msg.includes('NOT_ON_WHATSAPP')) {
      statusCode = 422;
      errorCode = 'NOT_ON_WHATSAPP';
      isPermanent = true;
    } else if (errorCode === 'INVALID_PHONE' || msg.includes('INVALID_PHONE')) {
      statusCode = 422;
      errorCode = 'INVALID_PHONE';
      isPermanent = true;
    } else if (errorCode === 'INVALID_MESSAGE' || msg.includes('INVALID_MESSAGE')) {
      statusCode = 422;
      errorCode = 'INVALID_MESSAGE';
      isPermanent = true;
    } else if (errorCode === 'INVALID_PARAMETERS') {
      statusCode = 400;
      errorCode = 'INVALID_PARAMETERS';
      isPermanent = true;
    } else {
      errorCode = 'SEND_FAILED';
      isPermanent = false;
    }

    res.status(statusCode).json({
      success: false,
      error: msg || 'Gagal mengirim pesan WhatsApp.',
      errorCode: errorCode,
      isPermanent: isPermanent,
    });
  }
});

// Disconnect / Logout Session
app.post('/api/disconnect', authenticateApiKey, async (req, res) => {
  try {
    const result = await gateway.disconnect();
    res.json(result);
  } catch (err) {
    res.status(500).json({
      success: false,
      error: err.message,
    });
  }
});

// Restart Socket Connection
app.post('/api/restart', authenticateApiKey, async (req, res) => {
  try {
    await gateway.init();
    res.json({ success: true, message: 'Inisialisasi ulang gateway berhasil dipicu.' });
  } catch (err) {
    res.status(500).json({
      success: false,
      error: err.message,
    });
  }
});

// Global error handler
app.use((err, req, res, next) => {
  console.error('[Server Error]', err);
  res.status(500).json({
    success: false,
    error: err.message || 'Internal Server Error',
  });
});

let server = null;
const isDirectRun = process.argv[1] && process.argv[1].endsWith('server.js');
if (isDirectRun && process.env.NODE_ENV !== 'test') {
  // Start Express server
  server = app.listen(PORT, '0.0.0.0', () => {
    console.log(`=============================================`);
    console.log(` SIMONGAN WhatsApp Gateway (Baileys) `);
    console.log(` Server berjalan pada port http://localhost:${PORT}`);
    console.log(` Status: http://localhost:${PORT}/api/status`);
    console.log(`=============================================`);

    // Initialize Baileys socket connection
    gateway.init();
  });

  // Graceful shutdown handlers
  const shutdown = async () => {
    console.log('\n[Server] Memulai shutdown dengan aman...');
    await gateway.gracefulShutdown();
    server.close(() => {
      console.log('[Server] Proses Node diakhiri.');
      process.exit(0);
    });
  };

  process.on('SIGINT', shutdown);
  process.on('SIGTERM', shutdown);
}

export { app, server };
