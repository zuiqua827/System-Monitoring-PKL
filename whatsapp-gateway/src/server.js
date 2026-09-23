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

// Normalize phone number
const normalizePhone = (phone) => {
  if (!phone || typeof phone !== 'string') return null;
  let clean = phone.replace(/[^0-9]/g, '');
  if (clean.startsWith('0')) {
    clean = '62' + clean.substring(1);
  } else if (clean.startsWith('8')) {
    clean = '62' + clean;
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
    });
  }

  const cleanPhone = normalizePhone(phone);
  if (!cleanPhone || cleanPhone.length < 10 || cleanPhone.length > 16) {
    return res.status(400).json({
      success: false,
      error: 'Format nomor telepon tidak valid. Panjang harus antara 10-16 digit.',
      errorCode: 'INVALID_PHONE',
    });
  }

  try {
    const result = await gateway.sendMessage(cleanPhone, message);
    res.json(result);
  } catch (err) {
    let statusCode = 500;
    const msg = err.message || '';
    let errorCode = 'UNKNOWN_ERROR';

    if (msg.includes('WHATSAPP_NOT_CONNECTED')) {
      statusCode = 503;
      errorCode = 'WHATSAPP_NOT_CONNECTED';
    } else if (msg.includes('INVALID_PHONE') || msg.includes('NOT_ON_WHATSAPP')) {
      statusCode = 422; // Unprocessable Entity (permanent error for client)
      errorCode = msg.includes('NOT_ON_WHATSAPP') ? 'NOT_ON_WHATSAPP' : 'INVALID_PHONE';
    } else {
      errorCode = 'SEND_FAILED';
    }

    res.status(statusCode).json({
      success: false,
      error: msg || 'Gagal mengirim pesan WhatsApp.',
      errorCode: errorCode,
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

// Start Express server
const server = app.listen(PORT, '0.0.0.0', () => {
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
