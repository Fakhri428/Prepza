/**
 * End-to-end integration test script (Service B simulator)
 *
 * Flow:
 * 1) GET categories, menus, queue orders from Service A
 * 2) Pick first order and do simple status analysis
 * 3) PATCH external-update to Service A
 * 4) POST trend update to Service A
 * 5) Log every response and before/after status
 *
 * Requirements:
 * - Node.js 18+ (built-in fetch)
 * - Base URL fixed to http://localhost:8000
 */

const BASE_URL = 'http://localhost:8000';

// Optional token support (if later Service A adds auth)
const SERVICE_A_TOKEN = process.env.SERVICE_A_TOKEN || '';

/**
 * Small request helper for JSON endpoints.
 * Throws clear errors with status and response body.
 */
async function requestJson(path, options = {}) {
  const url = `${BASE_URL}${path}`;

  const headers = {
    Accept: 'application/json',
    ...(options.body ? { 'Content-Type': 'application/json' } : {}),
    ...(SERVICE_A_TOKEN ? { Authorization: `Bearer ${SERVICE_A_TOKEN}` } : {}),
    ...(options.headers || {}),
  };

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const text = await response.text();
  let data;

  try {
    data = text ? JSON.parse(text) : null;
  } catch {
    data = { raw: text };
  }

  if (!response.ok) {
    const error = new Error(`HTTP ${response.status} ${response.statusText} for ${url}`);
    error.status = response.status;
    error.url = url;
    error.response = data;
    throw error;
  }

  return {
    status: response.status,
    url,
    data,
  };
}

/**
 * Determine next external status with simple rule:
 * waiting -> processing
 * processing -> done
 * others -> null (no update)
 */
function getNextStatus(currentStatus) {
  const status = String(currentStatus || '').toLowerCase();

  if (status === 'waiting') return 'processing';
  if (status === 'processing') return 'done';
  return null;
}

/**
 * Main test flow.
 */
async function run() {
  console.log('=== Service B E2E Simulation Start ===');
  console.log(`Base URL: ${BASE_URL}`);

  // 1) Fetch reference data + queue orders
  console.log('\n[1] Fetching categories...');
  const categoriesRes = await requestJson('/api/categories');
  console.log('Categories response:', JSON.stringify(categoriesRes.data, null, 2));

  console.log('\n[1] Fetching menus...');
  const menusRes = await requestJson('/api/menus');
  console.log('Menus response:', JSON.stringify(menusRes.data, null, 2));

  console.log('\n[1] Fetching queue orders (queued,waiting,processing)...');
  const ordersRes = await requestJson('/api/queue/orders?status=queued,waiting,processing');
  console.log('Queue orders response:', JSON.stringify(ordersRes.data, null, 2));

  const orders = Array.isArray(ordersRes.data?.data) ? ordersRes.data.data : [];

  if (orders.length === 0) {
    console.log('\nNo orders available');
    console.log('=== Service B E2E Simulation End ===');
    return;
  }

  // 2) Simple analysis on first order
  const firstOrder = orders[0];
  const orderId = firstOrder.id;
  const beforeStatus = firstOrder.external_status || firstOrder.status;
  const nextStatus = getNextStatus(firstOrder.status);

  console.log('\n[2] Simple analysis result:');
  console.log(`Order ID: ${orderId}`);
  console.log(`Before status: ${beforeStatus}`);
  console.log(`Order internal status: ${firstOrder.status}`);

  let afterStatus = beforeStatus;

  if (!nextStatus) {
    console.log('No status transition rule for this order status. Skipping PATCH update.');
  } else {
    // 3) Send external update
    const externalUpdatePayload = {
      external_status: nextStatus,
      external_note: `Auto update from Service B test script: ${beforeStatus} -> ${nextStatus}`,
    };

    console.log('\n[3] Sending PATCH external-update...');
    console.log('PATCH payload:', JSON.stringify(externalUpdatePayload, null, 2));

    const patchRes = await requestJson(`/api/queue/orders/${orderId}/external-update`, {
      method: 'PATCH',
      body: JSON.stringify(externalUpdatePayload),
    });

    console.log('PATCH response:', JSON.stringify(patchRes.data, null, 2));

    afterStatus = patchRes.data?.order?.external_status || nextStatus;
  }

  console.log('\nStatus change summary:');
  console.log(`Order ${orderId}: ${beforeStatus} -> ${afterStatus}`);

  // 4) Send dummy trend update
  const now = new Date();
  const expiresAt = new Date(now.getTime() + 2 * 60 * 60 * 1000); // +2 hours

  const trendPayload = {
    title: 'Ayam Geprek Test Trend',
    image_url: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c',
    caption: 'Dummy trend dari script e2e Service B',
    score: 85,
    source_timestamp: now.toISOString(),
    expires_at: expiresAt.toISOString(),
    is_active: true,
  };

  console.log('\n[4] Sending POST trend update...');
  console.log('Trend payload:', JSON.stringify(trendPayload, null, 2));

  const trendRes = await requestJson('/api/queue/trends/update', {
    method: 'POST',
    body: JSON.stringify(trendPayload),
  });

  console.log('Trend response:', JSON.stringify(trendRes.data, null, 2));

  console.log('\n=== Service B E2E Simulation End ===');
}

// 5) Global error handling
run().catch((error) => {
  console.error('\nE2E script failed.');
  console.error('Message:', error.message);

  if (error.status) {
    console.error('Status:', error.status);
  }

  if (error.url) {
    console.error('URL:', error.url);
  }

  if (error.response) {
    console.error('Response:', JSON.stringify(error.response, null, 2));
  }

  process.exitCode = 1;
});
