<?php

return [
    'redirect_not_success' => 'Redirect response (3xx): not counted as success.',
    'http_status_not_success' => 'HTTP status :code is not in the 2xx range.',
    'ssl_or_tls_error' => 'SSL/TLS or certificate error.',
    'mail_subject_up' => 'Recovered: :host',
    'mail_subject_down' => 'Down: :host',
    'check_url' => 'Check URL',
    'state_recovered' => 'The check for :host is OK again (2xx, valid TLS).',
    'state_failed' => 'The check for :host failed or is not OK.',
    'telegram_up' => 'UP: :host',
    'telegram_down' => 'DOWN: :host',
];
