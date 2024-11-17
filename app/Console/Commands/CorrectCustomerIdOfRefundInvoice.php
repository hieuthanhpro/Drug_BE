<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CorrectCustomerIdOfRefundInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:correct-customer-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correct customerId of table invoice for invoice refund NCC';

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
        $invoices_refund = \App\Models\Invoice::whereNotNull('refer_id')
            ->where('invoice_type', 'IV4')
            ->get()->toArray();
        try {
            \DB::beginTransaction();
            foreach ($invoices_refund as $invoice_refund) {
                $invoice_original = \App\Models\Invoice::where('id', $invoice_refund['refer_id'])->first();
                \App\Models\Invoice::where('id', $invoice_refund['id'])->update(['customer_id' => $invoice_original->customer_id]);

                echo 'Update invoice '. $invoice_refund['id']. ' success'. PHP_EOL;
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            LogEx::try_catch($this->className, $e);
            $this->line($e->getMessage());
            return 1;
        }
    }
}
