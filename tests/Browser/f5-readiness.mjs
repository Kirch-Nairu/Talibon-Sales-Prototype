import { chromium } from 'playwright';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
if (!PASSWORD) throw new Error('QA_DEMO_PASSWORD is required');

const OUT = 'storage/app/qa/f5-readiness-report.json';
const SHOTS = 'storage/app/qa/f5-screenshots';
const expectedShots = [
  'records-desktop-light.png',
  'records-mobile-light.png',
  'travel-orders-desktop-light.png',
  'travel-orders-mobile-light.png',
  'reports-desktop-populated.png',
  'reports-mobile-populated.png',
  'reports-desktop-dark.png',
];
const report = { generatedAt:new Date().toISOString(), completed:false, baseline:null, checks:[], targets:[], screenshots:[], diagnostics:[], failure:null };
const secrets = new Set([PASSWORD]);
const runtime = [];
let stage = 'bootstrap', pageRef = null;

const scrub = (value) => {
  let text = String(value ?? '');
  for (const secret of secrets) text = text.replaceAll(secret, '[MASKED]');
  return text;
};
const safe = (url) => { try { const u = new URL(url, BASE); return u.pathname + u.search; } catch { return '[unavailable]'; } };
const check = (name, ok, details='') => {
  const row = { name:`F5: ${scrub(name)}`, ok:Boolean(ok), details:scrub(details) };
  report.checks.push(row);
  if (!row.ok) throw new Error(`${row.name}: ${row.details}`);
};
const write = async () => { await fs.mkdir('storage/app/qa',{recursive:true}); await fs.writeFile(OUT, JSON.stringify(report,null,2)); };
const checkpoint = (nextStage, page=pageRef) => { stage=nextStage; if (page) pageRef=page; };

function monitor(page) {
  page.on('pageerror', error => runtime.push({type:'pageerror',path:safe(page.url()),details:error.message}));
  page.on('response', response => { if (response.status() >= 500) runtime.push({type:'server-5xx',path:safe(response.url()),details:String(response.status())}); });
}
function base32(secret) {
  const alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; let bits='';
  for (const char of secret.replace(/=+$/g,'').replace(/\s+/g,'').toUpperCase()) {
    const index=alphabet.indexOf(char); if(index<0) throw new Error('Invalid MFA secret'); bits+=index.toString(2).padStart(5,'0');
  }
  const bytes=[]; for(let i=0;i+8<=bits.length;i+=8) bytes.push(parseInt(bits.slice(i,i+8),2)); return Buffer.from(bytes);
}
function totp(secret) {
  const counter=Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now()/30000)));
  const digest=crypto.createHmac('sha1',base32(secret)).update(counter).digest(), offset=digest.at(-1)&15;
  const value=((digest[offset]&127)<<24)|(digest[offset+1]<<16)|(digest[offset+2]<<8)|digest[offset+3];
  return String(value%1000000).padStart(6,'0');
}
async function baselines() {
  checkpoint('accepted baselines');
  const [f1,f2,f3,f4] = await Promise.all(
    ['demo-readiness-report.json','f2-readiness-report.json','f3-readiness-report.json','f4-readiness-report.json']
      .map(name => fs.readFile(`storage/app/qa/${name}`,'utf8').then(JSON.parse)),
  );
  const f1n=f1.checks.filter(row=>row.name.startsWith('F1:')).length;
  const historical=f1.checks.length-f1n;
  const f2n=f2.checks.filter(row=>row.name.startsWith('F2:')).length;
  const f3n=f3.checks.filter(row=>row.name.startsWith('F3:')).length;
  const f4n=f4.checks.filter(row=>row.name.startsWith('F4:')).length;
  const failures = (r,prefix) => r.checks.filter(row=>row.name.startsWith(`${prefix}:`)&&!row.ok).length;
  report.baseline={historical,f1:f1n,accounts:f1.accounts.length,f1Screenshots:f1.screenshots.length,f2:f2n,f2Screenshots:f2.screenshots.length,f3:f3n,f3Targets:f3.targets.length,f3Screenshots:f3.screenshots.length,f4:f4n,f4Targets:f4.targets.length,f4Screenshots:f4.screenshots.length};
  check('historical/F1 baseline preserved',historical===174&&f1n===133,JSON.stringify(report.baseline));
  check('representative accounts and F1 screenshots preserved',f1.accounts.length===7&&f1.screenshots.length===7,JSON.stringify(report.baseline));
  check('F2 preserved',f2.completed===true&&f2.failure==null&&f2n===39&&failures(f2,'F2')===0&&f2.screenshots.length===5,JSON.stringify(report.baseline));
  check('F3 preserved',f3.completed===true&&f3.failure==null&&f3n===313&&failures(f3,'F3')===0&&f3.targets.length===5&&f3.screenshots.length===7,JSON.stringify(report.baseline));
  check('F4 preserved',f4.completed===true&&f4.failure==null&&f4n===58&&failures(f4,'F4')===0&&f4.targets.length===2&&f4.screenshots.length===6,JSON.stringify(report.baseline));
}
async function login(page) {
  checkpoint('authentication',page);
  await page.goto(`${BASE}/login`);
  await page.getByLabel('Email').fill('engineering@talibon.demo');
  await page.getByLabel('Password').fill(PASSWORD);
  await Promise.all([
    page.waitForURL(url=>['/dashboard','/security/mfa/enroll','/security/mfa/challenge'].includes(url.pathname)),
    page.getByRole('button',{name:'Sign In'}).click(),
  ]);
  check('fresh F5 login does not reuse MFA challenge',new URL(page.url()).pathname!='/security/mfa/challenge',safe(page.url()));
  if (new URL(page.url()).pathname==='/security/mfa/enroll') {
    const secret=(await page.locator('code').first().innerText()).trim(); secrets.add(secret);
    await page.getByLabel('Six-digit verification code').fill(totp(secret));
    await Promise.all([
      page.waitForURL(url=>url.pathname==='/security/mfa/recovery-codes'),
      page.getByRole('button',{name:/Confirm MFA enrollment/i}).click(),
    ]);
    const codes=(await page.locator('pre').innerText()).trim().split(/\s+/); codes.forEach(code=>secrets.add(code));
    await Promise.all([
      page.waitForURL(url=>url.pathname==='/dashboard'),
      page.getByRole('link',{name:/Continue to portal/i}).click(),
    ]);
  }
  check('department head reaches dashboard',new URL(page.url()).pathname==='/dashboard',safe(page.url()));
}
async function appearance(page,name,value) {
  const group=page.locator('[role="group"][aria-label="Appearance"]:visible').first();
  await group.waitFor({state:'visible'}); await group.getByRole('button',{name,exact:true}).click();
  await page.waitForFunction(expected=>document.documentElement.dataset.appearance===expected,value);
}
async function noOverflow(page,label) {
  const metrics=await page.evaluate(()=>({width:innerWidth,root:document.documentElement.scrollWidth,body:document.body.scrollWidth}));
  check(`${label}: no root horizontal overflow`,metrics.root<=metrics.width+1&&metrics.body<=metrics.width+1,JSON.stringify(metrics));
}
async function articleFit(article,label) {
  const metrics=await article.evaluate(node=>({scrollWidth:node.scrollWidth,clientWidth:node.clientWidth}));
  check(`${label}: result card fits its container`,metrics.scrollWidth<=metrics.clientWidth+1,JSON.stringify(metrics));
}
async function noErrors(label,start) {
  const errors=runtime.slice(start); check(`${label}: no page errors or server 5xx`,errors.length===0,JSON.stringify(errors));
}
async function shot(page,name) {
  await fs.mkdir(SHOTS,{recursive:true}); const path=`${SHOTS}/${name}`;
  await page.screenshot({path,animations:'disabled'}); report.screenshots.push(path);
}
async function formFor(page) {
  const button=page.getByRole('button',{name:/^Filters(?: \d+)?$/}).first(); await button.waitFor({state:'visible'});
  return button.locator('xpath=ancestor::form[1]');
}
async function controlByCaption(form,caption) {
  const labels=form.locator('label');
  const index=await labels.evaluateAll((nodes,wanted)=>nodes.findIndex(label=>{
    const normalize=value=>String(value||'').replace(/\s+/g,' ').trim();
    const direct=normalize([...label.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).map(node=>node.textContent).join(' '));
    const first=label.firstElementChild?.tagName==='SPAN'?normalize(label.firstElementChild.textContent):'';
    return direct===wanted||first===wanted;
  }),caption);
  if(index<0) throw new Error(`filter caption missing: ${caption}`);
  return labels.nth(index).locator('input,select,textarea').first();
}
async function activeFiltersVisible(form,label) {
  const active=form.getByLabel('Active filters');
  check(`${label}: F3 active filter chips remain visible`,await active.isVisible());
}

async function records(page) {
  checkpoint('records',page); let start=runtime.length;
  await page.setViewportSize({width:1440,height:900}); let response=await page.goto(`${BASE}/records`); await appearance(page,'Light','light');
  check('records desktop loads',response?.status()===200,String(response?.status()));
  const registry=page.getByRole('region',{name:'Records registry'}); await registry.waitFor({state:'visible'});
  const articles=registry.getByRole('article'); check('records populated results visible',await articles.count()>=2,`articles=${await articles.count()}`);
  const seeded=articles.filter({hasText:'TAL-F5-QA-REPORT-0001'}).first(); check('records deterministic transaction result visible',await seeded.count()===1);
  check('records reference/title hierarchy readable',await seeded.getByRole('link',{name:'TAL-F5-QA-REPORT-0001',exact:true}).isVisible()&&await seeded.locator('h2').isVisible());
  check('records type and state readable',await seeded.getByText('Transaction',{exact:true}).isVisible()&&await seeded.getByText('For Review',{exact:true}).isVisible());
  check('records accountability readable',await seeded.getByText('Current office',{exact:true}).isVisible()&&await seeded.getByText('Responsible',{exact:true}).isVisible());
  check('records action reachable',await seeded.getByRole('link',{name:/^Open record /}).isVisible()); await articleFit(seeded,'records desktop');
  const form=await formFor(page), typeControl=await controlByCaption(form,'Record Type'); await typeControl.selectOption('travel_order');
  await Promise.all([page.waitForURL(url=>url.pathname==='/records'&&url.searchParams.get('record_type')==='travel_order'),form.getByRole('button',{name:'Search',exact:true}).click()]);
  await activeFiltersVisible(form,'records'); check('records F3 filter returns Travel Order evidence',await registry.getByText('TAL-TO-F5-0001',{exact:true}).isVisible());
  await Promise.all([page.waitForURL(url=>url.pathname==='/records'&&!url.searchParams.has('record_type')),form.getByRole('button',{name:'Clear records filters'}).click()]);
  await shot(page,'records-desktop-light.png'); await noOverflow(page,'records desktop'); await noErrors('records desktop',start);

  start=runtime.length; await page.setViewportSize({width:390,height:844}); response=await page.goto(`${BASE}/records`);
  check('records mobile loads',response?.status()===200,String(response?.status()));
  const mobileRegistry=page.getByRole('region',{name:'Records registry'}), mobile=mobileRegistry.getByRole('article').filter({hasText:'TAL-F5-QA-REPORT-0001'}).first();
  check('records mobile populated hierarchy remains visible',await mobile.count()===1&&await mobile.locator('h2').isVisible());
  check('records mobile accountability remains visible',await mobile.getByText('Current office',{exact:true}).isVisible()&&await mobile.getByText('Responsible',{exact:true}).isVisible());
  check('records mobile action reachable',await mobile.getByRole('link',{name:/^Open record /}).isVisible()); await articleFit(mobile,'records mobile');
  await noOverflow(page,'records mobile'); await shot(page,'records-mobile-light.png'); await noErrors('records mobile',start);
  report.targets.push({key:'records',result:'pass'});
}

async function travelOrders(page) {
  checkpoint('travel-orders',page); let start=runtime.length;
  await page.setViewportSize({width:1440,height:900}); let response=await page.goto(`${BASE}/travel-orders`); await appearance(page,'Light','light');
  check('travel-orders desktop loads',response?.status()===200,String(response?.status()));
  const registry=page.getByRole('region',{name:'Approved travel order registry'}); await registry.waitFor({state:'visible'});
  const articles=registry.getByRole('article'); check('travel-orders populated results visible',await articles.count()>=2,`articles=${await articles.count()}`);
  const seeded=articles.filter({hasText:'TAL-TO-F5-0001'}).first(); check('travel-orders deterministic approved order visible',await seeded.count()===1);
  check('travel-orders reference hierarchy readable',await seeded.getByRole('link',{name:'TAL-TO-F5-0001',exact:true}).isVisible()&&await seeded.locator('h2').isVisible());
  check('travel-orders purpose and destination readable',await seeded.getByText('Municipal drainage site validation and barangay coordination',{exact:true}).isVisible()&&await seeded.getByText('Barangay San Isidro, Talibon, Bohol',{exact:true}).isVisible());
  for (const label of ['Travel period','Responsible office','Personnel issued','Issued']) check(`travel-orders ${label} readable`,await seeded.getByText(label,{exact:true}).isVisible());
  check('travel-orders existing personnel-count contract readable',await seeded.getByText(/person(?:nel)?$/i).first().isVisible());
  check('travel-orders status readable',await seeded.getByText('Approved',{exact:true}).isVisible());
  check('travel-orders action reachable',await seeded.getByRole('link',{name:'Open travel order TAL-TO-F5-0001'}).isVisible()); await articleFit(seeded,'travel-orders desktop');
  const form=await formFor(page), statusControl=await controlByCaption(form,'Status'); await statusControl.selectOption('completed');
  await Promise.all([page.waitForURL(url=>url.pathname==='/travel-orders'&&url.searchParams.get('status')==='completed'),form.getByRole('button',{name:'Apply',exact:true}).click()]);
  await activeFiltersVisible(form,'travel-orders'); check('travel-orders F3 filter returns completed evidence',await registry.getByText('TAL-TO-F5-0002',{exact:true}).isVisible());
  await Promise.all([page.waitForURL(url=>url.pathname==='/travel-orders'&&!url.searchParams.has('status')),form.getByRole('button',{name:'Clear Travel Order filters'}).click()]);
  await shot(page,'travel-orders-desktop-light.png'); await noOverflow(page,'travel-orders desktop'); await noErrors('travel-orders desktop',start);

  start=runtime.length; await page.setViewportSize({width:390,height:844}); response=await page.goto(`${BASE}/travel-orders`);
  check('travel-orders mobile loads',response?.status()===200,String(response?.status()));
  const mobile=page.getByRole('region',{name:'Approved travel order registry'}).getByRole('article').filter({hasText:'TAL-TO-F5-0001'}).first();
  check('travel-orders mobile populated hierarchy remains visible',await mobile.count()===1&&await mobile.locator('h2').isVisible());
  check('travel-orders mobile period and office remain visible',await mobile.getByText('Travel period',{exact:true}).isVisible()&&await mobile.getByText('Responsible office',{exact:true}).isVisible());
  check('travel-orders mobile personnel information readable',await mobile.getByText('Personnel issued',{exact:true}).isVisible());
  check('travel-orders mobile action reachable',await mobile.getByRole('link',{name:'Open travel order TAL-TO-F5-0001'}).isVisible()); await articleFit(mobile,'travel-orders mobile');
  await noOverflow(page,'travel-orders mobile'); await shot(page,'travel-orders-mobile-light.png'); await noErrors('travel-orders mobile',start);
  report.targets.push({key:'travel-orders',result:'pass'});
}

async function reports(page) {
  checkpoint('reports',page); let start=runtime.length;
  const populatedUrl=`${BASE}/reports?report=transaction-aging`;
  await page.setViewportSize({width:1440,height:900}); let response=await page.goto(populatedUrl); await appearance(page,'Light','light');
  check('reports desktop loads',response?.status()===200,String(response?.status()));
  const results=page.getByRole('region',{name:'Transaction Aging results'}); await results.waitFor({state:'visible'});
  check('reports detailed-result hierarchy visible',await results.getByText('Detailed results',{exact:true}).isVisible()&&await results.getByText('Transaction Aging',{exact:true}).isVisible());
  const articles=results.getByRole('article'); check('reports representative populated result exists',await articles.count()>0,`articles=${await articles.count()}`);
  const seeded=articles.filter({hasText:'TAL-F5-QA-REPORT-0001'}).first(); check('reports deterministic transaction result visible',await seeded.count()===1);
  check('reports lead reference/title hierarchy readable',await seeded.getByText('Reference',{exact:true}).isVisible()&&await seeded.getByText('Title',{exact:true}).isVisible()&&await seeded.getByText('TAL-F5-QA-REPORT-0001',{exact:true}).isVisible());
  for (const label of ['Current office','Assignee','Status','Priority','Due state']) check(`reports ${label} readable`,await seeded.getByText(label,{exact:true}).isVisible());
  check('reports export action reachable',await page.getByRole('link',{name:'Export CSV',exact:true}).isVisible());
  check('reports detail action reachable',await seeded.getByRole('link',{name:/^Open Transaction Aging result /}).isVisible()); await articleFit(seeded,'reports desktop');
  const form=await formFor(page), statusControl=await controlByCaption(form,'Status'); const options=await statusControl.locator('option').evaluateAll(nodes=>nodes.map(node=>node.value));
  const status=options.includes('assigned')?'assigned':options.find(value=>value)||''; check('reports has selectable common status filter',Boolean(status),JSON.stringify(options)); await statusControl.selectOption(status);
  await Promise.all([
    page.waitForFunction(expectedStatus => {
      const url=new URL(window.location.href);
      return url.pathname==='/reports'&&url.searchParams.get('report')==='transaction-aging'&&url.searchParams.get('status')===expectedStatus;
    }, status),
    form.getByRole('button',{name:'Apply',exact:true}).click(),
  ]);
  await activeFiltersVisible(form,'reports');
  await Promise.all([
    page.waitForFunction(() => {
      const url=new URL(window.location.href);
      return url.pathname==='/reports'&&url.searchParams.get('report')==='transaction-aging'&&!url.searchParams.has('status');
    }),
    form.getByRole('button',{name:'Reset',exact:true}).click(),
  ]);
  await page.waitForLoadState('networkidle'); await shot(page,'reports-desktop-populated.png'); await noOverflow(page,'reports desktop'); await noErrors('reports desktop',start);

  start=runtime.length; response=await page.goto(`${BASE}/reports?report=transaction-aging&date_from=2099-01-01&date_to=2099-01-02`);
  check('reports zero-result query loads',response?.status()===200,String(response?.status()));
  const zero=page.getByRole('region',{name:'Transaction Aging results'});
  await zero.waitFor({state:'visible'});
  const zeroCount=zero.getByText('0 results',{exact:true});
  const zeroState=zero.getByText('No authorized report results match these filters.',{exact:true});
  await zeroCount.waitFor({state:'visible'});
  await zeroState.waitFor({state:'visible'});
  check('reports zero-result state coherent',await zeroCount.isVisible()&&await zeroState.isVisible(),`articles=${await zero.getByRole('article').count()}`);
  await noOverflow(page,'reports zero result'); await noErrors('reports zero result',start);

  start=runtime.length; await page.setViewportSize({width:390,height:844}); response=await page.goto(populatedUrl);
  check('reports mobile loads',response?.status()===200,String(response?.status()));
  const mobileResults=page.getByRole('region',{name:'Transaction Aging results'}), mobile=mobileResults.getByRole('article').filter({hasText:'TAL-F5-QA-REPORT-0001'}).first();
  check('reports mobile representative result remains visible',await mobile.count()===1);
  check('reports mobile hierarchy remains readable',await mobile.getByText('Reference',{exact:true}).isVisible()&&await mobile.getByText('Current office',{exact:true}).isVisible());
  check('reports mobile export remains reachable',await page.getByRole('link',{name:'Export CSV',exact:true}).isVisible());
  check('reports mobile detail action reachable',await mobile.getByRole('link',{name:/^Open Transaction Aging result /}).isVisible()); await articleFit(mobile,'reports mobile');
  await noOverflow(page,'reports mobile'); await shot(page,'reports-mobile-populated.png'); await noErrors('reports mobile',start);

  start=runtime.length; await page.setViewportSize({width:1440,height:900}); await page.goto(populatedUrl); await appearance(page,'Dark','dark');
  const contrast=await page.evaluate(()=>{
    const surface=document.querySelector('section[aria-label="Transaction Aging results"]'); const text=surface?.querySelector('article');
    if(!surface||!text) return null;
    const canvas=document.createElement('canvas'),ctx=canvas.getContext('2d',{willReadFrequently:true}); canvas.width=canvas.height=1;
    const rgb=value=>{ctx.fillStyle='#000';ctx.fillStyle=value;ctx.fillRect(0,0,1,1);const d=ctx.getImageData(0,0,1,1).data;return[d[0],d[1],d[2]]};
    const lum=a=>{const f=v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4};return .2126*f(a[0])+.7152*f(a[1])+.0722*f(a[2])};
    const background=getComputedStyle(surface).backgroundColor, foreground=getComputedStyle(text).color, L1=lum(rgb(background)),L2=lum(rgb(foreground));
    return {background,foreground,ratio:(Math.max(L1,L2)+.05)/(Math.min(L1,L2)+.05)};
  });
  check('reports dark appearance active',await page.evaluate(()=>document.documentElement.dataset.appearance)==='dark');
  check('reports white operational surface contrast readable',Boolean(contrast&&contrast.ratio>=4.5),JSON.stringify(contrast));
  await noOverflow(page,'reports dark desktop'); await shot(page,'reports-desktop-dark.png'); await noErrors('reports dark desktop',start);
  report.targets.push({key:'reports',result:'pass'});
}

await write();
let browser;
try {
  await baselines();
  browser=await chromium.launch({headless:true}); const context=await browser.newContext({viewport:{width:1440,height:900}}); const page=await context.newPage(); pageRef=page; monitor(page);
  await login(page); await records(page); await travelOrders(page); await reports(page);
  checkpoint('finalize',page);
  const missing=expectedShots.filter(name=>!report.screenshots.some(path=>path.endsWith(`/${name}`)));
  check('exact F5 screenshot set captured',report.screenshots.length===expectedShots.length&&missing.length===0,JSON.stringify({count:report.screenshots.length,missing}));
  check('all F5 targets completed',report.targets.length===3&&report.targets.every(target=>target.result==='pass'),JSON.stringify(report.targets));
  report.diagnostics=runtime.map(item=>({type:item.type,path:item.path,details:scrub(item.details)}));
  check('F5 runtime diagnostics empty',report.diagnostics.length===0,JSON.stringify(report.diagnostics));
  report.completed=true; report.failure=null; await write();
  const failed=report.checks.filter(row=>!row.ok).length;
  console.log(`F5_BROWSER_QA_PASS historical=${report.baseline.historical} f1=${report.baseline.f1} f2=${report.baseline.f2} f3=${report.baseline.f3} f4=${report.baseline.f4} f5=${report.checks.length} accounts=${report.baseline.accounts} f1_screenshots=${report.baseline.f1Screenshots} f2_screenshots=${report.baseline.f2Screenshots} f3_targets=${report.baseline.f3Targets} f3_screenshots=${report.baseline.f3Screenshots} f4_targets=${report.baseline.f4Targets} f4_screenshots=${report.baseline.f4Screenshots} f5_targets=${report.targets.length} f5_screenshots=${report.screenshots.length} failures=${failed}`);
} catch (error) {
  report.completed=false; report.failure={stage,path:pageRef?safe(pageRef.url()):null,error:scrub(error?.stack||error)};
  report.diagnostics=runtime.map(item=>({type:item.type,path:item.path,details:scrub(item.details)})); await write();
  console.error(`F5_BROWSER_QA_FAIL stage=${stage} path=${report.failure.path || '[none]'} error=${scrub(error?.message||error)}`); process.exitCode=1;
} finally {
  if(browser) await browser.close();
}
