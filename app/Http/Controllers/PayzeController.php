<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayzeIO\LaravelPayze\Models\PayzeTransaction;

class PayzeController extends \PayzeIO\LaravelPayze\Controllers\PayzeController
{
    /**
     * Success Response
     *
     * Do any transaction related operations and return a response
     * If nothing is returned, default response will be used
     *
     * @param  PayzeTransaction  $transaction
     * @param  Request  $request
     *
     * @return mixed
     */
    protected function successResponse(PayzeTransaction $transaction, Request $request)
    {
        /*
         * Do any transaction related operations and return a response
         * If nothing is returned, default response will be used
         */
    }

    /**
     * Fail Response
     *
     * Do any transaction related operations and return a response
     * If nothing is returned, default response will be used
     *
     * @param  PayzeTransaction  $transaction
     * @param  Request  $request
     *
     * @return mixed
     */
    protected function failResponse(PayzeTransaction $transaction, Request $request)
    {
        /*
         * Do any transaction related operations and return a response
         * If nothing is returned, default response will be used
         */
    }
}
