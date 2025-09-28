<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowUploadLimits extends Command
{
    protected $signature = 'upload:limits';
    protected $description = 'Tampilkan konfigurasi batas upload PHP (max_file_uploads, upload_max_filesize, post_max_size)';

    public function handle(): int
    {
        $this->info('Konfigurasi PHP terkait upload:');
        $vals = [
            'max_file_uploads'   => ini_get('max_file_uploads'),
            'upload_max_filesize'=> ini_get('upload_max_filesize'),
            'post_max_size'      => ini_get('post_max_size'),
            'memory_limit'       => ini_get('memory_limit'),
            'max_input_vars'     => ini_get('max_input_vars'),
        ];
        foreach ($vals as $k=>$v) {
            $this->line(" - $k: $v");
        }
        $this->newLine();
        $this->line('Catatan: Jika max_file_uploads = 20 dan Anda kirim >20 file sekaligus, sisanya tidak akan diterima PHP. Naikkan di php.ini, contoh:');
        $this->line('  max_file_uploads = 60');
        $this->line('  upload_max_filesize = 8M (atau lebih)');
        $this->line('  post_max_size = 32M (lebih besar dari total semua file)');
        return self::SUCCESS;
    }
}
