import { chromium } from 'playwright';
import crypto from 'node:crypto';
import fs from 'node:fs/promises';

const BASE = process.env.QA_BASE_URL || 'http://127.0.0.1:8000';
const PASSWORD = process.env.QA_DEMO_PASSWORD;
if (!PASSWORD) throw new Error('QA_DEMO_PASSWORD is required');

const OUT = 'storage/app/qa/f4-readiness-report.json';
const SHOTS = 'storage/app/qa/f4-screenshots';
const expectedShots = [
  'my-work-desktop-light.png', 'my-work-mobile-light.png',
  'my-work-mobile-overdue-or-action-queue.png', 'correspondence-desktop-light.png',
  'correspondence-mobile-light.png', 'correspondence-desktop-dark.png',
];
const queues = ['My Work','Needs Action','Assigned to Me','Due Soon','Overdue','Recently Updated','Waiting on Another Office','Completed Recently','Office Work','Unassigned','Staff Workload','Escalations'];
const report = { generatedAt:new Date().toISOString(), completed:false, baseline:null, checks:[], targets:[], screenshots:[], diagnostics:[], failure:null };
const secrets = new Set([PASSWORD]);
const runtime = [];
let stage = 'bootstrap', pageRef = null;

const scrub = (v) => {
  let s = String(v ?? '');
  for (const x of secrets) s = s.replaceAll(x, '[MASKED]');
  return s;
};
const safe = (url) => { try { const u = new URL(url, BASE); return u.pathname + u.search; } catch { return '[unavailable]'; } };
const check = (name, ok, details='') => {
  const row = { name:`F4: ${scrub(name)}`, ok:Boolean(ok), details:scrub(details) };
  report.checks.push(row);
  if (!row.ok) throw new Error(`${row.name}: ${row.details}`);
};
const write = async () => { await fs.mkdir('storage/app/qa',{recursive:true}); await fs.writeFile(OUT, JSON.stringify(report,null,2)); };
const checkpoint = (s,p=pageRef) => { stage=s; if(p) pageRef=p; };

function monitor(page) {
  page.on('pageerror', e => runtime.push({type:'pageerror',path:safe(page.url()),details:e.message}));
  page.on('response', r => { if(r.status()>=500) runtime.push({type:'server-5xx',path:safe(r.url()),details:String(r.status())}); });
}
function base32(s) {
  const a='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; let bits='';
  for(const c of s.replace(/=+$/g,'').replace(/\s+/g,'').toUpperCase()){const i=a.indexOf(c);if(i<0)throw new Error('Invalid MFA secret');bits+=i.toString(2).padStart(5,'0');}
  const b=[]; for(let i=0;i+8<=bits.length;i+=8)b.push(parseInt(bits.slice(i,i+8),2)); return Buffer.from(b);
}
function totp(secret) {
  const counter=Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now()/30000)));
  const d=crypto.createHmac('sha1',base32(secret)).update(counter).digest(), o=d.at(-1)&15;
  const v=((d[o]&127)<<24)|(d[o+1]<<16)|(d[o+2]<<8)|d[o+3]; return String(v%1000000).padStart(6,'0');
}
async function baselines() {
  checkpoint('accepted baselines');
  const [f1,f2,f3]=await Promise.all(['demo-readiness-report.json','f2-readiness-report.json','f3-readiness-report.json'].map(n=>fs.readFile(`storage/app/qa/${n}`,'utf8').then(JSON.parse)));
  const f1n=f1.checks.filter(x=>x.name.startsWith('F1:')).length, hist=f1.checks.length-f1n;
  const f2n=f2.checks.filter(x=>x.name.startsWith('F2:')).length, f3n=f3.checks.filter(x=>x.name.startsWith('F3:')).length;
  const f2fail=f2.checks.filter(x=>!x.ok).length, f3fail=f3.checks.filter(x=>!x.ok).length;
  report.baseline={historical:hist,f1:f1n,accounts:f1.accounts.length,f1Screenshots:f1.screenshots.length,f2:f2n,f2Screenshots:f2.screenshots.length,f3:f3n,f3Targets:f3.targets.length,f3Screenshots:f3.screenshots.length};
  check('historical/F1 baseline preserved',hist===174&&f1n===133,JSON.stringify(report.baseline));
  check('representative accounts and F1 screenshots preserved',f1.accounts.length===7&&f1.screenshots.length===7,JSON.stringify(report.baseline));
  check('F2 preserved',f2.completed===true&&f2.failure==null&&f2n===39&&f2fail===0&&f2.screenshots.length===5,`f2=${f2n} failures=${f2fail}`);
  check('F3 preserved',f3.completed===true&&f3.failure==null&&f3n===313&&f3fail===0&&f3.targets.length===5&&f3.screenshots.length===7,`f3=${f3n} failures=${f3fail}`);
}
async function login(page) {
  checkpoint('authentication',page); await page.goto(`${BASE}/login`);
  await page.getByLabel('Email').fill('engineering@talibon.demo'); await page.getByLabel('Password').fill(PASSWORD);
  await Promise.all([page.waitForURL(u=>['/dashboard','/security/mfa/enroll','/security/mfa/challenge'].includes(u.pathname)),page.getByRole('button',{name:'Sign In'}).click()]);
  check('fresh F4 login does not reuse MFA challenge',new URL(page.url()).pathname!='/security/mfa/challenge',safe(page.url()));
  if(new URL(page.url()).pathname==='/security/mfa/enroll'){
    const secret=(await page.locator('code').first().innerText()).trim(); secrets.add(secret);
    await page.getByLabel('Six-digit verification code').fill(totp(secret));
    await Promise.all([page.waitForURL(u=>u.pathname==='/security/mfa/recovery-codes'),page.getByRole('button',{name:/Confirm MFA enrollment/i}).click()]);
    const codes=(await page.locator('pre').innerText()).trim().split(/\s+/); codes.forEach(x=>secrets.add(x));
    await Promise.all([page.waitForURL(u=>u.pathname==='/dashboard'),page.getByRole('link',{name:/Continue to portal/i}).click()]);
  }
  check('department head reaches dashboard',new URL(page.url()).pathname==='/dashboard',safe(page.url()));
}
async function appearance(page,name,value){
  const g=page.locator('[role="group"][aria-label="Appearance"]:visible').first(); await g.waitFor({state:'visible'}); await g.getByRole('button',{name,exact:true}).click();
  await page.waitForFunction(v=>document.documentElement.dataset.appearance===v,value);
}
async function noOverflow(page,label){const m=await page.evaluate(()=>({w:innerWidth,root:document.documentElement.scrollWidth,body:document.body.scrollWidth}));check(`${label}: no root horizontal overflow`,m.root<=m.w+1&&m.body<=m.w+1,JSON.stringify(m));}
async function noErrors(label,start){const e=runtime.slice(start);check(`${label}: no page errors or server 5xx`,e.length===0,JSON.stringify(e));}
async function shot(page,name){await fs.mkdir(SHOTS,{recursive:true});const p=`${SHOTS}/${name}`;await page.screenshot({path:p,animations:'disabled'});report.screenshots.push(p);}
async function formFor(page){const b=page.getByRole('button',{name:/^Filters(?: \d+)?$/}).first();await b.waitFor({state:'visible'});return b.locator('xpath=ancestor::form[1]');}
async function control(form,caption){
  const labels=form.locator('label'); const i=await labels.evaluateAll((nodes,w)=>nodes.findIndex(l=>{const n=v=>String(v||'').replace(/\s+/g,' ').trim();const direct=n([...l.childNodes].filter(x=>x.nodeType===Node.TEXT_NODE).map(x=>x.textContent).join(' '));const first=l.firstElementChild?.tagName==='SPAN'?n(l.firstElementChild.textContent):'';return direct===w||first===w;}),caption);
  if(i<0)throw new Error(`filter caption missing: ${caption}`); return labels.nth(i).locator('input,select,textarea').first();
}
async function f3Filter(page,route,caption,clearName,param){
  const f=await formFor(page), c=await control(f,caption), opts=await c.locator('option').evaluateAll(ns=>ns.map(n=>({v:n.value,t:n.textContent?.trim()}))), o=opts.find(x=>x.v&&x.v!=='all');
  if(!o)throw new Error(`No selectable ${caption} option`); await c.selectOption(o.v); await f.getByRole('button',{name:/Apply(?: filters)?$/i}).click();
  await page.waitForURL(u=>u.pathname===route&&u.searchParams.has(param)); check(`${route}: F3 common filter remains functional`,await f.getByLabel('Active filters').isVisible(),`${caption}=${o.t}`);
  await f.getByRole('button',{name:clearName,exact:true}).click(); await page.waitForURL(u=>u.pathname===route&&!u.searchParams.has(param));
}
async function queueGeometry(page,label){
  const q=page.getByRole('region',{name:'Work queues'}),m=await q.evaluate(root=>({sw:root.scrollWidth,cw:root.clientWidth,w:innerWidth,buttons:[...root.querySelectorAll('button')].map(b=>{const r=b.getBoundingClientRect();return{l:r.left,r:r.right,sw:b.scrollWidth,cw:b.clientWidth};})}));
  check(`${label}: queue selector does not scroll horizontally`,m.sw<=m.cw+1,JSON.stringify(m)); check(`${label}: queue controls are not clipped`,m.buttons.every(b=>b.l>=-1&&b.r<=m.w+1&&b.sw<=b.cw+1),JSON.stringify(m.buttons));
}
async function myWork(page){
  checkpoint('my-work',page); let start=runtime.length; await page.setViewportSize({width:1440,height:900}); let r=await page.goto(`${BASE}/transactions`); check('my-work desktop loads',r?.status()===200,String(r?.status()));
  const q=page.getByRole('region',{name:'Work queues'});await q.waitFor({state:'visible'});const txt=await q.getByRole('button').allTextContents();check('my-work queues all discoverable',queues.every(x=>txt.some(t=>t.trim().startsWith(x))),JSON.stringify(txt));check('my-work active queue identifiable',await q.locator('button[aria-current="page"]').count()===1);await queueGeometry(page,'my-work desktop');
  const a=page.getByRole('article').first(); if(await a.count()){check('my-work reference/title readable',(await a.locator('h2').innerText()).trim().length>0);check('my-work priority and due state readable',await a.getByText(/priority$/i).first().isVisible()&&await a.getByText(/^(On track|Due soon|Overdue|Completed)$/i).first().isVisible());check('my-work action reachable',await a.getByRole('link',{name:/^Open work item /}).isVisible());}
  await shot(page,'my-work-desktop-light.png');await f3Filter(page,'/transactions','Status','Clear filters','status');await noOverflow(page,'my-work desktop');await noErrors('my-work desktop',start);
  start=runtime.length;await page.setViewportSize({width:390,height:844});r=await page.goto(`${BASE}/transactions`);check('my-work mobile loads',r?.status()===200);await queueGeometry(page,'my-work mobile');await noOverflow(page,'my-work mobile');const m=page.getByRole('article').first();if(await m.count()){for(const x of ['Accountability','Assignment','Due','Next action'])check(`my-work mobile ${x} visible`,await m.getByText(x,{exact:true}).isVisible());check('my-work mobile action reachable',await m.getByRole('link',{name:/^Open work item /}).isVisible());}await shot(page,'my-work-mobile-light.png');
  let chosen=null;for(const x of [{label:'Overdue',key:'overdue'},{label:'Needs Action',key:'needs_my_action'},{label:'Due Soon',key:'due_soon'}]){const b=q.getByRole('button',{name:new RegExp(`^${x.label}\\b`)}).first();if(await b.count()){const t=await b.innerText(),count=Number(t.match(/(\d+) item/)?.[1]||0);if(!chosen||count>0)chosen={...x,b,count};if(count>0)break;}}check('my-work urgent/action queue selectable',Boolean(chosen));await chosen.b.click();await page.waitForURL(u=>u.pathname==='/transactions'&&u.searchParams.get('view')===chosen.key);check('my-work selected queue becomes active',(await q.locator('button[aria-current="page"]').innerText()).startsWith(chosen.label));await queueGeometry(page,'my-work selected queue');await noOverflow(page,'my-work selected queue');await shot(page,'my-work-mobile-overdue-or-action-queue.png');await noErrors('my-work mobile',start);report.targets.push({key:'my-work',result:'pass'});
}
async function correspondence(page){
  checkpoint('correspondence',page);let start=runtime.length;await page.setViewportSize({width:1440,height:900});let r=await page.goto(`${BASE}/correspondence`);check('correspondence desktop loads',r?.status()===200);const inbox=page.getByRole('region',{name:'Correspondence inbox'});await inbox.waitFor({state:'visible'});const articles=inbox.getByRole('article'),articleCount=await articles.count();check('correspondence populated evidence exists',articleCount>=2,`articles=${articleCount}`);const evidence=await articles.allTextContents();check('correspondence actionable evidence visible',evidence.some(t=>/Action required/i.test(t)),JSON.stringify(evidence));check('correspondence informational evidence visible',evidence.some(t=>/For information/i.test(t)),JSON.stringify(evidence));const routed=articles.filter({hasText:'TAL-F4-QA-ROUTE-0001'}).first();check('correspondence routed accountability evidence visible',await routed.count()===1&&await routed.getByText('Current responsibility',{exact:true}).isVisible(),JSON.stringify(evidence));const a=articles.first();check('correspondence subject/reference hierarchy readable',(await a.locator('h2').innerText()).trim().length>0&&(await a.locator('span').first().innerText()).trim().length>0);check('correspondence responsibility visible',await a.getByText('Current responsibility',{exact:true}).isVisible());check('correspondence lifecycle readable',await a.getByText(/^(Received|Registered|Classified|Routed|In Action)$/).first().isVisible());check('correspondence action state readable',await a.getByText(/^(Action required|For information)$/).first().isVisible());check('correspondence action reachable',await a.getByRole('link',{name:/^Open correspondence /}).isVisible());
  await shot(page,'correspondence-desktop-light.png');await f3Filter(page,'/correspondence','Lifecycle','Clear','lifecycle');await noOverflow(page,'correspondence desktop');await noErrors('correspondence desktop',start);
  start=runtime.length;await page.setViewportSize({width:390,height:844});r=await page.goto(`${BASE}/correspondence`);check('correspondence mobile loads',r?.status()===200);const mobileArticles=page.getByRole('region',{name:'Correspondence inbox'}).getByRole('article'),mobileCount=await mobileArticles.count();check('correspondence mobile populated evidence remains visible',mobileCount>=2,`articles=${mobileCount}`);const m=mobileArticles.first();check('correspondence mobile responsibility visible',await m.getByText('Current responsibility',{exact:true}).isVisible());check('correspondence mobile action reachable',await m.getByRole('link',{name:/^Open correspondence /}).isVisible());await noOverflow(page,'correspondence mobile');await shot(page,'correspondence-mobile-light.png');await noErrors('correspondence mobile',start);
  start=runtime.length;await page.setViewportSize({width:1440,height:900});await page.goto(`${BASE}/correspondence`);await appearance(page,'Dark','dark');const c=await page.evaluate(()=>{const s=document.querySelector('section[aria-label="Correspondence inbox"]'),t=s?.querySelector('article h2');const cv=document.createElement('canvas'),x=cv.getContext('2d',{willReadFrequently:true});cv.width=cv.height=1;const rgb=v=>{x.fillStyle='#000';x.fillStyle=v;x.fillRect(0,0,1,1);const d=x.getImageData(0,0,1,1).data;return[d[0],d[1],d[2]]};const L=a=>{const f=v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4};return .2126*f(a[0])+.7152*f(a[1])+.0722*f(a[2])};if(!s||!t)return{dark:document.documentElement.classList.contains('dark'),ratio:null};const A=L(rgb(getComputedStyle(s).backgroundColor)),B=L(rgb(getComputedStyle(t).color));return{dark:document.documentElement.classList.contains('dark'),ratio:(Math.max(A,B)+.05)/(Math.min(A,B)+.05)}});check('correspondence dark appearance active',c.dark===true,JSON.stringify(c));check('correspondence white surface contrast remains readable',c.ratio!==null&&c.ratio>=4.5,JSON.stringify(c));await noOverflow(page,'correspondence dark');await shot(page,'correspondence-desktop-dark.png');await noErrors('correspondence dark',start);await appearance(page,'Light','light');report.targets.push({key:'correspondence',result:'pass'});
}

async function main(){await baselines();const browser=await chromium.launch({headless:true});try{const ctx=await browser.newContext({viewport:{width:1440,height:900}}),page=await ctx.newPage();pageRef=page;monitor(page);await login(page);await appearance(page,'Light','light');await myWork(page);await correspondence(page);check('F4 target matrix 2/2',report.targets.length===2);check('F4 screenshot set 6/6',report.screenshots.length===6);check('F4 screenshot filenames exact',JSON.stringify(report.screenshots.map(p=>p.split('/').at(-1)))===JSON.stringify(expectedShots));report.completed=true;await write();console.log(`F4_BROWSER_QA_PASS historical=${report.baseline.historical} f1=${report.baseline.f1} f2=${report.baseline.f2} f3=${report.baseline.f3} f4=${report.checks.length} accounts=${report.baseline.accounts} f1_screenshots=${report.baseline.f1Screenshots} f2_screenshots=${report.baseline.f2Screenshots} f3_targets=${report.baseline.f3Targets} f3_screenshots=${report.baseline.f3Screenshots} f4_targets=${report.targets.length} f4_screenshots=${report.screenshots.length} failures=0`);await ctx.close();}finally{await browser.close();}}
main().catch(async e=>{report.failure={stage:scrub(stage),path:scrub(safe(pageRef?.url?.()||BASE)),error:scrub(e?.message||e)};report.diagnostics.push({stage:report.failure.stage,path:report.failure.path,type:'fatal',details:report.failure.error});await write().catch(()=>{});console.error(`F4_BROWSER_QA_FAIL stage=${report.failure.stage} path=${report.failure.path} error=${report.failure.error}`);process.exitCode=1;});