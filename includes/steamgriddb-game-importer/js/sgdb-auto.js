jQuery(document).ready(function ($) {
    $('#sgdb-search-btn').click(function () {
        const term = $('#sgdb-game-name').val();
        if (!term) return;
        $('#sgdb-results').html('搜索中...');
        $.post(sgdb_ajax.ajax_url, {
            action: 'sgdb_search_game',
            nonce: sgdb_ajax.nonce,
            term: term
        }, function (response) {
            console.log(response);
            if (response.success) {
                let html = '<ul>';
                response.data.forEach(game => {
                    html += `<li>
                        <strong>${game.name}</strong>
                        <button class="sgdb-create" data-id="${game.id}" data-name="${game.name}">导入</button>
                    </li>`;
                });
                html += '</ul>';
                $('#sgdb-results').html(html);
            } else {
                $('#sgdb-results').html('未找到结果');
            }
        });
    });

    $('#sgdb-results').on('click', '.sgdb-create', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        $(this).text('导入中...').attr('disabled', true);
        $.post(sgdb_ajax.ajax_url, {
            action: 'sgdb_fetch_and_create',
            nonce: sgdb_ajax.nonce,
            game_id: id,
            game_name: name
        }, function (response) {
            if (response.success) {
                $('#sgdb-results').html(`<p>文章创建成功：${response.data.title}</p>`);
            } else {
                alert('导入失败');
            }
        });
    });
});
