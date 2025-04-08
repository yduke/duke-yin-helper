jQuery(document).ready(function ($) {
    $('#tmdb-search-btn').on('click', function () {
        let name = $('#tmdb-movie-name').val();
        let type = $('#tmdb-content-type').val();
        $('#tmdb-results').html('<p>正在搜索...</p>');
        $.post(tmdb_ajax.ajax_url, {
            action: 'tmdb_search',
            nonce: tmdb_ajax.nonce,
            name: name,
            type: type
        }, function (res) {
            $('#tmdb-results').empty();
            if (res.success) {
                if (res.data.length === 0) {
                    $('#tmdb-results').html('<p>没有找到相关内容</p>');
                    return;
                }

                let html = '<table class="widefat importers striped"><tbody>';
                res.data.forEach(function (item) {
                    let title = item.title || item.name;
                    html += `<tr><td><a href="https://media.themoviedb.org/t/p/w220_and_h330_face${item.poster_path}" target="_blank">[海报]</a> <strong>${title}</strong> (${item.first_air_date || item.release_date}) </td>
                                 <td><button class="select-item" data-id="${item.id}" data-type="${type}">选择并导入</button></td></tr>`;
                });
                html += '</tbody></table>';
                $('#tmdb-results').html(html);
            } else {
                $('#tmdb-results').html('<p>查询失败</p>');
            }
        });
    });

    $('#tmdb-results').on('click', '.select-item', function () {
        let id = $(this).data('id');
        let type = $(this).data('type');
        let inputVal = $('#score').val();
        let score = (inputVal && inputVal.trim() !== 0) ? inputVal.trim() : 5;
        let status = $('#tmdb-status').val();
        $(this).text('正在导入...');
        $.post(tmdb_ajax.ajax_url, {
            action: 'tmdb_select',
            nonce: tmdb_ajax.nonce,
            id: id,
            type: type,
            score: score,
            status: status,
        }, function (res) {
            $(this).text('完成导入');
            if (res.success) {
                jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>'+'import success'+'</p></div>');
            } else {
                $(this).text('导入失败！');
                jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p>'+'Failed to import'+'</p></div>');
            }
        });
    });
});
