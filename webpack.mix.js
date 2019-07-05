let mix = require('laravel-mix');
mix.setPublicPath('assets');
mix.setResourceRoot('../');

mix
    .js('src/admin/Boot.js', 'assets/js/jobboard-boot.js')
    .js('src/admin/main.js', 'assets/js/jobboard-admin.js')
    .js('src/public/public.js', 'assets/js/jobboard-public.js')
    .js('src/public/fileupload.js', 'assets/js/fileupload.js')
    .js('src/integrations/tinymce.js', 'assets/js/tinymce.js')
    .sass('src/scss/admin/app_edit.scss', 'assets/css/jobboard-edit.css')
    .sass('src/scss/admin/app.scss', 'assets/css/jobboard-admin.css')
    .sass('src/scss/admin/jobboard-print.scss', 'assets/css/jobboard-print.css')
    .sass('src/scss/public/public.scss', 'assets/css/wp_job_board-public.css')
    .sass('src/scss/public/joblist.scss', 'assets/css/joblist.css')
    .copy('src/images', 'assets/images')
    .copy('src/integrations/tinymce_icon.png', 'assets/js/tinymce_icon.png')
    .copy('src/libs', 'assets/libs');
