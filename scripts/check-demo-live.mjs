const BASE = (process.env.BASE_URL || 'https://app.kuhu.org.in').replace(/\/$/, '');

async function main() {
  const jar = new Map();
  const getCookie = (res) => {
    const raw = res.headers.getSetCookie?.() || [];
    for (const line of raw) {
      const [pair] = line.split(';');
      const [k, v] = pair.split('=');
      if (k) jar.set(k.trim(), v ?? '');
    }
  };
  const cookieHeader = () => [...jar.entries()].map(([k, v]) => `${k}=${v}`).join('; ');

  const loginPage = await fetch(`${BASE}/login`, { redirect: 'manual' });
  getCookie(loginPage);
  const html = await loginPage.text();
  const token = html.match(/name="csrf-token" content="([^"]+)"/)?.[1] || '';

  const body = new URLSearchParams({
    workspace: 'demodashboard',
    email: 'demo@vouchex.in',
    password: 'demo@1234',
    _token: token,
  });

  const login = await fetch(`${BASE}/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Cookie: cookieHeader(),
      Referer: `${BASE}/login`,
    },
    body,
    redirect: 'manual',
  });
  getCookie(login);

  let pageUrl = `${BASE}/my-day`;
  let page = await fetch(pageUrl, {
    headers: { Cookie: cookieHeader() },
    redirect: 'follow',
  });
  getCookie(page);
  let content = await page.text();

  if (content.includes('Sign in') || page.url?.includes?.('/login')) {
    page = await fetch(`${BASE}/dashboard`, { headers: { Cookie: cookieHeader() } });
    content = await page.text();
    pageUrl = `${BASE}/dashboard`;
  }

  const build = content.match(/Build:\s*([^<]+)/)?.[1]?.trim();
  const autoShow = content.match(/const autoShow = (true|false);/)?.[1];
  const stepsCount = content.match(/const steps = (\[[\s\S]*?\]);/)?.[1];
  let stepsLen = null;
  try { stepsLen = stepsCount ? JSON.parse(stepsCount).length : null; } catch {}
  const report = {
    pageUrl,
    httpStatus: page.status,
    build,
    demoTourRoot: content.includes('id="demo-tour-root"'),
    takeTourFab: content.includes('Take a tour'),
    demoBadge: />\s*Demo\s*</.test(content),
    welcomeModal: content.includes('Welcome to Vouchex'),
    startingIn: content.includes('Starting in'),
    driverJs: content.includes('driver.js'),
    demoTourWelcomeFn: content.includes('function demoTourWelcome'),
    serverError: /Server Error|Whoops/i.test(content),
    autoShow,
    stepsLen,
    alpineCdn: content.includes('alpinejs'),
    xCloakOnWelcome: content.includes('x-show="welcomeOpen" x-cloak'),
  };
  console.log(JSON.stringify(report, null, 2));
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
