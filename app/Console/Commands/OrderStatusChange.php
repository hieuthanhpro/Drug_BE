<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OrderStatusChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:updateStatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status order after 3 hour confirm order from GDP';

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
        DB::statement("update t_order set status = 'prepared' where updated_at + INTERVAL '3 HOURS' <= NOW() and status = 'confirm'");
    }
}
