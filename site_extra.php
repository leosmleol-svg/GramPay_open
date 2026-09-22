<?php
// Shared helpers for public trust/legal/status pages.
declare(strict_types=1);
function gpPageHeader(string $title, string $description = ''): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-Frame-Options: SAMEORIGIN');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="description" content="'.htmlspecialchars($description ?: $title, ENT_QUOTES).'">';
    echo '<link rel="canonical" href="https://grampay.net/'.htmlspecialchars(basename($_SERVER['SCRIPT_NAME'] ?? ''), ENT_QUOTES).'">';
    echo '<title>'.htmlspecialchars($title, ENT_QUOTES).' — GRAMPAY</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'; 
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">';
    echo '<style>*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,sans-serif;background:#070913;color:#f8fafc;line-height:1.65}a{color:#93c5fd;text-decoration:none}a:hover{text-decoration:underline}.wrap{max-width:1000px;margin:0 auto;padding:24px}.nav{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 0}.brand{font-weight:900;color:#fff;font-size:20px}.links{display:flex;gap:14px;flex-wrap:wrap;font-size:13px}.hero{padding:56px 0 24px}.eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.16em;color:#67e8f9;font-weight:800}.hero h1{font-size:42px;line-height:1.1;margin:10px 0 16px}.hero p{color:#a5b4c7;max-width:760px}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.card{background:rgba(15,20,34,.75);border:1px solid rgba(255,255,255,.09);border-radius:20px;padding:24px;backdrop-filter:blur(18px);margin-bottom:18px}.card h2{margin:0 0 12px;font-size:20px}.card h3{margin:0 0 8px;font-size:15px}.muted{color:#94a3b8}.note{border-left:3px solid #6366f1;padding:10px 14px;background:rgba(99,102,241,.08);border-radius:10px}.warn{border-left-color:#f59e0b;background:rgba(245,158,11,.08)}.ok{border-left-color:#34d399;background:rgba(52,211,153,.08)}.footer{border-top:1px solid rgba(255,255,255,.08);margin-top:50px;padding:24px 0;color:#64748b;font-size:12px}@media(max-width:720px){.grid{grid-template-columns:1fr}.hero h1{font-size:34px}.links{display:none}}</style></head><body><div class="wrap"><nav class="nav"><a class="brand" href="index.php">⚡ GRAMPAY</a><div class="links"><a href="docs.php">API</a><a href="pricing.php">Тарифы</a><a href="security.php">Безопасность</a><a href="faq.php">FAQ</a><a href="support.php">Поддержка</a></div></nav>';
}
function gpPageFooter(): void {
    echo '<footer class="footer">© '.date('Y').' GRAMPAY · <a href="legal.php">Документы</a> · <a href="refunds.php">Возвраты</a> · <a href="status.php">Статус</a> · <a href="docs.php">API</a></footer></div></body></html>';
}
