<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PromotionStatusChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotion:updateStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status promotion';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::statement("update promotion set status = 'running' where start_date <= NOW() and (end_date is null or end_date > NOW()) and status = 'pending'");
        DB::statement("update promotion set status = 'ended' where end_date is not null and end_date < NOW() and status = 'running'");
    }
}
