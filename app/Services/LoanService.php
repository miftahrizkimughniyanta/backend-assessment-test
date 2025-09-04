<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        //
        $pinjaman = Loan::create([
            'user_id'            => $user->id,
            'amount'             => $amount,
            'terms'              => $terms,
            'currency_code'      => $currencyCode,
            'processed_at'       => $processedAt,
            'status'             => Loan::STATUS_DUE,
            'outstanding_amount' => $amount,
        ]);

        // ini supaya cicilan terakhir 1667 bukan 1668 tapi gagal karena 4999 != 5000
        // $amount_per_periode = floor($amount / $terms);
        // $amount_akumulasi = 0;
        // for ($i = 1; $i <= $terms; $i++) {
        //     $amount_akumulasi = $amount_per_periode;

        //     if($i == $terms){
        //         $amount_akumulasi = $amount - $amount_per_periode -1;
        //     }
        //     $amount_per_periode += $amount_akumulasi;
        //     $pinjaman->scheduledRepayments()->create([
        //         'amount'             => $amount_akumulasi,
        //         'outstanding_amount' => $amount_akumulasi,
        //         'currency_code'      => $currencyCode,
        //         'due_date'           => Carbon::parse($processedAt)->addMonths($i)->format('Y-m-d'),
        //         'status'             => ScheduledRepayment::STATUS_DUE,
        //     ]);
        // }
        $amount_per_periode = floor($amount / $terms);
        $nilai_sisa_pembulatan  = $amount - ($amount_per_periode * $terms);
        for ($i = 1; $i <= $terms; $i++) {
            $amount_akumulasi = $amount_per_periode;
            if ($i === $terms) {
                $amount_akumulasi += $nilai_sisa_pembulatan;
            }

            $pinjaman->scheduledRepayments()->create([
                'amount'             => $amount_akumulasi,
                'outstanding_amount' => $amount_akumulasi,
                'currency_code'      => $currencyCode,
                'due_date'           => Carbon::parse($processedAt)->addMonths($i)->format('Y-m-d'),
                'status'             => ScheduledRepayment::STATUS_DUE,
            ]);
        }


        return $pinjaman;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        //
    }
}
