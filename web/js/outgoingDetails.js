$(function () {
    let cancelTaskBtn = $('button#cancelTaskBtn');
    cancelTaskBtn.on('click.requestCancel', function () {
        makeInformerModal(
            'Отмена задачи',
            'Отменить задачу? Её нельзя будет активировать снова, только создать ещё одну.',
            function () {
                sendAjax(
                    'post',
                    '/cancel-task',
                    simpleAnswerHandler,
                    {'taskId': cancelTaskBtn.attr('data-task-id')}
                )
            },
            function () {
            }
        )
    });
});