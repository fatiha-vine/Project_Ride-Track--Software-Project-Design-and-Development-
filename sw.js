const C='ridetrack-v2';
self.addEventListener('install',e=>{e.waitUntil(caches.open(C).then(c=>c.addAll(['style.css','app.js','icon-192.png'])).then(()=>self.skipWaiting()))});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x!==C).map(x=>caches.delete(x)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',e=>{const r=e.request;if(r.method!=='GET')return;const u=new URL(r.url);if(u.origin!==location.origin)return;
 if(/\.(css|js|png|webmanifest)$/.test(u.pathname)){e.respondWith(fetch(r).then(x=>{const cp=x.clone();caches.open(C).then(c=>c.put(r,cp));return x}).catch(()=>caches.match(r)));return}
 e.respondWith(fetch(r).catch(()=>new Response('You are offline. Reconnect to use Ride Track.',{status:503,headers:{'Content-Type':'text/plain'}})))});
