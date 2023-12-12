class Main {
    resultTable = undefined;
    dropdown = undefined;

    jq = undefined;

    constructor(jq) {
        this.jq = jq
    }

    init() {
        this.resultTable = this.jq('#result')
        this.dropdown = this.jq('#allowedGroups')
        this.fillAllowedFroups()
    }

    loadData(url, data) {
        return this.jq.get(url, data)
    }

    fillAllowedFroups() {
        var url = '/ajax/getAllowedGroups'

        this.loadData(url, '').done(function(data) {
            $.each(data.result, function(index, value) {
                $('#allowedGroups').append(
                    $('<li></li>').attr('href', '#')
                        .attr('idx', index).attr('value', value).text(value).addClass('dropdown-item cp')
                );
            });
        });
    }

    clearResultTable() {
        $('#result').hide();
        $('#result tbody').text('');
    }

    getSchedule() {
        var url = '/ajax/getSchedule'
        this.clearResultTable();

        var currentGroup =  $('#allowedGroups li.dropdown-item.active').attr('idx');
        var date =  $('#groupDate').val();

        if (date !== "") {
            if (/([\d+]{2}\.[\d+]{2})/.test(date) === false) {
                alert('Некорректно введена дата! Формат даты должен быть: DD.MM. Если идут числа с 1 по 9, то нужно ставить "ноль". Например: 01.01')
                return false;
            }
        }

        url = url + '/' + currentGroup + '/' + date;

        this.loadData(url, '').done(function(data) {
            if (data.result != null) {
                $.each(data.result, function(index, value) {
                    var tableTr = $('#result tbody').append('<tr></tr>');
                    $(tableTr.find('tr:last')).append('<th scope="row">' + (parseInt(index) + 1) + '</th>')
                        .append('<td class="dt">' + (value[0]) + '</td>').append('<td>' + (value[1] ?? '-') + '</td>');
                });

                $('#result').show();
            } else {
                alert('Нет данных на выбранную дату');
            }

        });
    }

    updateDropdownBtnText(text) {
        $('#selectGroupDropdown').text(text)
    }
}

$(document).ready(function() {
    var main = new Main($);
    main.init();

    $('#allowedGroups').on('click', 'li.dropdown-item', function(e) {
        $('#allowedGroups li.active').removeClass('active');

        main.updateDropdownBtnText($(this).attr('value'));
        $('#groupDate').focus();

        $(this).addClass('active');
    });

    $('#btnGetSchedule').on('click', '', function(e) {
       main.getSchedule();
    });

    $('#useDate').on('click', 'button', function() {
        $('#groupDate').val($(this).attr('value'));
    })


});