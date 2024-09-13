<?php

declare(strict_types=1);

use Yiisoft\Di\Command\DebugContainerCommand;

return [
    'yiisoft/yii-debug' => [
        'ignoredCommands' => [
            'debug:container',
        ],
    ],
    'yiisoft/yii-console' => [
        'commands' => [
            'debug:container' => DebugContainerCommand::class,
        ],
    ],
];
