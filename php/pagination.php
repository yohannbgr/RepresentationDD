<?php if ($nb_pages > 1): ?>
<div class="pagination">
    <?php
    $params_url = $_GET;
    if ($page > 1):
        $params_url['page'] = $page - 1;
    ?><a href="?<?= http_build_query($params_url) ?>">←</a><?php endif; ?>
    <?php for ($i = max(1,$page-2); $i <= min($nb_pages,$page+2); $i++):
        $params_url['page'] = $i; ?>
        <?php if ($i == $page): ?>
            <span class="current"><?= $i ?></span>
        <?php else: ?>
            <a href="?<?= http_build_query($params_url) ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($page < $nb_pages):
        $params_url['page'] = $page + 1;
    ?><a href="?<?= http_build_query($params_url) ?>">→</a><?php endif; ?>
</div>
<?php endif; ?>
