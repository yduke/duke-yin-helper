jQuery(document).ready(function ($) {
    $('#sgdb-search-btn').click(function () {
        const term = $('#sgdb-game-name').val().trim();
        if (!term) return;
        $('#sgdb-results').html('搜索中...');
        $.post(sgdb_ajax.ajax_url, {
            action: 'sgdb_search_game',
            nonce: sgdb_ajax.nonce,
            term: term
        }, function (response) {
            // console.log(response);
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
        let id = $(this).data('id');
        let name = $(this).data('name').trim();
        let release_date = $(this).data('release_date');
        let platform = $(this).data('types');
        let status = $('#game-status').val();
        let short_review = $('#short-review').val().trim();
        let score = $('#score').val().trim();
        let graphic = $('#graphic-score').val().trim();
        let audio = $('#audios-score').val().trim();
        let narrative = $('#narrative-score').val().trim();
        let technical = $('#technical-score').val().trim();
        let gameplay = $('#gameplay-score').val().trim();
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
            short_review: short_review,
            score: score,
            graphic: graphic,
            audio: audio,
            narrative: narrative,
            technical: technical,
            gameplay: gameplay
        }, function (response) {
            $('.clicked').text('完成导入');
            if (response.success) {
                jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>'+'Import success, the post is <a target="_blank" href="'+ response.data.link +'">'+response.data.title+'</a>, edit it <a target="_blank" href="'+ response.data.edit_link +'">here</a>.</p></div>');
            } else {
                jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p>'+'Failed to import'+'</p></div>');
            }
        });
    });

    //Game score calculate
    if ($('body[class*="page_dk-game-importer"]').length > 0) {
      $('input').on('input',function(){
        var $graphic = $('#graphic-score').val();
          var $graphic_w;
          $graphic == 0?$graphic_w = 0:$graphic_w = 0.2;
        var $audios = $('#audios-score').val();
          var $audios_w;
          $audios == 0?$audios_w = 0:$audios_w = 0.05;
        var $narrative = $('#narrative-score').val();
          var $narrative_w;
          $narrative == 0?$narrative_w = 0:$narrative_w = 0.25;
        var $technical = $('#technical-score').val();
          var $technical_w;
          $technical == 0?$technical_w = 0:$technical_w = 0.1;
        var $gameplay = $('#gameplay-score').val();
          var $gameplay_w;
          $gameplay == 0?$gameplay_w = 0:$gameplay_w = 0.4;
        var score = 0;
        score = (parseInt($graphic)*$graphic_w + parseInt($audios)*$audios_w + parseInt($narrative)*$narrative_w + parseInt($technical)*$technical_w + parseInt($gameplay)*$gameplay_w)/($graphic_w+$audios_w+$narrative_w+$technical_w+$gameplay_w);
        $('#score').val(score.toFixed(1));
        $('#score').next().find('.range-value').html('<strong>' + score.toFixed(1) + '</strong>' );
      });
    
      };
});
