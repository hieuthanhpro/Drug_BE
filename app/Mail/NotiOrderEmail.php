<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\LibExtension\LogEx;

class NotiOrderEmail extends Mailable
{
    use Queueable, SerializesModels;

    private $order;
    private $drugStore;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($drugStore, $order)
    {
        LogEx::information("NotiOrderEmail - __construct");
        $this->order = $order;
        $this->drugStore = $drugStore;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        LogEx::information("NotiOrderEmail - build");
        return $this->subject('[Sphacy] Đơn đặt hàng thuốc')
            ->view('vendor.mail.noticeorder')->with([
                'drug_name' => $this->drugStore->name,
                'order_code' => $this->order['order']->order_code,
            ]);
    }
}
