<?php
declare(strict_types=1);
$app = require __DIR__.'/../bootstrap/app.php';
use App\Support\PortalSecurity;
use App\Support\PortalException;
$checks=0;
function secureCheck(bool $ok,string $why): void { global $checks; $checks++; if (!$ok) throw new RuntimeException($why); }
// RFC 6238 test vector, SHA-1, six-digit truncation.
secureCheck(PortalSecurity::totp('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',1)==='287082','TOTP RFC vector');
$secret=PortalSecurity::totpSecret();$key=str_repeat('k',32);
secureCheck(PortalSecurity::decrypt(PortalSecurity::encrypt($secret,$key),$key)===$secret,'Encrypted MFA key roundtrip');
try { PortalSecurity::decrypt(PortalSecurity::encrypt($secret,$key),str_repeat('z',32)); throw new RuntimeException('Bad key accepted'); } catch(PortalException) { secureCheck(true,'Bad key rejected'); }
$_SESSION=[];$token=PortalSecurity::csrf();secureCheck(strlen($token)===64,'CSRF entropy');PortalSecurity::verifyCsrf($token);
try { PortalSecurity::verifyCsrf('bad');throw new RuntimeException('CSRF accepted'); } catch(PortalException $e) { secureCheck($e->status===419,'CSRF rejected'); }
echo "$checks contrôles de sécurité PHP réussis.\n";
