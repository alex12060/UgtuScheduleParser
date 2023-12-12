<?php require_once (APP . '/Views/Parts/header.php') ?>
<!-- Begin page content -->
<main class="flex-shrink-0">
    <div class="container">
        <h1 class="mt-5">Расписание занятий ФЭУиИТ</h1>
        <p class="lead">Выберите из списков предлагаемый вариант и запросите результат.</p>

        <div class="input-group mb-3">
            <button class="btn btn-outline-secondary dropdown-toggle" id="selectGroupDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">Выберите группу</button>
            <ul class="dropdown-menu" id="allowedGroups">
            </ul>
            <input type="text" class="form-control" id="groupDate" placeholder="Пример: 12.12" value="<?php echo date('d.m'); ?>" title="Пример: 12.12">
            <button type="button" id="btnGetSchedule" class="btn btn-outline-secondary border-success">Просмотр</button>
        </div>

        <div class="d-flex gap-2 justify-content-center" id="useDate">
            <button type="button" class="btn btn-light rounded-pill px-3" value="<?php echo date('d.m'); ?>">Сегодня</button>
            <button type="button" class="btn btn-light rounded-pill px-3" value="<?php echo date('d.m', strtotime("+1 day")); ?>">Завтра</button>
            <button type="button" class="btn btn-light rounded-pill px-3" value="<?php echo date('d.m', strtotime("+2 days")); ?>">Послезавтра</button>
            <button type="button" class="btn btn-light rounded-pill px-3" value="<?php echo date('d.m', strtotime("-1 day")); ?>">Вчера</button>
            <button type="button" class="btn btn-light rounded-pill px-3" value="<?php echo date('d.m', strtotime("-2 days")); ?>">Позавчера</button>
        </div>

        <div class="mt-4" id="result">
            <table class="table table-bordered table-hover text-center p-5">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Время</th>
                    <th scope="col">Наименование</th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require_once (APP . '/Views/Parts/footer.php') ?>