<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => -1000,
            'currency_code' => 'XXX',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency_code']);
    }

    public function testCustomerCannotDeleteOtherCustomerTransaction()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $response = $this->deleteJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(405);
    }
}
