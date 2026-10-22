<?php
/** Read local .env as data, never as executable shell. Existing environment wins. */
declare(strict_types=1);
$file=dirname(__DIR__).'/.env';
if(is_file($file)) foreach(file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
    if(preg_match('/^\s*([A-Z][A-Z0-9_]*)=(.*)$/D',$line,$match) && getenv($match[1])===false) {
        $value=trim($match[2]);
        if(strlen($value)>=2 && (($value[0]==='"' && str_ends_with($value,'"')) || ($value[0]==="'" && str_ends_with($value,"'")))) $value=substr($value,1,-1);
        putenv($match[1].'='.$value);
    }
}
