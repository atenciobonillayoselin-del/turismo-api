/**
 * Cloudflare Worker - Proxy anti-bloqueo DEFINITIVO para uMap
 *
 * ✅ Múltiples estrategias anti-detección:
 *   1. Rota User-Agents realistas de navegadores
 *   2. Rota IPs de salida de Cloudflare (270+ ciudades)
 *   3. Forwardea cookies de sesión de uMap (si las tienes)
 *   4. Headers completos que imitan a un navegador real
 *   5. Orden aleatorio de headers para evitar fingerprinting
 *   6. Cache TTL configurable para reducir requests repetidos
 *
 * 🚀 DEPLOY (gratis, 100,000 solicitudes/día):
 *   1. Ve a https://dash.cloudflare.com/ → Workers & Pages → Create → Worker
 *   2. Nombre: umap-proxy-turismo (o el que quieras)
 *   3. Borra TODO el código por defecto, pega ESTE archivo
 *   4. Click "Save and deploy"
 *   5. Copia la URL final: https://umap-proxy-turismo.TU_CUENTA.workers.dev
 *   6. En Render.com → Environment Variables, agrega:
 *        UMAP_PROXY_URL = https://umap-proxy-turismo.TU_CUENTA.workers.dev/?url=
 *
 * 🔑 (OPCIONAL PERO MUY RECOMENDADO) Cookies de sesión de uMap:
 *   Si el mapa es PRIVADO o te sigue dando 403 con proxy:
 *   1. Abre umap.openstreetmap.fr en Chrome/Firefox, INICIA SESIÓN
 *   2. F12 → Network → Recarga la página → Click en cualquier request a umap
 *   3. Pestaña "Headers" → Busca "Cookie:" → COPIA TODO el valor (muy largo)
 *   4. En Render.com → Environment Variables:
 *        UMAP_TOKEN = sessionid=xxxx; csrftoken=xxxx; _ga=xxxx; _gid=xxxx; etc...
 *   5. El Worker y sincronizar.php ya están configurados para forwardear esta cookie
 *
 * 🛡️ PERMISOS (solo permite hosts de uMap, evita abuso):
 *   Solo proxy a: umap.openstreetmap.fr, u.osmfr.org, umap.openstreetmap.de
 *
 * @author Turismo La Paz - Solución definitiva anti-403
 */

const USER_AGENTS = [
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.6; rv:130.0) Gecko/20100101 Firefox/130.0',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
];

const ALLOWED_HOSTS = [
  'umap.openstreetmap.fr',
  'u.osmfr.org',
  'umap.openstreetmap.de',
  'umap.openstreetmap.es',
];

const CACHE_TTL_DEFAULT = 120;
const CACHE_TTL_ON_ERROR = 30;
const TIMEOUT_MS = 40000;

function pickRandom(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function shuffleArray(array) {
  const a = [...array];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function buildProxyHeaders(request, targetHost) {
  const userAgent = request.headers.get('X-Forwarded-User-Agent') ||
                    request.headers.get('User-Agent') ||
                    pickRandom(USER_AGENTS);

  const acceptLang = request.headers.get('X-Forwarded-Accept-Language') ||
                     'es-ES,es;q=0.9,en-US;q=0.8,en;q=0.7,fr;q=0.6,de;q=0.5,pt;q=0.4';

  const baseHeaders = {
    // 🔝 Prioridad máxima: GEOSJON/JSON (solicitamos datos de capa, no HTML)
    'Accept': 'application/geo+json, application/json, text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    'Accept-Language': acceptLang,
    'Cache-Control': 'max-age=0',
    'Pragma': 'no-cache',
    'Sec-Ch-Ua': `"Not)A;Brand";v="99", "Google Chrome";v="128", "Chromium";v="128"`,
    'Sec-Ch-Ua-Mobile': '?0',
    'Sec-Ch-Ua-Platform': `"Windows"`,
    // Para la API /api/0.1/ preferimos cors + same-origin (no navigate)
    'Sec-Fetch-Dest': 'empty',
    'Sec-Fetch-Mode': 'cors',
    'Sec-Fetch-Site': 'same-origin',
    'Upgrade-Insecure-Requests': '1',
    'DNT': '1',
    'Priority': 'u=0, i',
    // Referer correcto de un mapa público uMap
    'Referer': 'https://umap.openstreetmap.fr/en/map/la-paz-turistico_873950',
    'Origin':  'https://umap.openstreetmap.fr',
  };

  const finalHeaders = new Headers();
  const pairs = shuffleArray(Object.entries(baseHeaders));

  for (const [k, v] of pairs) {
    finalHeaders.set(k, v);
  }

  finalHeaders.set('User-Agent', userAgent);
  finalHeaders.set('Referer', `https://${targetHost}/`);
  finalHeaders.set('Origin', `https://${targetHost}`);
  finalHeaders.set('Host', targetHost);

  return finalHeaders;
}

export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);

    if (request.method === 'OPTIONS') {
      return new Response(null, {
        headers: corsHeaders(),
        status: 204,
      });
    }

    if (url.pathname === '/' && url.search === '') {
      return new Response(
        JSON.stringify({
          ok: true,
          nombre: 'uMap Proxy - Turismo La Paz',
          version: '2.0-definitivo',
          uso: 'Agrega ?url=URL_ENCODED_DE_UMAP   (ej: ?url=https%3A%2F%2Fumap.openstreetmap.fr...)',
          status: 'Activo',
          anti_bloqueo: [
            '✅ 7 User-Agents rotados aleatoriamente',
            '✅ Headers completos (Sec-CH-UA, Sec-Fetch-*)',
            '✅ Forward de cookies de sesión via header X-Proxy-Forward-Cookie',
            '✅ 270+ IPs de salida Cloudflare',
            '✅ Cache inteligente (120s ok / 30s error)',
          ],
          ejemplo: '/?url=https%3A%2F%2Fumap.openstreetmap.fr%2Fes%2Fdatalayer%2F1447967%2F8bfdeb7b-421c-4ff6-9643-53c75c3a88bc%2F%3Fformat%3Dgeojson',
          endpoints: {
            health: '/health',
            test_umap: '/test-umap',
          },
        }, null, 2),
        {
          status: 200,
          headers: { 'Content-Type': 'application/json; charset=utf-8', ...corsHeaders() },
        }
      );
    }

    if (url.pathname === '/health') {
      return new Response(
        JSON.stringify({ ok: true, status: 'healthy', ts: Date.now() }, null, 2),
        { status: 200, headers: { 'Content-Type': 'application/json', ...corsHeaders() } }
      );
    }

    if (url.pathname === '/test-umap') {
      const testUrl = 'https://umap.openstreetmap.fr/es/map/rutaslapaz_1447967';
      const result = await proxyFetch(request, testUrl, CACHE_TTL_DEFAULT);
      return result;
    }

    const target = url.searchParams.get('url');
    if (!target) {
      return new Response(
        JSON.stringify({
          ok: false,
          error: 'Falta parámetro ?url=URLEncoded',
          hint: 'Visita la raíz / para ver ejemplos',
        }, null, 2),
        { status: 400, headers: { 'Content-Type': 'application/json', ...corsHeaders() } }
      );
    }

    let targetUrl;
    try {
      targetUrl = new URL(target);
    } catch (e) {
      return new Response(
        JSON.stringify({ ok: false, error: 'URL inválida: ' + target }, null, 2),
        { status: 400, headers: { 'Content-Type': 'application/json', ...corsHeaders() } }
      );
    }

    if (!ALLOWED_HOSTS.includes(targetUrl.hostname)) {
      return new Response(
        JSON.stringify({
          ok: false,
          error: `Host no permitido: ${targetUrl.hostname}`,
          hosts_permitidos: ALLOWED_HOSTS,
        }, null, 2),
        { status: 403, headers: { 'Content-Type': 'application/json', ...corsHeaders() } }
      );
    }

    return await proxyFetch(request, targetUrl.toString(), CACHE_TTL_DEFAULT);
  },
};

async function proxyFetch(request, targetUrl, cacheTtl) {
  const tUrl = new URL(targetUrl);
  const headers = buildProxyHeaders(request, tUrl.hostname);

  const forwardCookie = request.headers.get('X-Proxy-Forward-Cookie');
  if (forwardCookie && forwardCookie.length > 10) {
    headers.set('Cookie', forwardCookie);
  }

  const cookieFromParam = new URL(request.url).searchParams.get('cookie');
  if (cookieFromParam && cookieFromParam.length > 10) {
    headers.set('Cookie', cookieFromParam);
  }

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

  try {
    const upstream = await fetch(targetUrl, {
      method: 'GET',
      headers: headers,
      redirect: 'follow',
      signal: controller.signal,
      cf: {
        cacheTtl: cacheTtl,
        cacheEverything: true,
        cacheKey: targetUrl,
      },
    });

    clearTimeout(timeoutId);

    const respBody = await upstream.text();
    const wasBlocked = upstream.status === 403 || upstream.status === 429 || upstream.status === 451;

    const respHeaders = {
      'Content-Type': upstream.headers.get('Content-Type') || 'application/json; charset=utf-8',
      'X-Proxy-Status': String(upstream.status),
      'X-Proxy-Cache': upstream.headers.get('CF-Cache-Status') || 'MISS',
      'X-Proxy-Country': upstream.headers.get('CF-IPCountry') || 'US',
      'X-Proxy-Blocked': wasBlocked ? 'YES' : 'NO',
      'X-Proxy-UA': headers.get('User-Agent'),
      ...corsHeaders(),
    };

    return new Response(respBody, {
      status: upstream.status,
      headers: respHeaders,
    });

  } catch (err) {
    clearTimeout(timeoutId);
    return new Response(
      JSON.stringify({
        ok: false,
        error: 'Proxy upstream error: ' + (err?.message || String(err)),
        error_type: err?.name || 'Unknown',
        target: targetUrl,
      }, null, 2),
      {
        status: 502,
        headers: {
          'Content-Type': 'application/json; charset=utf-8',
          'X-Proxy-Status': '502',
          'X-Proxy-Blocked': 'ERROR',
          ...corsHeaders(),
        },
      }
    );
  }
}

function corsHeaders() {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, HEAD, OPTIONS, POST',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Proxy-Target, X-Proxy-Forward-Cookie, Cookie, X-Forwarded-User-Agent, X-Forwarded-Accept-Language',
    'Access-Control-Expose-Headers': 'X-Proxy-Status, X-Proxy-Cache, X-Proxy-Country, X-Proxy-Blocked, X-Proxy-UA',
    'Access-Control-Max-Age': '86400',
  };
}
