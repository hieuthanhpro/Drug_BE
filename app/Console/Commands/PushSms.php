<?php

namespace App\Console\Commands;

use App\Repositories\StoreNotification\StoreNotificationRepositoryInterface;
use App\Services\SMSService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push SMS';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $smsService;
    private $storeNotification;

    public function __construct(SMSService                  $smsService,
                                StoreNotificationRepositoryInterface $storeNotification)
    {
        parent::__construct();
        $this->smsService = $smsService;
        $this->storeNotification = $storeNotification;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $listNoti = $this->storeNotification->getTopByIsSent('10', false);
        if (isset($listNoti)) {
            foreach ($listNoti as $noti){
                $this->smsService->sendSMS($noti->content_sms, $noti->user_phone);
                DB::statement("update store_notification set is_sent = true, updated_at = NOW() where id = ".$noti->id);
            }
        }
    }
}
