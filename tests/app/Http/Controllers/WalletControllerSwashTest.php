<?php
/**
 * Copyright (c) 2018-2022 Adshares sp. z o.o.
 *
 * This file is part of AdServer
 *
 * AdServer is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

// phpcs:ignoreFile PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound

declare(strict_types=1);

namespace Adshares\Adserver\Tests\Http\Controllers;

use Adshares\Adserver\Jobs\AdsSendOne;
use Adshares\Adserver\Mail\WalletConnectConfirm;
use Adshares\Adserver\Mail\WalletConnected;
use Adshares\Adserver\Mail\WithdrawalApproval;
use Adshares\Adserver\Models\Token;
use Adshares\Adserver\Models\User;
use Adshares\Adserver\Models\UserLedgerEntry;
use Adshares\Adserver\Tests\TestCase;
use Adshares\Common\Domain\ValueObject\WalletAddress;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

class WalletControllerSwashTest extends TestCase
{
    private const CONNECT_INIT_URI = '/api/wallet/connect/init';
    private const CONNECT_URI = '/api/wallet/connect';
    private const CONNECT_CONFIRM_URI = '/api/wallet/connect/confirm';

    public function testWithdrawSwash(): void
    {
        Mail::fake();
        Queue::fake();
        $this->createSwashEntries();

        $user = factory(User::class)->create([
            'email_confirmed_at' => now(),
            'admin_confirmed_at' => now(),
            'email' => null,
            'wallet_address' => new WalletAddress(WalletAddress::NETWORK_ADS, '0001-00000001-8B4E')
        ]);
        $user->is_admin = true;
        $user->saveOrFail();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/wallet/withdrawBatch');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['to' => config('app.swash_bsc_address'), 'total' => 151924038]);
        $json = $response->decodeResponseJson();
        $userLedgerEntry = UserLedgerEntry::getFirstRecordByBatchId($json['batch']);
        $this->assertEquals(0, UserLedgerEntry::getWalletBalanceForAllUsers());
        $this->assertNotNull($userLedgerEntry);
        $this->assertEquals(UserLedgerEntry::STATUS_PENDING, $userLedgerEntry->status);
        foreach ($json['shares'] as $item) {
            if($item['uid'] === $userLedgerEntry->user->name){
                $this->assertEquals($item['ads'], $userLedgerEntry->amount * -1);
            }
        }
    }

    private function generateUserIncome(int $userId, int $amount): void
    {
        $dateString = '2018-10-24 15:00:49';

        $ul = new UserLedgerEntry();
        $ul->user_id = $userId;
        $ul->amount = $amount;
        $ul->address_from = '0001-00000000-XXXX';
        $ul->address_to = '0001-00000000-XXXX';
        $ul->txid = '0001:0000000A:0001';
        $ul->type = UserLedgerEntry::TYPE_DEPOSIT;
        $ul->setCreatedAt($dateString);
        $ul->setUpdatedAt($dateString);
        $ul->save();
    }

    private function createSwashEntries(): void
    {
        $entries = [
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800001', 100000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800002', 40000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800003', 1000000],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800004', 0],
            ['0x9552D752001721d43d8F04AC4FDfb7aE27800005', 11000000]
        ];    
        foreach ($entries as $entry) {
            $user = factory(User::class)->create();
            $user->name = $entry[0];
            $user->save();
            $this->generateUserIncome($user->id, $entry[1]);
        }
    }
}
