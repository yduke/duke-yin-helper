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
                let html = '<table class="widefat importers striped"><tbody>';
                response.data.forEach(game => {
                    var date = new Date(game.release_date*1000);
                    var year = date.getFullYear();
                    html += `<tr>
                        <td>${year}</td>
                        <td><strong>${game.name}</strong></td>  
                        <td><button class="button sgdb-create" data-id="${game.id}" data-name="${game.name}" data-release_date="${game.release_date}" data-types="${game.types}">导入</button>
                    </td></tr>`;
                });
                html += '</tbody></table>';
                $('#sgdb-results').html(html);
            } else {
                $('#sgdb-results').html('未找到结果');
            }
        });
    });

    $('#sgdb-results').on('click', '.sgdb-create', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const release_date = $(this).data('release_date');
        const platform = $(this).data('types');
        let status = $('#game-status').val();
        $(this).text('导入中...').attr('disabled', true);
        $(this).addClass('clicked');
        $.post(sgdb_ajax.ajax_url, {
            action: 'sgdb_fetch_and_create',
            nonce: sgdb_ajax.nonce,
            game_id: id,
            game_name: name,
            release_date: release_date,
            platform: platform,
            status: status,
        }, function (response) {
            $('.clicked').text('完成导入');
            if (response.success) {
                jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>'+'Import success, the post is <a target="_blank" href="'+ response.data.link +'">'+response.data.title+'</a>.</p></div>');
            } else {
                jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p>'+'Failed to import'+'</p></div>');
            }
        });
    });
});
