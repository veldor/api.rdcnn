$(function () {
    let acceptTaskBtn = $('button#acceptTaskBtn');
    acceptTaskBtn.on('click.acceptTask', function () {
        makeInformerModal(
            'Принятие задачи',
            'Укажите, сколько дней потребуется вам на решение задачи.<br/><input class="form-control" value="1" id="daysForTaskFinish" type="number" min="1" max="100">',
            function () {
                let valueInput = $('#daysForTaskFinish');
                let val = valueInput.val();
                if(val > 0 && val < 100){
                    sendAjax(
                        'post',
                        '/accept-task',
                        simpleAnswerHandler,
                        {'taskId': acceptTaskBtn.attr('data-task-id'), 'daysForFinish' : val}
                    )
                }
                else{
                    alert('Так не пойдёт, надо ввести количество дней!');
                }
            },
            function () {
            }
        )
    });

    let finishTaskBtn = $('button#finishTaskBtn');
    finishTaskBtn.on('click.requestFinishTask', function () {
        makeInformerModal(
            'Подтверждение выполнения задачи',
            'Отметить задачу как выполненную?',
            function () {
                sendAjax(
                    'post',
                    '/executor/finish-task',
                    simpleAnswerHandler,
                    {'taskId': finishTaskBtn.attr('data-task-id')}
                )
            },
            function () {
            }
        )
    });
    let cancelTaskBtn = $('button#cancelTaskBtn');
    cancelTaskBtn.on('click.requestCancelTask', function () {
        makeInformerModal(
            'Отказ от задачи',
            'Не хотите или не можете выполнить задачу? Опишите причину, по которой это невозможно<br/><input id="taskCancelReason" class="form-control">',
            function () {
                let valueInput = $('input#taskCancelReason');
                let val = valueInput.val();
                if(val){
                    sendAjax(
                        'post',
                        '/executor/cancel-task',
                        simpleAnswerHandler,
                        {'taskId': cancelTaskBtn.attr('data-task-id'), 'reason' : val}
                    )
                }
                else{
                    alert('Так не пойдёт, надо ввести причину отказа!');
                }
            },
            function () {
            }
        )
    });
});