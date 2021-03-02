<?php

return [
    'api' => 'api/do',
    'get-file' => 'api/file',
    'login' => 'site/login',
    'cancel-task' => 'user/cancel-task',
    'schedule/hash' => 'user/show-schedule-hash',
    'accept-task' => 'executor/accept-task',
    'user/delete/<userId:\d+>' => 'manage/delete-user',
    'delete-task/<taskId:\d+>' => 'manage/delete-task',
    'delegate-task/<taskId:\d+>' => 'manage/delegate-task',
    'set-executor/<taskId:\d+>/<executorId:\d+>' => 'manage/set-executor',
    'task/set-target/<taskId:\d+>/<targetId:\d+>' => 'manage/set-target',
    'change-task-target/<taskId:\d+>' => 'manage/change-task-target',
    '/task_images/<imageName:.+>' => 'files/show-task-image',
    '/task_attachments/<fileName:.+>' => 'files/load-task-attachment',
    'outgoing-task/details/<taskId:\d+>' => 'user/outgoing-task-details',
];