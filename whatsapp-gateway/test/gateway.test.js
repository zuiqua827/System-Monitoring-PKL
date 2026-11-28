import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizePhone } from '../src/server.js';
import { MessageStore } from '../src/baileys.js';

test('normalizePhone formats Indonesian numbers correctly', () => {
  // A. 08123456789 -> 628123456789
  assert.equal(normalizePhone('08123456789'), '628123456789');

  // B. 0812 3456 789 -> 628123456789
  assert.equal(normalizePhone('0812 3456 789'), '628123456789');

  // C. 0812-3456-789 -> 628123456789
  assert.equal(normalizePhone('0812-3456-789'), '628123456789');

  // D. +628123456789 -> 628123456789
  assert.equal(normalizePhone('+628123456789'), '628123456789');

  // E. 628123456789 -> 628123456789
  assert.equal(normalizePhone('628123456789'), '628123456789');

  // F. 8123456789 -> 628123456789
  assert.equal(normalizePhone('8123456789'), '628123456789');

  // G. Empty string -> null
  assert.equal(normalizePhone(''), null);

  // H. null or non-string -> null
  assert.equal(normalizePhone(null), null);
  assert.equal(normalizePhone(undefined), null);

  // I. Too short (< 10 digits)
  assert.equal(normalizePhone('0812'), null);

  // J. Too long (> 16 digits)
  assert.equal(normalizePhone('0812345678901234567890'), null);

  // K. Non-Indonesian prefix or landline
  assert.equal(normalizePhone('1234567890'), null);
  assert.equal(normalizePhone('0211234567'), null);
});

test('MessageStore stores and retrieves messages by id', () => {
  const store = new MessageStore(5, 5000);
  const sampleMsg = { conversation: 'Halo dunia' };

  store.saveMessage('msg-001', sampleMsg);
  const retrieved = store.getMessage('msg-001');

  assert.deepEqual(retrieved, sampleMsg);
  assert.equal(store.getMessage('non-existent'), null);
});

test('MessageStore evicts oldest message when maxSize is exceeded', () => {
  const store = new MessageStore(2, 5000);

  store.saveMessage('msg-1', { text: '1' });
  store.saveMessage('msg-2', { text: '2' });
  store.saveMessage('msg-3', { text: '3' }); // Should evict msg-1

  assert.equal(store.getMessage('msg-1'), null);
  assert.deepEqual(store.getMessage('msg-2'), { text: '2' });
  assert.deepEqual(store.getMessage('msg-3'), { text: '3' });
});

test('MessageStore handles expired messages based on TTL', async () => {
  const store = new MessageStore(10, 50); // 50ms TTL
  store.saveMessage('msg-quick', { text: 'quick' });

  assert.deepEqual(store.getMessage('msg-quick'), { text: 'quick' });

  // Wait 60ms to expire
  await new Promise((r) => setTimeout(r, 60));

  assert.equal(store.getMessage('msg-quick'), null);
});

test('POST /api/send validates parameters and returns structured errorCodes', async () => {
  const { app } = await import('../src/server.js');
  const http = await import('http');

  const testServer = http.createServer(app);
  await new Promise((resolve) => testServer.listen(0, resolve));
  const port = testServer.address().port;
  const baseUrl = `http://127.0.0.1:${port}`;

  try {
    // 1. Missing parameters -> 400 INVALID_PARAMETERS
    const res1 = await fetch(`${baseUrl}/api/send`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: '' }),
    });
    assert.equal(res1.status, 400);
    const data1 = await res1.json();
    assert.equal(data1.errorCode, 'INVALID_PARAMETERS');
    assert.equal(data1.isPermanent, true);

    // 2. Empty message -> 400 INVALID_MESSAGE
    const res2 = await fetch(`${baseUrl}/api/send`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: '081234567890', message: '   ' }),
    });
    assert.equal(res2.status, 400);
    const data2 = await res2.json();
    assert.equal(data2.errorCode, 'INVALID_MESSAGE');
    assert.equal(data2.isPermanent, true);

    // 3. Invalid phone number -> 400 INVALID_PHONE
    const res3 = await fetch(`${baseUrl}/api/send`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ phone: '123456789', message: 'Hello' }),
    });
    assert.equal(res3.status, 400);
    const data3 = await res3.json();
    assert.equal(data3.errorCode, 'INVALID_PHONE');
    assert.equal(data3.isPermanent, true);
  } finally {
    await new Promise((resolve) => testServer.close(resolve));
  }
});
