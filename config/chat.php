<?php

declare(strict_types=1);
return [
    'storage' => getenv('CHAT_STORAGE') ?: PROJECT_ROOT . '/storage/chat',
    'max_file_bytes' => 10 * 1024 * 1024,
    'antivirus_binary' => getenv('CHAT_AV_BINARY') ?: '',
    'require_scan' => (getenv('APP_ENV') === 'production') || filter_var(getenv('CHAT_REQUIRE_SCAN') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    'ws_url' => getenv('CHAT_WS_URL') ?: '',
    'meeting_url' => rtrim(getenv('CHAT_MEETING_URL') ?: '', '/'),
    'meeting_app_id' => getenv('CHAT_MEETING_APP_ID') ?: '',
    'meeting_secret' => getenv('CHAT_MEETING_SECRET') ?: '',
];
