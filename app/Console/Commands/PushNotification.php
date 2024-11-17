<?php

namespace App\Console\Commands;

use App\Repositories\AdminNotification\AdminNotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class PushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push notification';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    private $notificationService;
    private $adminNotification;

    public function __construct(NotificationService                  $notificationService,
                                AdminNotificationRepositoryInterface $adminNotification)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->adminNotification = $adminNotification;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $listNoti = $this->adminNotification->getByTopStatus('10', 'waiting');
        if (isset($listNoti)) {
            $this->notificationService->pushNotification($listNoti);
        }
    }
}
