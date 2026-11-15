<?php
declare(strict_types=1);
session_start();
require dirname(__DIR__).'/vendor/autoload.php';
use Aymudo\Client;use Aymudo\Exception\AymudoException;use AymudoStarter\Dashboard;use AymudoStarter\Environment;
Environment::load(dirname(__DIR__).'/.env');
$appName=getenv('APP_NAME')?:'Aymudo PHP Starter';$error=null;$notice=null;$data=[];$state=['modules'=>[],'operations'=>[]];$view=preg_replace('/[^a-z-]/','',(string)($_GET['view']??'overview'))?:'overview';
if(!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(24));
try{
    $dashboard=new Dashboard(Client::fromEnvironment());$state=$dashboard->discover();if(!isset($state['modules'][$view]))$view='overview';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!hash_equals((string)$_SESSION['csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('The form expired. Reload the page and try again.');
        $operationKey=trim((string)($_POST['operationKey']??''));$operation=$dashboard->operation($operationKey);if($operation===null)throw new RuntimeException('This operation is not assigned to the workspace.');
        if(!empty($operation['mutating'])&&!isset($_POST['confirm']))throw new RuntimeException('Confirm this write before executing it.');
        $raw=(string)($_POST['input']??'{}');if(strlen($raw)>65536)throw new RuntimeException('Operation input exceeds 64 KiB.');$input=json_decode($raw,true,128,JSON_THROW_ON_ERROR);if(!is_array($input)||array_is_list($input))throw new RuntimeException('Operation input must be a JSON object.');
        $data=$dashboard->execute($operationKey,$input,trim((string)($_POST['idempotencyKey']??''))?:null);$notice='Operation completed: '.$operationKey;$view='operations';
    }else{$data=$dashboard->data($view);}
}catch(Throwable $e){$error=$e instanceof AymudoException?($e->getMessage().' ['.$e->errorCode.']'):$e->getMessage();}
$h=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$h($appName)?></title><link rel="stylesheet" href="/styles.css"></head><body>
<aside><div class="brand">Aymudo<span>PHP Starter</span></div><nav><?php foreach($state['modules'] as $id=>$module):?><a class="<?=$view===$id?'active':''?>" href="?view=<?=$h($id)?>"><strong><?=$h($module['title'])?></strong><small><?=$h($module['description'])?></small></a><?php endforeach;?></nav><a class="docs" href="https://www.aymudo.com/developers" target="_blank" rel="noreferrer">API documentation ↗</a></aside>
<main><header><div><p>CAPABILITY-DRIVEN WORKSPACE</p><h1><?=$h($state['modules'][$view]['title']??'Setup')?></h1><span><?=$h($state['modules'][$view]['description']??'Connect your Aymudo API key.')?></span></div><div class="status"><i></i><?=$error?'Configuration required':'Connected'?></div></header>
<?php if($error):?><section class="error"><h2>Request could not be completed</h2><p><?=$h($error)?></p><ol><li>Check the server-side API key and Store ID.</li><li>Confirm that the operation and its required scope are enabled.</li><li>For writes, retry only with the same idempotency key.</li></ol></section><?php else:?><?php if($notice):?><section class="notice"><?=$h($notice)?></section><?php endif;?><section class="summary"><div><b><?=count($state['modules'])?></b><span>enabled modules</span></div><div><b><?=count($state['operations'])?></b><span>assigned operations</span></div><div><b>Live</b><span>capability discovery</span></div></section>
<?php if($view==='operations'&&$state['operations']):?><section class="panel operation-runner"><div class="panel-head"><h2>Execute an assigned operation</h2><span>The catalogue below is generated from the live workspace contract.</span></div><form method="post"><input type="hidden" name="csrf" value="<?=$h($_SESSION['csrf'])?>"><label>Operation<select name="operationKey" required><?php foreach($state['operations'] as $operation):$key=(string)($operation['operationKey']??$operation['key']??'');?><option value="<?=$h($key)?>"><?=$h($key)?><?=$operation['mutating']??false?' · write':' · read'?></option><?php endforeach;?></select></label><label>Input JSON<textarea name="input" rows="9" spellcheck="false">{}</textarea></label><label>Stable idempotency key <input name="idempotencyKey" placeholder="optional; generated when empty"></label><label class="check"><input type="checkbox" name="confirm" value="1"> I confirm any selected write operation.</label><button type="submit">Execute</button></form></section><?php endif;?>
<section class="panel"><div class="panel-head"><h2>Live API response</h2><span>Only capabilities assigned to this store are displayed.</span></div><pre><?=$h(json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></pre></section><?php endif;?></main></body></html>
