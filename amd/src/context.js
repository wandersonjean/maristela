// AMD module to populate context cards (grades, upcoming, forums)
define(['jquery'], function($) {
    const initContext = function(opts) {
        const courseid = (opts && opts.courseid) || 0;
        const root = $('.maristtela-context');
        if (!root.length || !courseid) { return; }
        const endpoint = M.cfg.wwwroot + '/blocks/maristtela/api/context.php?courseid=' + courseid;
        root.addClass('loading');
        $.getJSON(endpoint)
          .done(function(data){
            // Grades
            const gbox = root.find('[data-box="grades"] .box-body');
            gbox.empty();
            if (data.grades && data.grades.length) {
                data.grades.slice(0,6).forEach(function(g){
                    const gradeStr = (g.grade !== null) ? (g.grade + '/' + (g.grademax || '-')) : '—';
                    gbox.append('<div class="row-item"><span>'+g.name+'</span><strong>'+gradeStr+'</strong></div>');
                });
            } else {
                gbox.append('<div class="empty">Sem notas disponíveis.</div>');
            }
            // Upcoming
            const ubox = root.find('[data-box="upcoming"] .box-body');
            ubox.empty();
            if (data.upcoming && data.upcoming.length) {
                data.upcoming.sort(function(a,b){return a.deadline-b.deadline;}).slice(0,6).forEach(function(ev){
                    const dt = new Date(ev.deadline * 1000).toLocaleString();
                    const href = ev.url || '#';
                    ubox.append('<a class="row-item" href="'+href+'"><span>'+ev.title+'</span><em>'+dt+'</em></a>');
                });
            } else {
                ubox.append('<div class="empty">Sem prazos próximos.</div>');
            }
            // Forums
            const fbox = root.find('[data-box="forums"] .box-body');
            fbox.empty();
            if (data.forums && data.forums.length) {
                data.forums.slice(0,6).forEach(function(fd){
                    const dt = new Date(fd.timemodified * 1000).toLocaleString();
                    fbox.append('<a class="row-item" href="'+fd.url+'"><span>'+fd.forum+': '+fd.discussion+'</span><em>'+dt+'</em></a>');
                });
            } else {
                fbox.append('<div class="empty">Sem discussões recentes.</div>');
            }
          })
          .fail(function(){
            root.find('.box-body').html('<div class="empty">Falha ao carregar o contexto.</div>');
          })
          .always(function(){
            root.removeClass('loading');
          });
    };
    return { initContext: initContext };
});
