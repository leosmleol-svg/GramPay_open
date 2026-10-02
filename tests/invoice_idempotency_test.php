<?php
declare(strict_types=1);
// Run the real endpoint in isolation; no real provider, credentials or data files.
$root = sys_get_temp_dir() . '/gp-invoice-' . bin2hex(random_bytes(8));
mkdir($root . '/api/v1', 0700, true);
copy(dirname(__DIR__) . '/api/v1/create_invoice.php', $root . '/api/v1/create_invoice.php');
file_put_contents($root . '/storage.php', <<<'MOCK'
<?php
function getApiKeyFromRequest() { return 'test-only'; }
function findUserByApiKey($key) { return ['id'=>7,'username'=>'test']; }
function findTransactionByIdempotencyKey($user, $key) {
    if ($user !== 7 || $key !== 'existing') return null;
    return ['transaction_id'=>'old-invoice','amount'=>100,'payment_method'=>2,'fee'=>5,'total_amount'=>105,'currency'=>'RUB','status'=>'PENDING','redirect_url'=>'https://example.test/pay?mh=test-hash'];
}
function saveTransaction($tx) {
    file_put_contents(__DIR__ . '/saved.json', json_encode($tx));
}
MOCK
);
file_put_contents($root . '/config.php', "<?php return ['payment_methods'=>[2=>'SBP',11=>'Card'],'method_min_amounts'=>[2=>1,11=>1],'platega'=>[]];");
file_put_contents($root . '/PlategaService.php', <<<'MOCK'
<?php
class PlategaService {
    public function __construct($config) {}
    public function createTransaction(...$args) {
        file_put_contents(__DIR__ . '/provider-called', '1');
        return ['transactionId'=>'new-invoice','redirect'=>'https://example.test/pay','status'=>'PENDING'];
    }
}
MOCK
);
file_put_contents($root . '/run.php', <<<'MOCK'
<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = json_decode($argv[1], true);
ob_start();
register_shutdown_function(function () {
    $body = json_decode(ob_get_clean(), true);
    echo json_encode(['status'=>http_response_code(), 'body'=>$body]);
});
require __DIR__ . '/api/v1/create_invoice.php';
MOCK
);
$cases = [
    ['changed amount', ['amount'=>1,'payment_method'=>2,'idempotency_key'=>'existing'],409,false],
    ['identical retry', ['amount'=>100,'payment_method'=>2,'idempotency_key'=>'existing'],200,false],
    ['normalized retry', ['amount'=>'100','idempotency_key'=>'existing'],200,false],
    ['changed method', ['amount'=>100,'payment_method'=>11,'idempotency_key'=>'existing'],409,false],
    ['invalid amount', ['amount'=>0,'idempotency_key'=>'existing'],400,false],
    ['missing amount', ['idempotency_key'=>'existing'],400,false],
    ['new key one ruble', ['amount'=>1,'payment_method'=>2,'idempotency_key'=>'new-order'],200,true],
    ['no key one ruble', ['amount'=>1,'payment_method'=>2],200,true],
];
try {
    foreach ($cases as [$name,$input,$expectedStatus,$expectProvider]) {
        @unlink($root . '/provider-called'); @unlink($root . '/saved.json');
        $proc=proc_open([PHP_BINARY,$root . '/run.php',json_encode($input)], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        fclose($pipes[0]); $output=stream_get_contents($pipes[1]); $errors=stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]); $exit=proc_close($proc);
        $result=json_decode($output,true);
        if ($exit!==0 || ($result['status']??null)!==$expectedStatus) throw new RuntimeException($name . ': ' . $output . $errors);
        if (is_file($root . '/provider-called') !== $expectProvider) throw new RuntimeException($name . ': unexpected provider call');
        $body=$result['body'];
        if ($expectedStatus===409 && (($body['code']??'')!=='IDEMPOTENCY_CONFLICT' || ($body['ok']??true)!==false)) throw new RuntimeException('Conflict not reported');
        if ($expectedStatus===200 && !$expectProvider && (($body['amount']??0)!==100 || ($body['idempotent']??false)!==true)) throw new RuntimeException('Replay changed');
        if ($expectProvider) {
            $saved=json_decode(file_get_contents($root . '/saved.json'),true);
            foreach ([$saved,$body] as $data) {
                if ($data['amount']!==1 || $data['fee']!==0 || $data['total_amount']!==1) throw new RuntimeException('One-ruble price is incorrect');
            }
        }
        echo 'PASS ' . $name . "\n";
    }
} finally {
    $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname()); }
    rmdir($root);
}
