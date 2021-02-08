
function showNewOutgoingTaskForm() {
    // receive the form
    sendAjax(
        'get',
        '/user/get-outgoing-form',
        function (data){
            let frm = $(data['form']);
            console.log(frm);
            let modal = makeModal(
                'Создание новой задачи'
            )
            modal.find('div.modal-body').append(frm);
            normalReload();
        }
    )
}

function handleFilterResults() {
    let filterResultsBtn = $('.filter-view');
    let incomingFilterView = $('.incoming-filter-view');
    $('.dropdown-menu li').on('click', function (e) {
        e.cancelBubble = true;
        e.stopPropagation();
    });
    filterResultsBtn.on('hidden.bs.dropdown', function () {
        $('form#listStyleForm').submit();

    });
    incomingFilterView.on('hidden.bs.dropdown', function () {
        $('form#incomingListStyleForm').submit();

    });
}

function handleOrderSelect(){
    let sortCookie = getCookie('outgoingOrderBy');
    let select = $('#orderBySelect');
    select.on('change', function (){
        // отправлю запрос на установку сортировки
    });
}

$(function () {
    enableTabNavigation();
    handleAjaxActivators();

    // creating modal with form for create new ticket
    let createTaskBtn = $('#createTaskBtn');
    createTaskBtn.on('click.createTask', function (){
        showNewOutgoingTaskForm();
    });

    handleOrderSelect();
    handleFilterResults();
});