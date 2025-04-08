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
                            // Clear previous results
                            $('#tmdb-results').empty();
            if (res.success) {
                console.log(res);
                if (res.data.length === 0) {
                    $('#tmdb-results').html('<p>没有找到相关内容</p>');
                    return;
                }

                let html = '<ul>';
                res.data.forEach(function (item) {
                    let title = item.title || item.name;
                    html += `<li><a href="https://media.themoviedb.org/t/p/w220_and_h330_face${item.poster_path}" target="_blank">Poster</a> <strong>${title}</strong> (${item.first_air_date || item.release_date}) 
                             <button class="select-item" data-id="${item.id}" data-type="${type}">选择</button></li>`;
                });
                html += '</ul>';
                $('#tmdb-results').html(html);
            } else {
                $('#tmdb-results').html('<p>查询失败</p>');
            }
        });
    });

    $('#tmdb-results').on('click', '.select-item', function () {
        let id = $(this).data('id');
        let type = $(this).data('type');
        $(this).text('正在导入...');
        $.post(tmdb_ajax.ajax_url, {
            action: 'tmdb_select',
            nonce: tmdb_ajax.nonce,
            id: id,
            type: type
        }, function (res) {
            console.log(res);
            if (res.success) {
                $(this).text('完成导入');
                // alert('导入成功！文章ID: ' + res.data.post_id);
                jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>'+'import success'+'</p></div>');
            } else {
                // alert('导入失败！');
                $(this).text('导入失败！');
                jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p>'+'Failed to import'+'</p></div>');
            }
        });
    });
});
