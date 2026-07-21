// pawapay-client.js
// Client minimal pour l'API pawaPay V1 (deposits, payouts, refunds).
// Nécessite : npm install node-fetch uuid (ou fetch natif si Node >= 18)

const { randomUUID } = require('crypto');

const PAWAPAY_ENV = process.env.PAWAPAY_ENV || 'sandbox';
const BASE_URL = PAWAPAY_ENV === 'production'
  ? process.env.PAWAPAY_BASE_URL_PRODUCTION
  : process.env.PAWAPAY_BASE_URL_SANDBOX;
const API_TOKEN = PAWAPAY_ENV === 'production'
  ? process.env.PAWAPAY_API_TOKEN_PRODUCTION
  : process.env.PAWAPAY_API_TOKEN_SANDBOX;

if (!BASE_URL || !API_TOKEN) {
  throw new Error('Configuration pawaPay manquante : vérifier PAWAPAY_ENV et les variables associées dans .env');
}

async function pawapayRequest(path, method, body) {
  const response = await fetch(`${BASE_URL}${path}`, {
    method,
    headers: {
      Authorization: `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    const err = new Error(`pawaPay API error (${response.status})`);
    err.status = response.status;
    err.data = data;
    throw err;
  }

  return data;
}

/**
 * Initier un deposit (encaisser de l'argent du client).
 * @param {object} params
 * @param {string} params.amount - montant en string, ex: "15.00"
 * @param {string} params.currency - ex: "USD"
 * @param {string} params.country - code ISO 3 lettres, ex: "COD"
 * @param {string} params.correspondent - ex: "ORANGE_COD"
 * @param {string} params.msisdn - numéro du client, ex: "243123456789"
 * @param {string} params.statementDescription - 4 à 22 caractères alphanumériques
 * @param {Array<{fieldName: string, fieldValue: string, isPII?: boolean}>} [params.metadata]
 */
async function initiateDeposit({ amount, currency, country, correspondent, msisdn, statementDescription, metadata }) {
  const depositId = randomUUID();
  const payload = {
    depositId,
    amount,
    currency,
    country,
    correspondent,
    payer: { type: 'MSISDN', address: { value: msisdn } },
    customerTimestamp: new Date().toISOString(),
    statementDescription,
    ...(metadata ? { metadata } : {}),
  };
  const result = await pawapayRequest('deposits', 'POST', payload);
  return { depositId, result };
}

/**
 * Initier un payout (envoyer de l'argent à un bénéficiaire).
 */
async function initiatePayout({ amount, currency, country, correspondent, msisdn, statementDescription, metadata }) {
  const payoutId = randomUUID();
  const payload = {
    payoutId,
    amount,
    currency,
    country,
    correspondent,
    recipient: { type: 'MSISDN', address: { value: msisdn } },
    customerTimestamp: new Date().toISOString(),
    statementDescription,
    ...(metadata ? { metadata } : {}),
  };
  const result = await pawapayRequest('payouts', 'POST', payload);
  return { payoutId, result };
}

/**
 * Rembourser un deposit existant.
 */
async function initiateRefund({ depositId, amount, metadata }) {
  const refundId = randomUUID();
  const payload = {
    refundId,
    depositId,
    ...(amount ? { amount } : {}), // omettre `amount` = remboursement total selon la doc pawaPay
    ...(metadata ? { metadata } : {}),
  };
  const result = await pawapayRequest('refunds', 'POST', payload);
  return { refundId, result };
}

async function checkDepositStatus(depositId) {
  return pawapayRequest(`deposits/${depositId}`, 'GET');
}

async function checkPayoutStatus(payoutId) {
  return pawapayRequest(`payouts/${payoutId}`, 'GET');
}

async function predictCorrespondent(msisdn) {
  return pawapayRequest(`predict-correspondent?msisdn=${encodeURIComponent(msisdn)}`, 'GET');
}

module.exports = {
  initiateDeposit,
  initiatePayout,
  initiateRefund,
  checkDepositStatus,
  checkPayoutStatus,
  predictCorrespondent,
};
