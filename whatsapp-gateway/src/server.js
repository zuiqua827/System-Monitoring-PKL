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

// Send Message Endpoint
app.post('/api/send', authenticateApiKey, async (req, res) => {
  const { phone, message } = req.body;

  if (!phone || typeof phone !== 'string' || !message || typeof message !== 'string') {
    return res.status(400).json({
      success: false,
      error: 'Parameter phone dan message wajib diisi dengan format string yang valid.',
    });
  }

  const cleanPhone = phone.replace(/[^0-9]/g, '');
  if (cleanPhone.length < 10 || cleanPhone.length > 16) {
    return res.status(400).json({
      success: false,
      error: 'Format nomor telepon tidak valid. Panjang harus antara 10-16 digit.',
    });
  }

  try {
    const result = await gateway.sendMessage(phone, message);
    res.json(result);
  } catch (err) {
    res.status(500).json({
      success: false,
      error: err.message || 'Gagal mengirim pesan WhatsApp.',
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
app.listen(PORT, '0.0.0.0', () => {
  console.log(`=============================================`);
  console.log(` SIMONGAN WhatsApp Gateway (Baileys) `);
  console.log(` Server berjalan pada port http://localhost:${PORT}`);
  console.log(` Status: http://localhost:${PORT}/api/status`);
  console.log(`=============================================`);

  // Initialize Baileys socket connection
  gateway.init();
});
