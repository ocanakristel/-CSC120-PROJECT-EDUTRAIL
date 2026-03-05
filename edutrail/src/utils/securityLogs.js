// src/utils/securityLogs.js
// Lightweight client-side security logging helper.
// For now this just prints to console to avoid noisy 404s when
// the backend does not expose a security_logs endpoint.
export const logSecurityEvent = async (action, details = '') => {
  try {
    const payload = { action, details, ts: new Date().toISOString() }
    // keep a lightweight client-side trace
    // eslint-disable-next-line no-console
    console.info('SECURITY_EVENT', payload)
    return { ok: true }
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn('Failed to record security event locally', e)
    return { ok: false }
  }
}
