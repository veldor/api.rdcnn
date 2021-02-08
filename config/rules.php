<?php

return [
    'api' => 'api/do',
    'login' => 'site/login',
    'cancel-task' => 'user/cancel-task',
    'accept-task' => 'executor/accept-task',
    'user/delete/<userId:\d+>' => 'manage/delete-user',
    '/task_images/<imageName:.+>' => 'files/show-task-image',
    '/task_attachments/<fileName:.+>' => 'files/load-task-attachment',
    'outgoing-task/details/<taskId:\d+>' => 'user/outgoing-task-details',
];